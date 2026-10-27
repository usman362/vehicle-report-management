<?php
/**
 * T14: a delivery that fails is retried, logged, and eventually escalated.
 *
 * @package VehicleReportOrders
 */

use VehicleReportOrders\Audit\AuditLogger;
use VehicleReportOrders\Domain\FulfillmentStatus;
use VehicleReportOrders\Domain\OrderMeta;
use VehicleReportOrders\Domain\Settings;
use VehicleReportOrders\Notifications\EmailNotifier;
use VehicleReportOrders\Reports\ReportService;
use VehicleReportOrders\Reports\ReportVersionRepository;
use VehicleReportOrders\Schema;

vr_it_case( 'A failed delivery is retried, recorded and escalated (T14)' );

global $wpdb;

$vr_reports  = new ReportService( new ReportVersionRepository() );
$vr_notifier = new EmailNotifier( $vr_reports );
$vr_order    = vr_it_order( 'VR14 RTY' );
vr_it_report( $vr_order );

// A report that has only been uploaded is not a report that has been delivered.
$vr_too_early = $vr_reports->mark_delivered( $vr_order );
vr_it_ok( 'an order awaiting staff cannot jump straight to delivered', is_wp_error( $vr_too_early ) && 'vr_bad_transition' === $vr_too_early->get_error_code() );

$vr_order->update_meta_data( OrderMeta::REPORT_STATUS, FulfillmentStatus::REPORT_UPLOADED->value );
$vr_order->save();

vr_it_ok( 'a report can be delivered once it has been uploaded', true === $vr_reports->mark_delivered( $vr_order ) );
$vr_order = wc_get_order( $vr_order->get_id() );

$vr_settings_before = Settings::get( 'max_delivery_tries' );
Settings::update( array( 'max_delivery_tries' => 3 ) );

/**
 * Count delivery attempts of one kind.
 *
 * @param int    $order_id Order identifier.
 * @param string $status   sent or failed.
 *
 * @return int
 */
$vr_attempts = static function ( int $order_id, string $status ) use ( $wpdb ): int {
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			'SELECT COUNT(*) FROM %i WHERE order_id = %d AND status = %s',
			Schema::table( Schema::DELIVERY_ATTEMPTS ),
			$order_id,
			$status
		)
	);
};

/**
 * Count audit entries of one kind.
 *
 * @param int    $order_id Order identifier.
 * @param string $action   Audit action.
 *
 * @return int
 */
$vr_audited = static function ( int $order_id, string $action ) use ( $wpdb ): int {
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			'SELECT COUNT(*) FROM %i WHERE order_id = %d AND action = %s',
			AuditLogger::table(),
			$order_id,
			$action
		)
	);
};

// Make the mailer reject everything.
$vr_reject = static fn() => false;
add_filter( 'pre_wp_mail', $vr_reject, 999 );

$vr_sent = $vr_notifier->send_report_ready( $vr_order );
vr_it_ok( 'a rejected message is reported as not sent', false === $vr_sent );
vr_it_is( 'the failure is written to the delivery log', 1, $vr_attempts( $vr_order->get_id(), 'failed' ) );
vr_it_is( 'a retry is scheduled', 1, $vr_audited( $vr_order->get_id(), 'email.delivery_retry_scheduled' ) );

$vr_order = wc_get_order( $vr_order->get_id() );
vr_it_is( 'the attempt count is on the order', 1, (int) $vr_order->get_meta( OrderMeta::DELIVERY_ATTEMPT_COUNT ) );
vr_it_is( 'the reason is on the order', 'mailer_rejected', (string) $vr_order->get_meta( OrderMeta::LAST_DELIVERY_ERROR ) );

$vr_queued = function_exists( 'as_next_scheduled_action' )
	? as_next_scheduled_action( 'vr_retry_report_delivery', array( $vr_order->get_id() ), 'vehicle-report-orders' )
	: wp_next_scheduled( 'vr_retry_report_delivery', array( $vr_order->get_id() ) );

vr_it_ok( 'the retry is really on a queue, not only in the log', false !== $vr_queued && null !== $vr_queued );

// Burn through the remaining attempts.
$vr_notifier->retry_delivery( $vr_order->get_id() );
$vr_notifier->retry_delivery( $vr_order->get_id() );

vr_it_is( 'every attempt is logged', 3, $vr_attempts( $vr_order->get_id(), 'failed' ) );
vr_it_is( 'the service gives up once, not repeatedly', 1, $vr_audited( $vr_order->get_id(), 'email.delivery_gave_up' ) );

$vr_order = wc_get_order( $vr_order->get_id() );
vr_it_is( 'the order is flagged as failed delivery', FulfillmentStatus::DELIVERY_FAILED->value, (string) $vr_order->get_meta( OrderMeta::REPORT_STATUS ) );
vr_it_ok( 'a failed order no longer grants the customer access', ! FulfillmentStatus::from_stored( (string) $vr_order->get_meta( OrderMeta::REPORT_STATUS ) )->grants_customer_access() );

// A scheduled retry that fires after the service gave up does nothing.
$vr_notifier->retry_delivery( $vr_order->get_id() );
vr_it_is( 'a late retry does not attempt delivery again', 3, $vr_attempts( $vr_order->get_id(), 'failed' ) );

// With the mailer working again, a staff resend goes through.
remove_filter( 'pre_wp_mail', $vr_reject, 999 );

$vr_order->update_meta_data( OrderMeta::REPORT_STATUS, FulfillmentStatus::DELIVERED->value );
$vr_order->save();

vr_it_ok( 'the report sends once the mailer recovers', true === $vr_notifier->send_report_ready( wc_get_order( $vr_order->get_id() ) ) );
vr_it_is( 'the successful delivery is recorded', 1, $vr_attempts( $vr_order->get_id(), 'sent' ) );

Settings::update( array( 'max_delivery_tries' => $vr_settings_before ) );
