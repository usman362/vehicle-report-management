<?php
/**
 * Report details inside WooCommerce's own order emails.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Notifications;

use VehicleReportOrders\Domain\OrderMeta;
use VehicleReportOrders\Domain\OrderRepository;
use VehicleReportOrders\Domain\PackageRepository;
use VehicleReportOrders\Domain\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the vehicle and the delivery promise to the order confirmation.
 *
 * WooCommerce already sends a receipt with the totals and tax; sending a second
 * message would just teach customers to ignore one of them. Instead the
 * promise is written into the email they already receive, and it says plainly
 * that the report itself arrives separately.
 */
final class OrderEmailAdditions {

	/**
	 * Hook the WooCommerce email templates.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_email_order_meta', array( $this, 'render' ), 10, 3 );
	}

	/**
	 * Print the report block.
	 *
	 * @param \WC_Order $order         Order.
	 * @param bool      $sent_to_admin Whether this copy goes to staff.
	 * @param bool      $plain_text    Whether the email is plain text.
	 *
	 * @return void
	 */
	public function render( $order, $sent_to_admin = false, $plain_text = false ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$registration = OrderRepository::registration_of( $order );
		$vin          = (string) $order->get_meta( OrderMeta::ORIGINAL_VIN );

		if ( null === $registration && '' === $vin && '' === (string) $order->get_meta( OrderMeta::REPORT_STATUS ) ) {
			return; // Not one of ours.
		}

		$hours = Settings::sla_hours();

		if ( null !== $registration ) {
			$label = __( 'Registration', 'vehicle-report-orders' );
			$mark  = $registration->for_display();
		} elseif ( '' !== $vin ) {
			$label = __( 'VIN', 'vehicle-report-orders' );
			$mark  = $vin;
		} else {
			$label = __( 'Vehicle', 'vehicle-report-orders' );
			$mark  = __( 'not supplied — we will ask you', 'vehicle-report-orders' );
		}

		$snapshot = PackageRepository::snapshot_for( $order );
		$package  = is_array( $snapshot ) ? (string) ( $snapshot['name'] ?? '' ) : '';

		$promise = null !== $hours
			? sprintf(
				/* translators: 1: business hours, 2: opening hours description. */
				__( 'Your report is prepared by hand and emailed within %1$d business hours (%2$s).', 'vehicle-report-orders' ),
				$hours,
				Settings::business_hours_label()
			)
			: __( 'Your report is prepared by hand and emailed as soon as it is ready.', 'vehicle-report-orders' );

		$separate = __( 'This message confirms your payment. The report itself arrives in a separate email with a private download link.', 'vehicle-report-orders' );

		if ( $plain_text ) {
			printf(
				"\n%s\n%s: %s\n%s%s\n%s\n",
				esc_html__( 'Vehicle report', 'vehicle-report-orders' ),
				esc_html( $label ),
				esc_html( $mark ),
				'' !== $package ? esc_html__( 'Package', 'vehicle-report-orders' ) . ': ' . esc_html( $package ) . "\n" : '',
				esc_html( $promise ),
				esc_html( $sent_to_admin ? '' : $separate )
			);

			return;
		}

		printf(
			'<h2 style="margin-top:24px;">%s</h2><p><strong>%s:</strong> %s</p>%s<p>%s</p>%s',
			esc_html__( 'Vehicle report', 'vehicle-report-orders' ),
			esc_html( $label ),
			esc_html( $mark ),
			'' !== $package
				? '<p><strong>' . esc_html__( 'Package', 'vehicle-report-orders' ) . ':</strong> ' . esc_html( $package ) . '</p>'
				: '',
			esc_html( $promise ),
			$sent_to_admin ? '' : '<p style="color:#5b6b7b;font-size:13px;">' . esc_html( $separate ) . '</p>'
		);
	}
}
