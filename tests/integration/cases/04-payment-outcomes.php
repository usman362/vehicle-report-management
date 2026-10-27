<?php
/**
 * T08, T16 and T28: what the site does when the money does not arrive,
 * when the provider cannot be reached, and when the money goes back.
 *
 * @package VehicleReportOrders
 */

use VehicleReportOrders\Audit\AuditLogger;
use VehicleReportOrders\Domain\FulfillmentStatus;
use VehicleReportOrders\Domain\OrderLifecycle;
use VehicleReportOrders\Domain\OrderMeta;
use VehicleReportOrders\Domain\OrderRepository;
use VehicleReportOrders\Domain\Settings;
use VehicleReportOrders\Reports\DownloadToken;
use VehicleReportOrders\Reports\ReportService;
use VehicleReportOrders\Reports\ReportVersionRepository;

vr_it_case( 'A payment that fails never becomes work (T08, T28)' );

global $wpdb;

$vr_reports   = new ReportService( new ReportVersionRepository() );
$vr_lifecycle = new OrderLifecycle( $vr_reports );
$vr_queue     = new OrderRepository();

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

foreach ( array(
	'declined'    => 'The payment was declined.',
	'unreachable' => 'The provider could not be reached.',
) as $vr_case => $vr_note ) {
	$vr_order = vr_it_order( 'VR08 PAY' );
	$vr_order->set_status( 'failed', $vr_note );
	$vr_order->update_meta_data( OrderMeta::REPORT_STATUS, FulfillmentStatus::NOT_READY->value );
	$vr_order->delete_meta_data( OrderMeta::SLA_DUE_AT );
	$vr_order->save();

	$vr_fresh = wc_get_order( $vr_order->get_id() );

	vr_it_ok( "{$vr_case}: the order is not treated as paid", ! $vr_fresh->is_paid() );
	vr_it_is( "{$vr_case}: no delivery deadline is set", '', (string) $vr_fresh->get_meta( OrderMeta::SLA_DUE_AT ) );
	vr_it_ok( "{$vr_case}: the order is not waiting for staff", FulfillmentStatus::AWAITING_STAFF !== OrderRepository::status_of( $vr_fresh ) );

	$vr_in_queue = array_filter(
		$vr_queue->search( array( 'status' => FulfillmentStatus::AWAITING_STAFF->value ) )['orders'] ?? array(),
		static fn( $candidate ): bool => $candidate->get_id() === $vr_fresh->get_id()
	);

	vr_it_is( "{$vr_case}: the order is not in the fulfilment queue", 0, count( $vr_in_queue ) );
	vr_it_is( "{$vr_case}: no work was queued in the audit trail", 0, $vr_audited( $vr_fresh->get_id(), 'payment.verified' ) );

	// Even a payment notification arriving for this order changes nothing,
	// because the order was never actually paid for.
	$vr_lifecycle->on_payment_complete( $vr_fresh->get_id() );

	vr_it_is(
		"{$vr_case}: a payment notification for an unpaid order is ignored",
		FulfillmentStatus::NOT_READY,
		OrderRepository::status_of( wc_get_order( $vr_fresh->get_id() ) )
	);
}

// A failed order that is later paid for properly does become work: a decline
// must not poison the order for good.
//
// The deadline only exists if the merchant has said what it is, so the case
// supplies that itself rather than depending on whatever this site happens to
// be configured with.
$vr_sla_before   = Settings::get( 'sla_hours' );
$vr_days_before  = Settings::get( 'business_days' );
$vr_open_before  = Settings::get( 'business_open' );
$vr_close_before = Settings::get( 'business_close' );

Settings::update(
	array(
		'sla_hours'      => 8,
		'business_days'  => array( 1, 2, 3, 4, 5 ),
		'business_open'  => '09:00',
		'business_close' => '17:00',
	)
);

$vr_recovered = vr_it_order( 'VR08 RTY' );
$vr_recovered->set_status( 'failed' );
$vr_recovered->update_meta_data( OrderMeta::REPORT_STATUS, FulfillmentStatus::NOT_READY->value );
$vr_recovered->save();

// Paid for properly this time, through WooCommerce's own hooks.
$vr_recovered->payment_complete( 'vr-dev-retry' );
$vr_recovered = wc_get_order( $vr_recovered->get_id() );

vr_it_is( 'a retried payment puts the order in the queue', FulfillmentStatus::AWAITING_STAFF, OrderRepository::status_of( $vr_recovered ) );
vr_it_ok( 'a retried payment sets the delivery deadline', '' !== (string) $vr_recovered->get_meta( OrderMeta::SLA_DUE_AT ) );

Settings::update(
	array(
		'sla_hours'      => $vr_sla_before,
		'business_days'  => $vr_days_before,
		'business_open'  => $vr_open_before,
		'business_close' => $vr_close_before,
	)
);

vr_it_case( 'Money back means access back (T16)' );

$vr_order = vr_it_order( 'VR16 REF' );
$vr_order->set_total( '24.99' );
vr_it_report( $vr_order );
$vr_order->update_meta_data( OrderMeta::REPORT_STATUS, FulfillmentStatus::REPORT_UPLOADED->value );
$vr_order->save();
$vr_reports->mark_delivered( $vr_order );
$vr_order = wc_get_order( $vr_order->get_id() );

$vr_link   = $vr_reports->download_url( $vr_order );
$vr_secret = $vr_reports->order_secret( $vr_order );
$vr_query  = array();
parse_str( (string) wp_parse_url( $vr_link, PHP_URL_QUERY ), $vr_query );
$vr_token = isset( $vr_query['vr-report'] ) ? (string) $vr_query['vr-report'] : '';

vr_it_is( 'the delivered report opens before the refund', 'ok', DownloadToken::verify( $vr_token, $vr_secret, time() )['reason'] );

// A partial refund is a goodwill gesture, not an undoing of the sale.
wc_create_refund(
	array(
		'order_id' => $vr_order->get_id(),
		'amount'   => 5.00,
		'reason'   => 'Integration test: partial goodwill refund',
	)
);

$vr_order = wc_get_order( $vr_order->get_id() );

vr_it_ok( 'a partial refund does not count as a full one', ! OrderRepository::is_fully_refunded( $vr_order ) );
vr_it_is(
	'a partial refund leaves the report reachable',
	'ok',
	DownloadToken::verify( $vr_token, $vr_reports->order_secret( $vr_order ), time() )['reason']
);

// The rest of the money goes back.
wc_create_refund(
	array(
		'order_id' => $vr_order->get_id(),
		'amount'   => 19.99,
		'reason'   => 'Integration test: remainder refunded',
	)
);

$vr_order = wc_get_order( $vr_order->get_id() );

vr_it_ok( 'the order is now fully refunded', OrderRepository::is_fully_refunded( $vr_order ) );
vr_it_is(
	'the link the customer already had stops working',
	'signature',
	DownloadToken::verify( $vr_token, $vr_reports->order_secret( $vr_order ), time() )['reason']
);
vr_it_is( 'the revocation is on the record', 1, $vr_audited( $vr_order->get_id(), 'report.access_revoked' ) );
vr_it_is(
	'the delivery itself is not rewritten out of history',
	FulfillmentStatus::DELIVERED,
	OrderRepository::status_of( $vr_order )
);
