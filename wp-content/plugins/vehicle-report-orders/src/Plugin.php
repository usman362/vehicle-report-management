<?php
/**
 * Plugin bootstrap.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders;

use VehicleReportOrders\Admin\AuditLogPage;
use VehicleReportOrders\Admin\FulfillmentQueuePage;
use VehicleReportOrders\Admin\Menu;
use VehicleReportOrders\Admin\SettingsPage;
use VehicleReportOrders\Checkout\OrderMetaSaver;
use VehicleReportOrders\Checkout\RegistrationField;
use VehicleReportOrders\Domain\OrderLifecycle;
use VehicleReportOrders\Admin\FulfillmentActions;
use VehicleReportOrders\Admin\OrderPanel;
use VehicleReportOrders\Domain\OrderRepository;
use VehicleReportOrders\Notifications\EmailNotifier;
use VehicleReportOrders\Notifications\OrderEmailAdditions;
use VehicleReportOrders\Reports\DownloadController;
use VehicleReportOrders\Reports\ReportService;
use VehicleReportOrders\Reports\ReportVersionRepository;
use VehicleReportOrders\Storefront\Disclosures;
use VehicleReportOrders\Storefront\OrderForm;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin's services into WordPress.
 */
final class Plugin {

	/**
	 * Shared instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Guards against booting twice.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Shared instance accessor.
	 *
	 * @return self
	 */
	public static function instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Wire the plugin up once WordPress and WooCommerce are loaded.
	 *
	 * Without WooCommerce there is no order to attach a report to, so the
	 * plugin stays dormant and tells the administrator why.
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'render_missing_woocommerce_notice' ) );

			return;
		}

		// WordPress 6.7 expects translations to load no earlier than `init`.
		add_action(
			'init',
			static function (): void {
				load_plugin_textdomain( 'vehicle-report-orders', false, dirname( plugin_basename( PLUGIN_FILE ) ) . '/languages' );
			}
		);

		if ( Schema::needs_update() ) {
			Schema::install();
		}

		$reports  = new ReportService( new ReportVersionRepository() );
		$notifier = new EmailNotifier( $reports );

		( new RegistrationField() )->register();
		( new OrderMetaSaver() )->register();
		( new OrderLifecycle() )->register();
		( new DownloadController( $reports ) )->register();
		( new OrderEmailAdditions() )->register();
		( new OrderForm() )->register();
		( new Disclosures() )->register();
		$notifier->register();

		if ( is_admin() ) {
			$orders = new OrderRepository();
			$menu   = new Menu( new FulfillmentQueuePage( $orders ), new SettingsPage(), new AuditLogPage() );
			$menu->register();

			( new OrderPanel( $reports ) )->register();
			( new FulfillmentActions( $reports, $notifier ) )->register();
		}
	}

	/**
	 * Explain why the plugin is inactive when WooCommerce is missing.
	 *
	 * @return void
	 */
	public function render_missing_woocommerce_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'Vehicle Report Orders needs WooCommerce to be installed and active.', 'vehicle-report-orders' )
		);
	}
}
