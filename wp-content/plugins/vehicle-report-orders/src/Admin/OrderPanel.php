<?php
/**
 * Report panel on the WooCommerce order screen.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Admin;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use VehicleReportOrders\Domain\FulfillmentStatus;
use VehicleReportOrders\Domain\OrderMeta;
use VehicleReportOrders\Domain\OrderRepository;
use VehicleReportOrders\Reports\ReportService;

defined( 'ABSPATH' ) || exit;

/**
 * Shows the fulfilment state and the actions a member of staff may take.
 *
 * The panel registers against both order screens, because a site may or may
 * not have High-Performance Order Storage switched on.
 */
final class OrderPanel {

	/**
	 * Report storage.
	 *
	 * @var ReportService
	 */
	private ReportService $reports;

	/**
	 * Constructor.
	 *
	 * @param ReportService $reports Report storage.
	 */
	public function __construct( ReportService $reports ) {
		$this->reports = $reports;
	}

	/**
	 * Hook the metabox and notices.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'render_notice' ) );
	}

	/**
	 * Register the panel on whichever order screen is in use.
	 *
	 * @param string $screen_id Current screen identifier.
	 * @param mixed  $post      Post or order object.
	 *
	 * @return void
	 */
	public function add_meta_box( $screen_id, $post = null ): void {
		unset( $post );

		$screens = array( 'shop_order', 'woocommerce_page_wc-orders' );

		if ( class_exists( CustomOrdersTableController::class ) && function_exists( 'wc_get_container' ) ) {
			$screens[] = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
				? wc_get_page_screen_id( 'shop-order' )
				: 'shop_order';
		}

		if ( ! in_array( (string) $screen_id, $screens, true ) ) {
			return;
		}

		add_meta_box(
			'vr-report-panel',
			__( 'Vehicle report', 'vehicle-report-orders' ),
			array( $this, 'render' ),
			(string) $screen_id,
			'normal',
			'high'
		);
	}

	/**
	 * Render the panel.
	 *
	 * @param mixed $post_or_order Order or post.
	 *
	 * @return void
	 */
	public function render( $post_or_order ): void {
		$order = $post_or_order instanceof \WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID ?? 0 );

		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		if ( ! current_user_can( CapabilityManager::VIEW_QUEUE ) ) {
			echo '<p>' . esc_html__( 'You do not have permission to see report fulfilment.', 'vehicle-report-orders' ) . '</p>';

			return;
		}

		$status       = OrderRepository::status_of( $order );
		$registration = OrderRepository::registration_of( $order );
		$hours_left   = OrderRepository::hours_until_due( $order );
		$versions     = $this->reports->versions()->all_for( $order->get_id() );
		$current      = $this->reports->versions()->current_for( $order->get_id() );
		$assignee     = (int) $order->get_meta( OrderMeta::ASSIGNED_STAFF_ID );
		$transitions  = FulfillmentStatus::transitions()[ $status->value ];

		include __DIR__ . '/../../templates/admin/order-panel.php';
	}

	/**
	 * Show the result of the last action.
	 *
	 * @return void
	 */
	public function render_notice(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only message passed through a redirect.
		if ( ! isset( $_GET['vr_notice'], $_GET['vr_message'] ) ) {
			return;
		}

		$type    = 'success' === sanitize_key( wp_unslash( $_GET['vr_notice'] ) ) ? 'success' : 'error';
		$raw     = sanitize_text_field( wp_unslash( $_GET['vr_message'] ) );
		$message = sanitize_text_field( rawurldecode( $raw ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $type ),
			esc_html( $message )
		);
	}
}
