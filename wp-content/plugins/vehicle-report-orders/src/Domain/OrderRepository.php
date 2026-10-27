<?php
/**
 * Order queries for the fulfilment workflow.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * Finds orders through the WooCommerce CRUD layer.
 *
 * Everything goes through wc_get_orders() rather than SQL against wp_posts, so
 * the plugin keeps working whether or not High-Performance Order Storage is
 * switched on.
 */
final class OrderRepository {

	/**
	 * Search the fulfilment queue.
	 *
	 * @param array{status?: string, assigned?: int, search?: string, overdue?: bool, page?: int, per_page?: int} $filters Filters.
	 *
	 * @return array{orders: list<\WC_Order>, total: int}
	 */
	public function search( array $filters = array() ): array {
		$per_page = max( 1, (int) ( $filters['per_page'] ?? 20 ) );
		$page     = max( 1, (int) ( $filters['page'] ?? 1 ) );

		$args = array(
			'limit'      => $per_page,
			'paged'      => $page,
			'paginate'   => true,
			'orderby'    => 'date',
			'order'      => 'ASC',
			'type'       => 'shop_order',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Fulfilment state is an order attribute; WooCommerce indexes order meta.
				array(
					'key'     => OrderMeta::REPORT_STATUS,
					'compare' => 'EXISTS',
				),
			),
		);

		$status = (string) ( $filters['status'] ?? '' );

		if ( 'outstanding' === $status ) {
			$args['meta_query'][] = array(
				'key'     => OrderMeta::REPORT_STATUS,
				'value'   => array(
					FulfillmentStatus::AWAITING_STAFF->value,
					FulfillmentStatus::IN_PROGRESS->value,
					FulfillmentStatus::NEEDS_CUSTOMER_INFO->value,
					FulfillmentStatus::REPORT_UPLOADED->value,
					FulfillmentStatus::DELIVERY_FAILED->value,
				),
				'compare' => 'IN',
			);
		} elseif ( '' !== $status && null !== FulfillmentStatus::tryFrom( $status ) ) {
			$args['meta_query'][] = array(
				'key'   => OrderMeta::REPORT_STATUS,
				'value' => $status,
			);
		}

		if ( ! empty( $filters['assigned'] ) ) {
			$args['meta_query'][] = array(
				'key'   => OrderMeta::ASSIGNED_STAFF_ID,
				'value' => (int) $filters['assigned'],
			);
		}

		if ( ! empty( $filters['overdue'] ) ) {
			$args['meta_query'][] = array(
				'key'     => OrderMeta::SLA_DUE_AT,
				'value'   => gmdate( 'Y-m-d H:i:s' ),
				'compare' => '<',
				'type'    => 'DATETIME',
			);
		}

		$search = trim( (string) ( $filters['search'] ?? '' ) );

		if ( '' !== $search ) {
			$normalized = RegistrationNumber::normalize( $search );

			if ( '' !== $normalized && RegistrationNumber::is_plausible( $normalized ) ) {
				$args['meta_query'][] = array(
					'key'   => OrderMeta::NORMALIZED_REGISTRATION,
					'value' => $normalized,
				);
			} elseif ( is_numeric( $search ) ) {
				$args['include'] = array( (int) $search );
			} else {
				$args['billing_email'] = $search;
			}
		}

		$results = wc_get_orders( $args );

		if ( is_object( $results ) && isset( $results->orders ) ) {
			return array(
				'orders' => $results->orders,
				'total'  => (int) $results->total,
			);
		}

		return array(
			'orders' => is_array( $results ) ? $results : array(),
			'total'  => is_array( $results ) ? count( $results ) : 0,
		);
	}

	/**
	 * How many orders still need staff work.
	 *
	 * @return int
	 */
	public function outstanding_count(): int {
		$result = $this->search(
			array(
				'status'   => 'outstanding',
				'per_page' => 1,
			)
		);

		return $result['total'];
	}

	/**
	 * Current fulfilment status of an order.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return FulfillmentStatus
	 */
	public static function status_of( \WC_Order $order ): FulfillmentStatus {
		return FulfillmentStatus::from_stored( (string) $order->get_meta( OrderMeta::REPORT_STATUS ) );
	}

	/**
	 * Registration mark recorded on an order, if any.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return RegistrationNumber|null
	 */
	public static function registration_of( \WC_Order $order ): ?RegistrationNumber {
		$entered = (string) $order->get_meta( OrderMeta::ORIGINAL_REGISTRATION );

		if ( '' === $entered ) {
			return null;
		}

		try {
			return RegistrationNumber::from_input( $entered );
		} catch ( \InvalidArgumentException $exception ) {
			unset( $exception );

			return null;
		}
	}

	/**
	 * Hours remaining against the promised delivery window.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return float|null Negative when overdue, null when no window was promised.
	 */
	public static function hours_until_due( \WC_Order $order ): ?float {
		$due = (string) $order->get_meta( OrderMeta::SLA_DUE_AT );

		if ( '' === $due ) {
			return null;
		}

		$timestamp = strtotime( $due . ' UTC' );

		if ( false === $timestamp ) {
			return null;
		}

		return ( $timestamp - time() ) / HOUR_IN_SECONDS;
	}
}
