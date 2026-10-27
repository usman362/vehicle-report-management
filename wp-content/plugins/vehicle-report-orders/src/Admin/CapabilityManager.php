<?php
/**
 * Roles and capabilities.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Creates the least-privilege roles the fulfilment workflow needs.
 *
 * Uploading a customer's report must not require a WordPress administrator, and
 * reading the audit trail must not require the ability to change settings, so
 * each action has its own capability.
 */
final class CapabilityManager {

	public const VIEW_QUEUE      = 'vr_view_queue';
	public const CLAIM_ORDER     = 'vr_claim_order';
	public const UPLOAD_REPORT   = 'vr_upload_report';
	public const DELIVER_REPORT  = 'vr_deliver_report';
	public const RESEND_REPORT   = 'vr_resend_report';
	public const VIEW_AUDIT      = 'vr_view_audit';
	public const MANAGE_SETTINGS = 'vr_manage_settings';

	public const ROLE_FULFILMENT = 'vr_fulfilment_staff';
	public const ROLE_SUPPORT    = 'vr_support_agent';
	public const ROLE_MANAGER    = 'vr_store_manager';

	/**
	 * Every capability this plugin defines.
	 *
	 * @return list<string>
	 */
	public static function all(): array {
		return array(
			self::VIEW_QUEUE,
			self::CLAIM_ORDER,
			self::UPLOAD_REPORT,
			self::DELIVER_REPORT,
			self::RESEND_REPORT,
			self::VIEW_AUDIT,
			self::MANAGE_SETTINGS,
		);
	}

	/**
	 * Capabilities granted to each role this plugin creates.
	 *
	 * @return array<string, array{name: string, caps: list<string>}>
	 */
	public static function roles(): array {
		return array(
			self::ROLE_FULFILMENT => array(
				'name' => __( 'Report fulfilment staff', 'vehicle-report-orders' ),
				'caps' => array( self::VIEW_QUEUE, self::CLAIM_ORDER, self::UPLOAD_REPORT, self::DELIVER_REPORT ),
			),
			self::ROLE_SUPPORT    => array(
				'name' => __( 'Report support agent', 'vehicle-report-orders' ),
				'caps' => array( self::VIEW_QUEUE, self::RESEND_REPORT ),
			),
			self::ROLE_MANAGER    => array(
				'name' => __( 'Report store manager', 'vehicle-report-orders' ),
				'caps' => array( self::VIEW_QUEUE, self::CLAIM_ORDER, self::UPLOAD_REPORT, self::DELIVER_REPORT, self::RESEND_REPORT, self::VIEW_AUDIT, self::MANAGE_SETTINGS ),
			),
		);
	}

	/**
	 * Create the roles and grant administrators every capability.
	 *
	 * @return void
	 */
	public static function install(): void {
		foreach ( self::roles() as $slug => $role ) {
			$capabilities = array( 'read' => true );

			foreach ( $role['caps'] as $capability ) {
				$capabilities[ $capability ] = true;
			}

			// Staff work inside wp-admin but may not browse the whole site data.
			remove_role( $slug );
			add_role( $slug, $role['name'], $capabilities );
		}

		$administrator = get_role( 'administrator' );

		if ( $administrator instanceof \WP_Role ) {
			foreach ( self::all() as $capability ) {
				$administrator->add_cap( $capability );
			}
		}

		$shop_manager = get_role( 'shop_manager' );

		if ( $shop_manager instanceof \WP_Role ) {
			foreach ( self::all() as $capability ) {
				$shop_manager->add_cap( $capability );
			}
		}
	}

	/**
	 * Remove the capabilities from core roles; custom roles are left in place
	 * so a deactivation does not strip a member of staff of their account.
	 *
	 * @return void
	 */
	public static function remove_from_core_roles(): void {
		foreach ( array( 'administrator', 'shop_manager' ) as $slug ) {
			$role = get_role( $slug );

			if ( ! $role instanceof \WP_Role ) {
				continue;
			}

			foreach ( self::all() as $capability ) {
				$role->remove_cap( $capability );
			}
		}
	}
}
