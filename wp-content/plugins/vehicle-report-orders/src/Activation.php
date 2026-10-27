<?php
/**
 * Activation and deactivation routines.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders;

use VehicleReportOrders\Admin\CapabilityManager;
use VehicleReportOrders\Reports\PrivateStorage;

defined( 'ABSPATH' ) || exit;

/**
 * Runs when the plugin is activated or deactivated.
 */
final class Activation {

	/**
	 * Option holding the installed plugin version.
	 *
	 * @var string
	 */
	public const VERSION_OPTION = 'vr_plugin_version';

	/**
	 * Create tables, roles and the protected report directory.
	 *
	 * Nothing here touches existing orders or WooCommerce settings.
	 *
	 * @return void
	 */
	public static function activate(): void {
		Schema::install();
		CapabilityManager::install();
		PrivateStorage::prepare();

		update_option( self::VERSION_OPTION, VERSION );
	}

	/**
	 * Leave data and staff roles intact; only stop scheduled work and remove
	 * the capabilities granted to core roles.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		CapabilityManager::remove_from_core_roles();

		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'vr_retry_report_delivery' );
		}
	}
}
