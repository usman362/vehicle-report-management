<?php
/**
 * Customer and staff email.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Notifications;

use VehicleReportOrders\Audit\AuditLogger;
use VehicleReportOrders\Domain\FulfillmentStatus;
use VehicleReportOrders\Domain\OrderMeta;
use VehicleReportOrders\Domain\OrderRepository;
use VehicleReportOrders\Domain\Settings;
use VehicleReportOrders\Reports\Coverage;
use VehicleReportOrders\Reports\ReportService;
use VehicleReportOrders\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Sends the few messages this service owes a customer.
 *
 * Every attempt is recorded with its outcome, so a report that never arrived
 * is visible to staff instead of being assumed delivered. Messages carry a link
 * rather than the file: a mailbox is not a safe home for someone's vehicle and
 * payment history, and a link can expire.
 */
final class EmailNotifier {

	/**
	 * Report storage, for building links.
	 *
	 * @var ReportService
	 */
	private ReportService $reports;

	/**
	 * Constructor.
	 *
	 * @param ReportService $reports Report storage.
	 */
	public function __construct( ReportService $reports ) {
		$this->reports = $reports;
	}

	/**
	 * Hook the automatic messages.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'vr_retry_report_delivery', array( $this, 'retry_delivery' ), 10, 1 );
	}

	/**
	 * Tell the customer their report is ready.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return bool Whether the message was handed to the mailer.
	 */
	public function send_report_ready( \WC_Order $order ): bool {
		$link = $this->reports->download_url( $order );

		if ( '' === $link ) {
			$this->record_attempt( $order, 'failed', 'no_report', __( 'No report file to link to.', 'vehicle-report-orders' ) );

			return false;
		}

		$registration = OrderRepository::registration_of( $order );
		$ttl_hours    = max( 1, (int) Settings::get( 'download_ttl_hours' ) );

		$subject = sprintf(
			/* translators: %s: order number. */
			__( 'Your vehicle report is ready (order %s)', 'vehicle-report-orders' ),
			$order->get_order_number()
		);

		$version = $this->reports->versions()->current_for( $order->get_id() );

		$body = $this->render(
			'report-ready',
			array(
				'order'        => $order,
				'link'         => $link,
				'registration' => $registration ? $registration->for_display() : '',
				'ttl_hours'    => $ttl_hours,
				'coverage'     => is_object( $version ) ? Coverage::from_json( (string) ( $version->coverage_json ?? '' ) ) : null,
			)
		);

		$sent = $this->dispatch( $order, $order->get_billing_email(), $subject, $body );

		if ( $sent ) {
			$this->record_attempt( $order, 'sent', '', '' );
			AuditLogger::log( $order->get_id(), 'email.report_ready_sent' );

			return true;
		}

		$this->record_attempt( $order, 'failed', 'mailer_rejected', __( 'wp_mail() returned false.', 'vehicle-report-orders' ) );
		$this->schedule_retry( $order );

		return false;
	}

	/**
	 * Warn staff that a delivery failed.
	 *
	 * @param \WC_Order $order  Order.
	 * @param string    $reason Failure reason.
	 *
	 * @return bool
	 */
	public function send_delivery_failed_to_staff( \WC_Order $order, string $reason ): bool {
		$to = (string) Settings::get( 'support_email' );

		if ( ! is_email( $to ) ) {
			return false;
		}

		$subject = sprintf(
			/* translators: %s: order number. */
			__( 'Report delivery failed for order %s', 'vehicle-report-orders' ),
			$order->get_order_number()
		);

		$body = $this->render(
			'delivery-failed',
			array(
				'order'  => $order,
				'reason' => $reason,
			)
		);

		return $this->dispatch( $order, $to, $subject, $body );
	}

	/**
	 * Retry a failed delivery.
	 *
	 * @param int $order_id Order identifier.
	 *
	 * @return void
	 */
	public function retry_delivery( $order_id ): void {
		$order = wc_get_order( (int) $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		if ( ! OrderRepository::status_of( $order )->grants_customer_access() ) {
			return;
		}

		$this->send_report_ready( $order );
	}

	/**
	 * Queue another attempt, up to the configured maximum.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return void
	 */
	private function schedule_retry( \WC_Order $order ): void {
		$attempts = (int) $order->get_meta( OrderMeta::DELIVERY_ATTEMPT_COUNT );
		$maximum  = max( 1, (int) Settings::get( 'max_delivery_tries' ) );

		if ( $attempts >= $maximum ) {
			$order->update_meta_data( OrderMeta::REPORT_STATUS, FulfillmentStatus::DELIVERY_FAILED->value );
			$order->save();

			AuditLogger::log( $order->get_id(), 'email.delivery_gave_up', array( 'attempts' => $attempts ) );
			$this->send_delivery_failed_to_staff( $order, __( 'Maximum delivery attempts reached.', 'vehicle-report-orders' ) );

			return;
		}

		// Back off: 5 minutes, then 25, then 125 minutes.
		$delay = (int) min( DAY_IN_SECONDS, 300 * ( 5 ** max( 0, $attempts - 1 ) ) );

		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + $delay, 'vr_retry_report_delivery', array( $order->get_id() ), 'vehicle-report-orders' );
		} else {
			wp_schedule_single_event( time() + $delay, 'vr_retry_report_delivery', array( $order->get_id() ) );
		}

		AuditLogger::log(
			$order->get_id(),
			'email.delivery_retry_scheduled',
			array(
				'in_seconds' => $delay,
				'attempt'    => $attempts,
			)
		);
	}

	/**
	 * Hand a message to WordPress and note the outcome.
	 *
	 * @param \WC_Order $order   Order the message concerns.
	 * @param string    $to      Recipient.
	 * @param string    $subject Subject line.
	 * @param string    $body    HTML body.
	 *
	 * @return bool
	 */
	private function dispatch( \WC_Order $order, string $to, string $subject, string $body ): bool {
		if ( ! is_email( $to ) ) {
			return false;
		}

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		$from    = (string) Settings::get( 'support_email' );

		if ( is_email( $from ) ) {
			$trader = (string) Settings::get( 'trader_name' );
			$name   = '' !== $trader ? $trader : get_bloginfo( 'name' );

			$headers[] = sprintf( 'Reply-To: %s <%s>', $name, $from );
		}

		$sent = wp_mail( $to, $subject, $body, $headers );

		AuditLogger::log(
			$order->get_id(),
			'email.dispatched',
			array(
				'subject' => $subject,
				'sent'    => $sent,
			)
		);

		return (bool) $sent;
	}

	/**
	 * Record one delivery attempt.
	 *
	 * @param \WC_Order $order   Order.
	 * @param string    $status  sent or failed.
	 * @param string    $code    Failure code.
	 * @param string    $message Failure message.
	 *
	 * @return void
	 */
	private function record_attempt( \WC_Order $order, string $status, string $code, string $message ): void {
		global $wpdb;

		$attempt = (int) $order->get_meta( OrderMeta::DELIVERY_ATTEMPT_COUNT ) + 1;

		$order->update_meta_data( OrderMeta::DELIVERY_ATTEMPT_COUNT, $attempt );

		if ( '' !== $code ) {
			$order->update_meta_data( OrderMeta::LAST_DELIVERY_ERROR, $code );
		}

		$order->save();

		$version = $this->reports->versions()->current_for( $order->get_id() );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dedicated plugin table.
		$wpdb->insert(
			Schema::table( Schema::DELIVERY_ATTEMPTS ),
			array(
				'order_id'          => $order->get_id(),
				'report_version_id' => $version ? (int) $version->id : null,
				'channel'           => 'email',
				'status'            => $status,
				'recipient_hash'    => substr( hash_hmac( 'sha256', (string) $order->get_billing_email(), wp_salt( 'auth' ) ), 0, 32 ),
				'failure_code'      => '' !== $code ? $code : null,
				'failure_message'   => '' !== $message ? $message : null,
				'attempt_number'    => $attempt,
				'created_at'        => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);
	}

	/**
	 * Render an email template.
	 *
	 * @param string               $template Template name.
	 * @param array<string, mixed> $data     Template data.
	 *
	 * @return string
	 */
	private function render( string $template, array $data ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $data is in scope for the included template.
		$file = __DIR__ . '/../../templates/emails/' . $template . '.php';

		if ( ! is_readable( $file ) ) {
			return '';
		}

		ob_start();
		include $file;

		return (string) ob_get_clean();
	}
}
