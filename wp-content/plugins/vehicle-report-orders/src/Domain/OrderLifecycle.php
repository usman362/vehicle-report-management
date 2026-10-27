<?php
/**
 * Moves a paid order into the fulfilment queue.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Domain;

use DateTimeImmutable;
use VehicleReportOrders\Audit\AuditLogger;
use VehicleReportOrders\Reports\ReportService;

defined( 'ABSPATH' ) || exit;

/**
 * Turns a verified payment into work for staff.
 *
 * Payment is taken from WooCommerce's own record of the gateway result, never
 * from a thank-you page visit, and the transition runs once: a duplicate
 * webhook, a browser refresh or a manual status change cannot queue the same
 * order twice or restart its delivery clock.
 */
final class OrderLifecycle {

	/**
	 * Report storage and delivery.
	 *
	 * @var ReportService
	 */
	private ReportService $reports;

	/**
	 * Constructor.
	 *
	 * @param ReportService $reports Report storage and delivery.
	 */
	public function __construct( ReportService $reports ) {
		$this->reports = $reports;
	}

	/**
	 * Hook the payment events.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_payment_complete', array( $this, 'on_payment_complete' ), 20, 1 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'on_payment_complete' ), 20, 1 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'on_payment_complete' ), 20, 1 );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'on_order_cancelled' ), 20, 1 );
		add_action( 'woocommerce_order_status_refunded', array( $this, 'on_order_refunded' ), 20, 1 );
	}

	/**
	 * Queue the report once payment is verified.
	 *
	 * @param int $order_id Order identifier.
	 *
	 * @return void
	 */
	public function on_payment_complete( $order_id ): void {
		$order = wc_get_order( (int) $order_id );

		if ( ! $order instanceof \WC_Order || ! $order->is_paid() ) {
			return;
		}

		$current = OrderRepository::status_of( $order );

		// Already queued or further along: a repeated notification changes nothing.
		if ( FulfillmentStatus::NOT_READY !== $current ) {
			AuditLogger::log( $order->get_id(), 'payment.duplicate_notification', array( 'status' => $current->value ) );

			return;
		}

		// A registration or a VIN both name the vehicle; neither means staff
		// have nothing to research and must go back to the customer.
		$reference = OrderRepository::vehicle_reference_of( $order );
		$target    = '' === $reference ? FulfillmentStatus::NEEDS_CUSTOMER_INFO : FulfillmentStatus::AWAITING_STAFF;

		// needs_customer_info is only reachable from awaiting_staff, so queue first.
		$order->update_meta_data( OrderMeta::REPORT_STATUS, FulfillmentStatus::AWAITING_STAFF->value );
		$order->update_meta_data( OrderMeta::PAYMENT_VERIFIED_AT, gmdate( 'Y-m-d H:i:s' ) );

		$due = $this->due_at( $order );

		if ( null !== $due ) {
			$order->update_meta_data( OrderMeta::SLA_DUE_AT, $due );
		}

		if ( FulfillmentStatus::NEEDS_CUSTOMER_INFO === $target ) {
			$order->update_meta_data( OrderMeta::REPORT_STATUS, $target->value );
		}

		$order->save();

		AuditLogger::log(
			$order->get_id(),
			'payment.verified',
			array(
				'gateway'  => $order->get_payment_method(),
				'total'    => $order->get_total(),
				'currency' => $order->get_currency(),
				'due_at'   => $due,
			),
			FulfillmentStatus::NOT_READY->value,
			$target->value
		);
	}

	/**
	 * A cancelled order stops needing a report.
	 *
	 * @param int $order_id Order identifier.
	 *
	 * @return void
	 */
	public function on_order_cancelled( $order_id ): void {
		$order = wc_get_order( (int) $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$current = OrderRepository::status_of( $order );

		if ( ! $current->can_move_to( FulfillmentStatus::CANCELLED ) ) {
			AuditLogger::log( $order->get_id(), 'order.cancel_ignored', array( 'status' => $current->value ) );

			return;
		}

		$order->update_meta_data( OrderMeta::REPORT_STATUS, FulfillmentStatus::CANCELLED->value );
		$order->save();

		AuditLogger::log( $order->get_id(), 'order.cancelled', array(), $current->value, FulfillmentStatus::CANCELLED->value );
	}

	/**
	 * A refund is recorded but never rewrites fulfilment history.
	 *
	 * Whether access to an already delivered report is withdrawn is a merchant
	 * policy decision, so the state is left alone and the event is logged.
	 *
	 * @param int $order_id Order identifier.
	 *
	 * @return void
	 */
	public function on_order_refunded( $order_id ): void {
		$order = wc_get_order( (int) $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$fully = OrderRepository::is_fully_refunded( $order );

		AuditLogger::log(
			$order->get_id(),
			'order.refunded',
			array(
				'status'         => OrderRepository::status_of( $order )->value,
				'refunded_total' => $order->get_total_refunded(),
				'full_refund'    => $fully,
			)
		);

		if ( ! $fully ) {
			return;
		}

		// Money back means access back. The fulfilment state is left alone: a
		// report that was delivered was delivered, and rewriting that would be
		// rewriting history. What changes is that the links stop working.
		$this->reports->revoke_links( $order );

		AuditLogger::log( $order->get_id(), 'report.access_revoked', array( 'reason' => 'full_refund' ) );
	}

	/**
	 * Deadline for this order, in UTC, or null when no window was promised.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return string|null
	 */
	private function due_at( \WC_Order $order ): ?string {
		$hours = Settings::sla_hours();

		if ( null === $hours ) {
			return null;
		}

		$paid = $order->get_date_paid();
		$from = $paid ? new DateTimeImmutable( '@' . $paid->getTimestamp() ) : new DateTimeImmutable( 'now' );

		$deadline = Settings::business_hours()->deadline_after( $from, (float) $hours );

		return $deadline?->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
	}
}
