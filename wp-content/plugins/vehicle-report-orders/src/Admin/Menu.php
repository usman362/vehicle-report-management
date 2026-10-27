<?php
/**
 * Admin menu registration.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the "Vehicle Reports" menu and its pages.
 *
 * Each page declares the capability it needs, so a support agent sees the queue
 * without gaining settings or audit access.
 */
final class Menu {

	public const SLUG_QUEUE    = 'vr-fulfilment-queue';
	public const SLUG_SETTINGS = 'vr-report-settings';
	public const SLUG_AUDIT    = 'vr-audit-log';

	/**
	 * Fulfilment queue page.
	 *
	 * @var FulfillmentQueuePage
	 */
	private FulfillmentQueuePage $queue;

	/**
	 * Settings page.
	 *
	 * @var SettingsPage
	 */
	private SettingsPage $settings;

	/**
	 * Audit log page.
	 *
	 * @var AuditLogPage
	 */
	private AuditLogPage $audit;

	/**
	 * Constructor.
	 *
	 * @param FulfillmentQueuePage $queue    Queue page.
	 * @param SettingsPage         $settings Settings page.
	 * @param AuditLogPage         $audit    Audit page.
	 */
	public function __construct( FulfillmentQueuePage $queue, SettingsPage $settings, AuditLogPage $audit ) {
		$this->queue    = $queue;
		$this->settings = $settings;
		$this->audit    = $audit;
	}

	/**
	 * Hook the menu up.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_pages' ) );
	}

	/**
	 * Add the menu and its sub-pages.
	 *
	 * @return void
	 */
	public function add_pages(): void {
		$outstanding = $this->queue->outstanding_count();
		$title       = __( 'Vehicle Reports', 'vehicle-report-orders' );

		if ( $outstanding > 0 ) {
			$title .= sprintf(
				' <span class="awaiting-mod"><span class="pending-count">%s</span></span>',
				esc_html( number_format_i18n( $outstanding ) )
			);
		}

		add_menu_page(
			__( 'Vehicle Reports', 'vehicle-report-orders' ),
			$title,
			CapabilityManager::VIEW_QUEUE,
			self::SLUG_QUEUE,
			array( $this->queue, 'render' ),
			'dashicons-media-document',
			56
		);

		add_submenu_page(
			self::SLUG_QUEUE,
			__( 'Fulfilment queue', 'vehicle-report-orders' ),
			__( 'Fulfilment queue', 'vehicle-report-orders' ),
			CapabilityManager::VIEW_QUEUE,
			self::SLUG_QUEUE,
			array( $this->queue, 'render' )
		);

		add_submenu_page(
			self::SLUG_QUEUE,
			__( 'Report settings', 'vehicle-report-orders' ),
			__( 'Settings', 'vehicle-report-orders' ),
			CapabilityManager::MANAGE_SETTINGS,
			self::SLUG_SETTINGS,
			array( $this->settings, 'render' )
		);

		add_submenu_page(
			self::SLUG_QUEUE,
			__( 'Audit log', 'vehicle-report-orders' ),
			__( 'Audit log', 'vehicle-report-orders' ),
			CapabilityManager::VIEW_AUDIT,
			self::SLUG_AUDIT,
			array( $this->audit, 'render' )
		);
	}

	/**
	 * URL of one of the plugin's admin pages.
	 *
	 * @param string               $slug Page slug.
	 * @param array<string, mixed> $args Extra query arguments.
	 *
	 * @return string
	 */
	public static function url( string $slug, array $args = array() ): string {
		return add_query_arg( array_merge( array( 'page' => $slug ), $args ), admin_url( 'admin.php' ) );
	}
}
