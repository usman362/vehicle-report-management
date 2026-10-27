<?php
/**
 * T11 and T30: a staff member cannot do more than their role allows.
 *
 * @package VehicleReportOrders
 */

use VehicleReportOrders\Admin\CapabilityManager;
use VehicleReportOrders\Admin\FulfillmentActions;
use VehicleReportOrders\Audit\AuditLogger;
use VehicleReportOrders\Notifications\EmailNotifier;
use VehicleReportOrders\Reports\ReportService;
use VehicleReportOrders\Reports\ReportVersionRepository;
use VehicleReportOrders\Schema;

vr_it_case( 'Capabilities hold at the action, not just in the menu (T11, T30)' );

$vr_user = static function ( string $role ): int {
	$id = wp_insert_user(
		array(
			'user_login' => 'vr-integration-' . $role . '-' . wp_generate_password( 6, false ),
			'user_pass'  => wp_generate_password( 20 ),
			'user_email' => 'vr-integration-' . wp_generate_password( 8, false ) . '@example.test',
			'role'       => $role,
		)
	);

	return is_wp_error( $id ) ? 0 : (int) $id;
};

$vr_support    = $vr_user( CapabilityManager::ROLE_SUPPORT );
$vr_fulfilment = $vr_user( CapabilityManager::ROLE_FULFILMENT );
$vr_manager    = $vr_user( CapabilityManager::ROLE_MANAGER );

vr_it_ok( 'the three staff roles exist and users can be created in them', $vr_support > 0 && $vr_fulfilment > 0 && $vr_manager > 0 );

$vr_reports = new ReportService( new ReportVersionRepository() );
$vr_actions = new FulfillmentActions( $vr_reports, new EmailNotifier( $vr_reports ) );
$vr_order   = vr_it_order( 'VR11 CAP' );

// A support agent may resend, but may not claim or deliver.
wp_set_current_user( $vr_support );
vr_it_ok( 'support agent may resend a report', current_user_can( CapabilityManager::RESEND_REPORT ) );
$vr_result = $vr_actions->run( $vr_order->get_id(), 'claim' );
vr_it_ok( 'support agent is refused when claiming an order', is_wp_error( $vr_result ) && 'vr_forbidden' === $vr_result->get_error_code(), is_wp_error( $vr_result ) ? $vr_result->get_error_code() : 'no error returned' );
$vr_result = $vr_actions->run( $vr_order->get_id(), 'deliver' );
vr_it_ok( 'support agent is refused when delivering a report', is_wp_error( $vr_result ) && 'vr_forbidden' === $vr_result->get_error_code() );

// Fulfilment staff may claim, but may not change settings or read the audit log.
wp_set_current_user( $vr_fulfilment );
$vr_result = $vr_actions->run( $vr_order->get_id(), 'claim' );
vr_it_ok( 'fulfilment staff may claim an order', ! is_wp_error( $vr_result ), is_wp_error( $vr_result ) ? $vr_result->get_error_message() : '' );
vr_it_ok( 'fulfilment staff may not change settings', ! current_user_can( CapabilityManager::MANAGE_SETTINGS ) );
vr_it_ok( 'fulfilment staff may not read the audit log', ! current_user_can( CapabilityManager::VIEW_AUDIT ) );

// T30: no staff role can export the customer list or reach user administration.
foreach ( array(
	'fulfilment staff' => $vr_fulfilment,
	'support agent'    => $vr_support,
	'store manager'    => $vr_manager,
) as $vr_label => $vr_id ) {
	wp_set_current_user( $vr_id );

	foreach ( array( 'export', 'list_users', 'edit_users', 'manage_woocommerce', 'export_others_personal_data', 'erase_others_personal_data', 'manage_options' ) as $vr_cap ) {
		vr_it_ok( "{$vr_label} may not {$vr_cap}", ! current_user_can( $vr_cap ) );
	}
}

// Every refusal is on the record.
global $wpdb;

$vr_denied = (int) $wpdb->get_var(
	$wpdb->prepare(
		'SELECT COUNT(*) FROM %i WHERE order_id = %d AND action = %s',
		AuditLogger::table(),
		$vr_order->get_id(),
		'permission.denied'
	)
);

vr_it_is( 'both refusals were written to the audit trail', 2, $vr_denied );

$vr_claimed = (int) $wpdb->get_var(
	$wpdb->prepare(
		'SELECT COUNT(*) FROM %i WHERE order_id = %d AND action = %s',
		AuditLogger::table(),
		$vr_order->get_id(),
		'fulfilment.claimed'
	)
);

vr_it_is( 'the permitted claim was recorded once', 1, $vr_claimed );

wp_set_current_user( 0 );
