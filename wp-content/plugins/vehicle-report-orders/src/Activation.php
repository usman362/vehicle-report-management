<?php
/**
 * Activation and deactivation routines.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders;

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
	 * Schema creation and capability registration are added by the milestones
	 * that introduce them; activation currently only records the version so an
	 * upgrade routine has a starting point.
	 *
	 * @return void
	 */
	public static function activate(): void {
		update_option( self::VERSION_OPTION, VERSION );
	}

	/**
	 * Clean up scheduled work on deactivation.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Scheduled events are unscheduled here once delivery retries exist.
	}
}
