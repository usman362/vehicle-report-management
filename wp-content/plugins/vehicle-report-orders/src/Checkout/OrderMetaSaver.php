<?php
/**
 * Writes the order's report metadata at purchase time.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Checkout;

use VehicleReportOrders\Audit\AuditLogger;
use VehicleReportOrders\Domain\FulfillmentStatus;
use VehicleReportOrders\Domain\OrderMeta;
use VehicleReportOrders\Domain\RegistrationNumber;
use VehicleReportOrders\Domain\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Snapshots what the customer was actually promised.
 *
 * Price, delivery window and terms version are copied onto the order as it is
 * created. If the merchant later raises the price or shortens the window, the
 * order still shows what was agreed at the time, which is what a dispute or a
 * chargeback turns on.
 */
final class OrderMetaSaver {

	/**
	 * Hook order creation for both checkouts.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_checkout_create_order', array( $this, 'capture' ), 20, 1 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'capture' ), 20, 1 );
	}

	/**
	 * Copy the registration and the promises onto the order.
	 *
	 * @param \WC_Order $order Order being created.
	 *
	 * @return void
	 */
	public function capture( $order ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$entered = RegistrationField::submitted_value( $order );

		if ( '' !== $entered ) {
			$normalized = RegistrationNumber::normalize( $entered );

			$order->update_meta_data( OrderMeta::ORIGINAL_REGISTRATION, $entered );
			$order->update_meta_data( OrderMeta::NORMALIZED_REGISTRATION, $normalized );
		}

		$order->update_meta_data( OrderMeta::CUSTOMER_EMAIL_SNAPSHOT, $order->get_billing_email() );
		$order->update_meta_data( OrderMeta::PACKAGE_SNAPSHOT, wp_json_encode( $this->package_snapshot( $order ) ) );
		$order->update_meta_data( OrderMeta::DELIVERY_SLA_SNAPSHOT, wp_json_encode( $this->sla_snapshot() ) );

		$terms_version = (string) Settings::get( 'terms_version' );

		if ( '' !== $terms_version ) {
			$order->update_meta_data( OrderMeta::TERMS_VERSION, $terms_version );
			$order->update_meta_data( OrderMeta::TERMS_ACCEPTED_AT, gmdate( 'Y-m-d H:i:s' ) );
		}

		// Nothing is owed to staff until the gateway confirms the payment.
		if ( '' === (string) $order->get_meta( OrderMeta::REPORT_STATUS ) ) {
			$order->update_meta_data( OrderMeta::REPORT_STATUS, FulfillmentStatus::NOT_READY->value );
		}

		AuditLogger::log(
			$order->get_id(),
			'order.captured',
			array(
				'registration_present' => '' !== $entered,
				'items'                => count( $order->get_items() ),
			)
		);
	}

	/**
	 * What the customer is paying for, exactly as presented.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return array<string, mixed>
	 */
	private function package_snapshot( \WC_Order $order ): array {
		$items = array();

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$items[] = array(
				'product_id' => $item->get_product_id(),
				'name'       => $item->get_name(),
				'quantity'   => $item->get_quantity(),
				'subtotal'   => $item->get_subtotal(),
				'total'      => $item->get_total(),
			);
		}

		return array(
			'items'       => $items,
			'currency'    => $order->get_currency(),
			'total'       => $order->get_total(),
			'total_tax'   => $order->get_total_tax(),
			'captured_at' => gmdate( 'c' ),
		);
	}

	/**
	 * The delivery promise in force when the order was placed.
	 *
	 * @return array<string, mixed>
	 */
	private function sla_snapshot(): array {
		return array(
			'hours'          => Settings::sla_hours(),
			'business_hours' => Settings::business_hours_label(),
			'timezone'       => Settings::timezone()->getName(),
		);
	}
}
