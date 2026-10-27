<?php
/**
 * Fulfilment queue screen.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Admin;

use VehicleReportOrders\Domain\FulfillmentStatus;
use VehicleReportOrders\Domain\OrderRepository;

defined( 'ABSPATH' ) || exit;

/**
 * The staff working view: paid orders waiting for a report.
 *
 * It shows only what fulfilment needs — who ordered, which vehicle, how long
 * the promise has left — and never becomes a second order editor.
 */
final class FulfillmentQueuePage {

	/**
	 * Order queries.
	 *
	 * @var OrderRepository
	 */
	private OrderRepository $orders;

	/**
	 * Constructor.
	 *
	 * @param OrderRepository $orders Order queries.
	 */
	public function __construct( OrderRepository $orders ) {
		$this->orders = $orders;
	}

	/**
	 * Orders still needing work, for the menu badge.
	 *
	 * @return int
	 */
	public function outstanding_count(): int {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return 0;
		}

		return $this->orders->outstanding_count();
	}

	/**
	 * Render the queue.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( CapabilityManager::VIEW_QUEUE ) ) {
			wp_die( esc_html__( 'You do not have permission to view the fulfilment queue.', 'vehicle-report-orders' ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filters on an admin screen.
		$filters = array(
			'status'   => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'outstanding',
			'search'   => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'assigned' => isset( $_GET['assigned'] ) ? absint( wp_unslash( $_GET['assigned'] ) ) : 0,
			'overdue'  => isset( $_GET['overdue'] ) && '1' === $_GET['overdue'],
			'page'     => isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1,
			'per_page' => 20,
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$result     = $this->orders->search( $filters );
		$orders     = $result['orders'];
		$total      = $result['total'];
		$pages      = (int) ceil( $total / $filters['per_page'] );
		$statuses   = FulfillmentStatus::cases();
		$repository = $this->orders;

		include __DIR__ . '/../../templates/admin/queue.php';
	}

	/**
	 * Staff who can be assigned work.
	 *
	 * @return array<int, string>
	 */
	public function assignable_staff(): array {
		$users = get_users(
			array(
				'capability' => CapabilityManager::UPLOAD_REPORT,
				'number'     => 100,
				'fields'     => array( 'ID', 'display_name' ),
			)
		);

		$choices = array();

		foreach ( $users as $user ) {
			$choices[ (int) $user->ID ] = (string) $user->display_name;
		}

		return $choices;
	}
}
