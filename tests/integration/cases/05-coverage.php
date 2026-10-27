<?php
/**
 * A report cannot be stored without saying what each source could tell us.
 *
 * @package VehicleReportOrders
 */

use VehicleReportOrders\Admin\CapabilityManager;
use VehicleReportOrders\Admin\FulfillmentActions;
use VehicleReportOrders\Domain\CoverageOutcome;
use VehicleReportOrders\Domain\FulfillmentStatus;
use VehicleReportOrders\Domain\OrderMeta;
use VehicleReportOrders\Domain\OrderRepository;
use VehicleReportOrders\Domain\Settings;
use VehicleReportOrders\Notifications\EmailNotifier;
use VehicleReportOrders\Reports\Coverage;
use VehicleReportOrders\Reports\ReportService;
use VehicleReportOrders\Reports\ReportVersionRepository;

vr_it_case( 'A report says, source by source, what could be checked' );

$vr_sources_before = Settings::get( 'data_sources' );

Settings::update( array( 'data_sources' => "DVSA MOT history\nDVLA vehicle enquiry\nOutstanding finance" ) );

$vr_versions = new ReportVersionRepository();
$vr_reports  = new ReportService( $vr_versions );
$vr_notifier = new EmailNotifier( $vr_reports );
$vr_actions  = new FulfillmentActions( $vr_reports, $vr_notifier );

$vr_staff = wp_insert_user(
	array(
		'user_login' => 'vr-integration-coverage-' . wp_generate_password( 6, false ),
		'user_pass'  => wp_generate_password( 20 ),
		'user_email' => 'vr-integration-' . wp_generate_password( 8, false ) . '@example.test',
		'role'       => CapabilityManager::ROLE_FULFILMENT,
	)
);

wp_set_current_user( (int) $vr_staff );

$vr_order = vr_it_order( 'VR20 COV' );

/**
 * A PDF on disk, presented the way an upload arrives.
 *
 * @return array<string, mixed>
 */
$vr_file = static function (): array {
	$tmp = wp_tempnam( 'vr-coverage' );
	file_put_contents( $tmp, "%PDF-1.4\ncoverage fixture\n%%EOF\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

	return array(
		'name'     => 'coverage.pdf',
		'tmp_name' => $tmp,
		'size'     => (int) filesize( $tmp ),
		'error'    => UPLOAD_ERR_OK,
	);
};

// Nothing said about any source.
$vr_result = $vr_reports->store_upload( $vr_order, $vr_file(), 'DVSA MOT history', '', null );
vr_it_ok( 'a report with no coverage recorded can still be stored by code that predates it', ! is_wp_error( $vr_result ) );

// The action, which is what staff actually use, refuses an unanswered source.
$_POST = array(
	'source_summary'   => 'DVSA MOT history',
	'coverage_outcome' => array( 'found', 'none' ),
	'coverage_reason'  => array( '', '' ),
);

$vr_refused = $vr_actions->run( $vr_order->get_id(), 'upload' );
vr_it_ok(
	'staff cannot store a report that leaves a source unanswered',
	is_wp_error( $vr_refused ) && 'vr_coverage_incomplete' === $vr_refused->get_error_code(),
	is_wp_error( $vr_refused ) ? $vr_refused->get_error_code() : 'no error'
);

// "Could not be checked" with no explanation is refused too.
$_POST['coverage_outcome'] = array( 'found', 'unavailable', 'none' );
$_POST['coverage_reason']  = array( '', '', '' );

$vr_refused = $vr_actions->run( $vr_order->get_id(), 'upload' );
vr_it_ok(
	'an unexplained gap is refused',
	is_wp_error( $vr_refused ) && 'vr_coverage_reason_required' === $vr_refused->get_error_code(),
	is_wp_error( $vr_refused ) ? $vr_refused->get_error_code() : 'no error'
);

$_POST = array();

// Stored properly, with one source that could not be reached.
$vr_coverage = Coverage::from_input(
	array( 'found', 'none', 'unavailable' ),
	array( '', '', 'The finance register was unreachable all afternoon' ),
	Settings::source_list()
);

vr_it_ok( 'an explained gap is accepted', $vr_coverage instanceof Coverage );

$vr_version_id = $vr_reports->store_upload( $vr_order, $vr_file(), 'DVSA MOT history, DVLA vehicle enquiry', '', $vr_coverage );
vr_it_ok( 'the report is stored', ! is_wp_error( $vr_version_id ), is_wp_error( $vr_version_id ) ? $vr_version_id->get_error_message() : '' );

$vr_stored = Coverage::from_json( (string) $vr_versions->current_for( $vr_order->get_id() )->coverage_json );

vr_it_ok( 'the coverage comes back from storage', $vr_stored instanceof Coverage );
vr_it_is( 'with every source', 3, $vr_stored instanceof Coverage ? count( $vr_stored->rows() ) : 0 );
vr_it_is( 'and the gap named', 'Outstanding finance', $vr_stored instanceof Coverage ? $vr_stored->unavailable()[0]['source'] : '' );
vr_it_is(
	'as not checked rather than as clear',
	CoverageOutcome::UNAVAILABLE,
	$vr_stored instanceof Coverage ? $vr_stored->unavailable()[0]['outcome'] : null
);

// The customer is told the same thing in the delivery email.
$vr_order = wc_get_order( $vr_order->get_id() );
$vr_reports->mark_delivered( $vr_order );
$vr_order = wc_get_order( $vr_order->get_id() );

$vr_captured = '';
$vr_capture  = static function ( $short_circuit, array $atts ) use ( &$vr_captured ) {
	$vr_captured = (string) ( $atts['message'] ?? '' );

	return true;
};

add_filter( 'pre_wp_mail', $vr_capture, 1, 2 );
$vr_notifier->send_report_ready( $vr_order );
remove_filter( 'pre_wp_mail', $vr_capture, 1 );

vr_it_ok( 'the delivery email lists each source', str_contains( $vr_captured, 'DVSA MOT history' ) && str_contains( $vr_captured, 'Outstanding finance' ) );
vr_it_ok( 'it says plainly what was not checked', str_contains( $vr_captured, 'Not checked' ) );
vr_it_ok( 'it gives the reason', str_contains( $vr_captured, 'unreachable all afternoon' ) );
vr_it_ok( 'it does not let the customer read a gap as a pass', str_contains( $vr_captured, 'not a sign that there is nothing to find' ) );

wp_set_current_user( 0 );
Settings::update( array( 'data_sources' => $vr_sources_before ) );
