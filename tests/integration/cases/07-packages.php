<?php
/**
 * Packages promise checks, and an order records which tier was bought.
 *
 * @package VehicleReportOrders
 */

use VehicleReportOrders\Domain\FulfillmentStatus;
use VehicleReportOrders\Domain\OrderLifecycle;
use VehicleReportOrders\Domain\OrderMeta;
use VehicleReportOrders\Domain\OrderRepository;
use VehicleReportOrders\Domain\Package;
use VehicleReportOrders\Domain\PackageRepository;
use VehicleReportOrders\Domain\Settings;
use VehicleReportOrders\Domain\VehicleCheck;
use VehicleReportOrders\Domain\VehicleType;
use VehicleReportOrders\Reports\ReportService;
use VehicleReportOrders\Reports\ReportVersionRepository;

vr_it_case( 'Packages, VINs and vehicle types' );

$vr_packages = PackageRepository::all();

vr_it_ok( 'the merchant has packages to sell', count( $vr_packages ) > 0, count( $vr_packages ) . ' configured' );

$vr_slugs = array_map( static fn( Package $p ): string => $p->slug(), $vr_packages );
vr_it_ok( 'each package has its own slug', count( $vr_slugs ) === count( array_unique( $vr_slugs ) ) );

foreach ( $vr_packages as $vr_package ) {
	vr_it_ok(
		"the {$vr_package->slug()} package only promises checks that exist",
		array() === array_diff( $vr_package->checks(), VehicleCheck::keys() ),
		implode( ', ', $vr_package->checks() )
	);
}

// A package with no price cannot be bought, however it is reached.
$vr_unpriced = new Package( 'integration-unpriced', 'Unpriced', 0, array( 'keepers' ) );
vr_it_ok( 'a package without a price is not purchasable', ! $vr_unpriced->is_purchasable() );

// The tier is recorded on the order, so later edits cannot rewrite history.
$vr_order = vr_it_order( 'PK19 TST' );
PackageRepository::remember( $vr_order, new Package( 'silver', 'Silver', 0, array( 'keepers', 'finance' ) ) );
$vr_order->save();

$vr_snapshot = PackageRepository::snapshot_for( $vr_order );

vr_it_is( 'the order remembers which tier was bought', 'Silver', (string) ( $vr_snapshot['name'] ?? '' ) );
vr_it_is( 'and exactly which checks it promised', 'keepers, finance', implode( ', ', (array) ( $vr_snapshot['checks'] ?? array() ) ) );

// Renaming the live tier must not change what this customer was promised.
$vr_settings_before = Settings::get( 'packages' );
Settings::update(
	array(
		'packages' => array(
			array(
				'slug'       => 'silver',
				'name'       => 'Silver (renamed after the sale)',
				'product_id' => 0,
				'checks'     => array( 'keepers' ),
			),
		),
	)
);

$vr_after = PackageRepository::snapshot_for( $vr_order );
vr_it_is( 'renaming the tier later does not rewrite the order', 'Silver', (string) ( $vr_after['name'] ?? '' ) );

Settings::update( array( 'packages' => is_array( $vr_settings_before ) ? $vr_settings_before : array() ) );

// A VIN names the vehicle just as a registration does.
$vr_vin_order = wc_create_order();
$vr_vin_order->set_billing_email( 'integration@example.test' );
$vr_vin_order->update_meta_data( OrderMeta::ORIGINAL_VIN, 'WVWZZZ1KZAW123456' );
$vr_vin_order->update_meta_data( OrderMeta::NORMALIZED_VIN, 'WVWZZZ1KZAW123456' );
$vr_vin_order->update_meta_data( OrderMeta::REPORT_STATUS, FulfillmentStatus::NOT_READY->value );
$vr_vin_order->update_meta_data( '_vr_integration_fixture', '1' );
$vr_vin_order->set_status( 'processing' );
$vr_vin_order->set_date_paid( time() );
$vr_vin_order->save();

vr_it_is( 'the queue names a VIN-only order by its VIN', 'WVWZZZ1KZAW123456', OrderRepository::vehicle_reference_of( $vr_vin_order ) );

( new OrderLifecycle( new ReportService( new ReportVersionRepository() ) ) )->on_payment_complete( $vr_vin_order->get_id() );

$vr_reloaded = wc_get_order( $vr_vin_order->get_id() );

vr_it_is(
	'a paid VIN-only order goes to staff, not back to the customer',
	FulfillmentStatus::AWAITING_STAFF->value,
	OrderRepository::status_of( $vr_reloaded )->value
);

// An order naming no vehicle at all still has to go back to the customer.
$vr_blank = wc_create_order();
$vr_blank->set_billing_email( 'integration@example.test' );
$vr_blank->update_meta_data( OrderMeta::REPORT_STATUS, FulfillmentStatus::NOT_READY->value );
$vr_blank->update_meta_data( '_vr_integration_fixture', '1' );
$vr_blank->set_status( 'processing' );
$vr_blank->set_date_paid( time() );
$vr_blank->save();

( new OrderLifecycle( new ReportService( new ReportVersionRepository() ) ) )->on_payment_complete( $vr_blank->get_id() );

vr_it_is(
	'an order naming no vehicle needs the customer',
	FulfillmentStatus::NEEDS_CUSTOMER_INFO->value,
	OrderRepository::status_of( wc_get_order( $vr_blank->get_id() ) )->value
);

// Vehicle types are the merchant's choice; nothing else may be ordered.
$vr_types_before = Settings::get( 'vehicle_types' );
Settings::update( array( 'vehicle_types' => array( 'car', 'nonsense' ) ) );

vr_it_is( 'only known vehicle types are offered', 'car', implode( ', ', array_keys( VehicleType::offered() ) ) );
vr_it_ok( 'an offered type is accepted', VehicleType::is_offered( 'car' ) );
vr_it_ok( 'a type the merchant does not offer is refused', ! VehicleType::is_offered( 'truck' ) );

Settings::update( array( 'vehicle_types' => is_array( $vr_types_before ) ? $vr_types_before : array() ) );

// The package comparison never invents a price.
$vr_cards = do_shortcode( '[vehicle_report_packages]' );
vr_it_ok( 'the package comparison renders', str_contains( $vr_cards, 'vr-package-grid' ) );
vr_it_ok(
	'and says plainly which checks are not included',
	str_contains( $vr_cards, 'vr-excluded' ),
	'a tier that lists only what it includes reads as if it includes everything'
);

// Checks are described with their limits wherever they are published.
$vr_checks = do_shortcode( '[vehicle_report_checks only="stolen"]' );
vr_it_ok( 'a check is published with its limits', str_contains( $vr_checks, 'What it does not tell you' ) );
vr_it_ok( 'and a stolen check does not claim to be a police check', str_contains( $vr_checks, 'not a police check' ) );

// Reviews are the merchant's real ones or nothing at all.
$vr_reviews_before = Settings::get( 'reviews' );
Settings::update( array( 'reviews' => array() ) );
vr_it_is( 'no reviews section appears before anyone has left one', '', do_shortcode( '[vehicle_report_reviews]' ) );

Settings::update( array( 'reviews' => array( array( 'quote' => 'It arrived when they said it would.', 'name' => 'A customer', 'date' => '2026-10-01' ) ) ) );
vr_it_ok( 'a real review is published as written', str_contains( do_shortcode( '[vehicle_report_reviews]' ), 'It arrived when they said it would.' ) );

Settings::update( array( 'reviews' => is_array( $vr_reviews_before ) ? $vr_reviews_before : array() ) );

// Support channels stay hidden until someone is answering them.
$vr_whatsapp_before = Settings::get( 'whatsapp_number' );
Settings::update( array( 'whatsapp_number' => '' ) );
vr_it_is( 'no WhatsApp link without a number', '', Settings::whatsapp_url() );

Settings::update( array( 'whatsapp_number' => '+44 7700 900000' ) );
vr_it_is( 'and a configured number becomes a link', 'https://wa.me/447700900000', Settings::whatsapp_url() );

Settings::update( array( 'whatsapp_number' => is_string( $vr_whatsapp_before ) ? $vr_whatsapp_before : '' ) );

// Report files must not live where a web server could serve them. ABSPATH is
// whatever the entry point made it — WP-CLI's `--path=.` leaves a `/./` in it
// — so the path above the root has to be resolved before it is trusted.
vr_it_ok(
	'private report storage sits outside the web root',
	VehicleReportOrders\Reports\PrivateStorage::is_outside_web_root(),
	VehicleReportOrders\Reports\PrivateStorage::base_path()
);

vr_it_ok(
	'and the resolved path is not inside the site directory',
	! str_starts_with(
		VehicleReportOrders\Reports\PrivateStorage::base_path(),
		trailingslashit( (string) realpath( untrailingslashit( ABSPATH ) ) )
	),
	VehicleReportOrders\Reports\PrivateStorage::base_path()
);
