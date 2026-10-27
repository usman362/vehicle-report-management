<?php
/**
 * T26: two customers ordering the same vehicle stay completely apart.
 *
 * @package VehicleReportOrders
 */

use VehicleReportOrders\Domain\FulfillmentStatus;
use VehicleReportOrders\Domain\OrderMeta;
use VehicleReportOrders\Reports\DownloadToken;
use VehicleReportOrders\Reports\ReportService;
use VehicleReportOrders\Reports\ReportVersionRepository;

vr_it_case( 'Two orders for the same registration stay apart (T26)' );

$vr_reports = new ReportService( new ReportVersionRepository() );

/**
 * A delivered order with its own report.
 *
 * @param string          $email   Billing email address.
 * @param ReportService   $reports Report service.
 *
 * @return WC_Order
 */
$vr_make = static function ( string $email, ReportService $reports ): WC_Order {
	$order = vr_it_order( 'SA66 MEV', $email );
	vr_it_report( $order );
	$order->update_meta_data( OrderMeta::REPORT_STATUS, FulfillmentStatus::REPORT_UPLOADED->value );
	$order->save();
	$reports->mark_delivered( $order );

	return wc_get_order( $order->get_id() );
};

$vr_first  = $vr_make( 'first.buyer@example.test', $vr_reports );
$vr_second = $vr_make( 'second.buyer@example.test', $vr_reports );

vr_it_ok( 'the two orders are separate records', $vr_first->get_id() !== $vr_second->get_id() );

$vr_first_secret  = $vr_reports->order_secret( $vr_first );
$vr_second_secret = $vr_reports->order_secret( $vr_second );

vr_it_ok( 'each order has its own download secret', '' !== $vr_first_secret && $vr_first_secret !== $vr_second_secret );

$vr_first_link  = $vr_reports->download_url( $vr_first );
$vr_second_link = $vr_reports->download_url( $vr_second );

vr_it_ok( 'each order has its own link', '' !== $vr_first_link && $vr_first_link !== $vr_second_link );

/**
 * The token part of a download URL.
 *
 * @param string $url Download URL.
 *
 * @return string
 */
$vr_token_of = static function ( string $url ): string {
	$query = array();
	parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );

	return isset( $query['vr-report'] ) ? (string) $query['vr-report'] : '';
};

$vr_first_token  = $vr_token_of( $vr_first_link );
$vr_second_token = $vr_token_of( $vr_second_link );

vr_it_is(
	"the first customer's token opens the first order",
	'ok',
	DownloadToken::verify( $vr_first_token, $vr_first_secret, time() )['reason']
);

vr_it_is(
	"the first customer's token does not open the second order",
	'signature',
	DownloadToken::verify( $vr_first_token, $vr_second_secret, time() )['reason']
);

vr_it_is(
	"the second customer's token does not open the first order",
	'signature',
	DownloadToken::verify( $vr_second_token, $vr_first_secret, time() )['reason']
);

// Revoking one customer's access leaves the other untouched.
$vr_reports->revoke_links( $vr_first );
$vr_first = wc_get_order( $vr_first->get_id() );

vr_it_is(
	'revoking the first link kills the first token',
	'signature',
	DownloadToken::verify( $vr_first_token, $vr_reports->order_secret( $vr_first ), time() )['reason']
);

vr_it_is(
	"revoking the first link leaves the second customer's token working",
	'ok',
	DownloadToken::verify( $vr_second_token, $vr_reports->order_secret( wc_get_order( $vr_second->get_id() ) ), time() )['reason']
);

// The stored files are separate too.
$vr_versions   = new ReportVersionRepository();
$vr_first_key  = (string) $vr_versions->current_for( $vr_first->get_id() )->storage_key;
$vr_second_key = (string) $vr_versions->current_for( $vr_second->get_id() )->storage_key;

vr_it_ok( 'the two reports are separate files', '' !== $vr_first_key && $vr_first_key !== $vr_second_key );
vr_it_ok( "a version lookup will not return another order's report", null === $vr_versions->find_for_order( (int) $vr_versions->current_for( $vr_second->get_id() )->id, $vr_first->get_id() ) );
