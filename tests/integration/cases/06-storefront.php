<?php
/**
 * The storefront shortcodes say what the merchant configured, and nothing else.
 *
 * @package VehicleReportOrders
 */

use VehicleReportOrders\Domain\Settings;

vr_it_case( 'The storefront prints settings, not assumptions (T29)' );

$vr_before = Settings::all();

// A heading asked for is a heading rendered. This went missing for a while
// because the shortcode attribute never reached the template.
$vr_form = do_shortcode( '[vehicle_report_order_form heading="Order a vehicle report"]' );

vr_it_ok( 'the order form renders', str_contains( $vr_form, 'vr-order-form' ) );
vr_it_ok( 'a requested heading is shown', str_contains( $vr_form, '<h2>Order a vehicle report</h2>' ) );

$vr_plain = do_shortcode( '[vehicle_report_order_form]' );
vr_it_ok( 'no heading is invented when none is asked for', ! str_contains( $vr_plain, '<h2>' ) );

// With nothing configured, every disclosure says so rather than guessing.
Settings::update(
	array(
		'sla_hours'     => 0,
		'business_days' => array(),
		'data_sources'  => '',
		'trader_name'   => '',
		'support_email' => '',
	)
);

foreach ( array(
	'vehicle_report_sla'           => 'delivery time to be confirmed',
	'vehicle_report_sources'       => 'data sources for this service have not been published',
	'vehicle_report_trader'        => 'trading name to be confirmed',
	'vehicle_report_support_email' => 'support address to be confirmed',
	'vehicle_report_hours'         => 'opening hours to be confirmed',
	'vehicle_report_sample'        => 'example report has not been published',
) as $vr_shortcode => $vr_expected ) {
	$vr_output = do_shortcode( "[{$vr_shortcode}]" );

	vr_it_ok(
		"[{$vr_shortcode}] admits it has nothing to say yet",
		str_contains( $vr_output, $vr_expected ) && str_contains( $vr_output, 'vr-pending' ),
		wp_strip_all_tags( $vr_output )
	);
}

$vr_blocked = do_shortcode( '[vehicle_report_order_form]' );
vr_it_ok( 'and ordering is switched off while they are missing', str_contains( $vr_blocked, 'Ordering is switched off' ) );

// A legal page says it is a draft until the operator confirms otherwise.
Settings::update( array( 'legal_copy_approved' => false ) );
vr_it_ok( 'legal pages carry the draft notice', str_contains( do_shortcode( '[vehicle_report_legal_draft]' ), 'Draft wording, not yet in force' ) );

Settings::update( array( 'legal_copy_approved' => true ) );
vr_it_is( 'and drop it once the wording is signed off', '', do_shortcode( '[vehicle_report_legal_draft]' ) );

Settings::update( $vr_before );
