<?php
/**
 * Personal-data export and erasure.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Privacy;

use VehicleReportOrders\Audit\AuditLogger;
use VehicleReportOrders\Domain\OrderMeta;
use VehicleReportOrders\Domain\OrderRepository;
use VehicleReportOrders\Domain\VehicleType;
use VehicleReportOrders\Reports\PrivateStorage;
use VehicleReportOrders\Reports\ReportVersionRepository;
use VehicleReportOrders\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Answers a customer's request to see or delete what is held about them.
 *
 * A UK registration mark identifies a vehicle, and in context a person, so it
 * is treated as personal data. Erasure removes the report file and the
 * identifying fields, but keeps the order, its totals and the audit trail:
 * financial records must survive, and an audit log that can be erased on
 * request is not an audit log.
 */
final class PersonalData {

	private const GROUP = 'vehicle-report-orders';

	/**
	 * Report versions.
	 *
	 * @var ReportVersionRepository
	 */
	private ReportVersionRepository $versions;

	/**
	 * Constructor.
	 *
	 * @param ReportVersionRepository $versions Report versions.
	 */
	public function __construct( ReportVersionRepository $versions ) {
		$this->versions = $versions;
	}

	/**
	 * Register the exporter and eraser with WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'add_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'add_eraser' ) );
		add_action( 'admin_init', array( $this, 'suggest_privacy_policy_text' ) );
	}

	/**
	 * Tell WordPress what this plugin does with personal data.
	 *
	 * The text appears as a suggestion on the privacy-policy screen. It is a
	 * starting point for the merchant's own lawyer, not finished legal copy,
	 * and it says so.
	 *
	 * @return void
	 */
	public function suggest_privacy_policy_text(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content  = '<p class="privacy-policy-tutorial">' . esc_html__( 'Draft wording for review by the merchant and their legal adviser before publication.', 'vehicle-report-orders' ) . '</p>';
		$content .= '<p>' . esc_html__( 'When you order a vehicle report we record the registration mark you entered, your order and billing details, the version of our terms you accepted, and the times at which your order was paid for and your report was delivered.', 'vehicle-report-orders' ) . '</p>';
		$content .= '<p>' . esc_html__( 'The report itself is stored as a file outside the public part of this website and is reached only through a signed link that expires. Staff actions on your order are written to an audit record so we can show what was done and when.', 'vehicle-report-orders' ) . '</p>';
		$content .= '<p>' . esc_html__( 'If you ask us to erase your data we delete the stored report file, the registration mark and the contact details held against the order, and any link already sent to you stops working. We keep the order itself, its totals and the audit record, because we are required to retain financial records and an audit record that can be deleted on request is not an audit record.', 'vehicle-report-orders' ) . '</p>';

		wp_add_privacy_policy_content( __( 'Vehicle report orders', 'vehicle-report-orders' ), wp_kses_post( $content ) );
	}

	/**
	 * Add the exporter.
	 *
	 * @param array<string, mixed> $exporters Registered exporters.
	 *
	 * @return array<string, mixed>
	 */
	public function add_exporter( array $exporters ): array {
		$exporters[ self::GROUP ] = array(
			'exporter_friendly_name' => __( 'Vehicle report orders', 'vehicle-report-orders' ),
			'callback'               => array( $this, 'export' ),
		);

		return $exporters;
	}

	/**
	 * Add the eraser.
	 *
	 * @param array<string, mixed> $erasers Registered erasers.
	 *
	 * @return array<string, mixed>
	 */
	public function add_eraser( array $erasers ): array {
		$erasers[ self::GROUP ] = array(
			'eraser_friendly_name' => __( 'Vehicle report orders', 'vehicle-report-orders' ),
			'callback'             => array( $this, 'erase' ),
		);

		return $erasers;
	}

	/**
	 * Everything held against an email address.
	 *
	 * @param string $email Customer email address.
	 * @param int    $page  Page number, one-based.
	 *
	 * @return array{data: list<array<string, mixed>>, done: bool}
	 */
	public function export( string $email, int $page = 1 ): array {
		$data = array();

		foreach ( $this->orders_for( $email, $page ) as $order ) {
			$registration = OrderRepository::registration_of( $order );
			$items        = array(
				array(
					'name'  => __( 'Order number', 'vehicle-report-orders' ),
					'value' => $order->get_order_number(),
				),
				array(
					'name'  => __( 'Vehicle registration', 'vehicle-report-orders' ),
					'value' => $registration ? $registration->entered() : '',
				),
				array(
					'name'  => __( 'VIN', 'vehicle-report-orders' ),
					'value' => (string) $order->get_meta( OrderMeta::ORIGINAL_VIN ),
				),
				array(
					'name'  => __( 'Kind of vehicle', 'vehicle-report-orders' ),
					'value' => VehicleType::label( (string) $order->get_meta( OrderMeta::VEHICLE_TYPE ) ),
				),
				array(
					'name'  => __( 'Report status', 'vehicle-report-orders' ),
					'value' => OrderRepository::status_of( $order )->label(),
				),
				array(
					'name'  => __( 'Delivery deadline', 'vehicle-report-orders' ),
					'value' => (string) $order->get_meta( OrderMeta::SLA_DUE_AT ),
				),
				array(
					'name'  => __( 'Delivered at', 'vehicle-report-orders' ),
					'value' => (string) $order->get_meta( OrderMeta::DELIVERED_AT ),
				),
				array(
					'name'  => __( 'Terms version accepted', 'vehicle-report-orders' ),
					'value' => (string) $order->get_meta( OrderMeta::TERMS_VERSION ),
				),
			);

			foreach ( $this->versions->all_for( $order->get_id() ) as $version ) {
				$items[] = array(
					'name'  => sprintf(
						/* translators: %d: report version number. */
						__( 'Report file (version %d)', 'vehicle-report-orders' ),
						(int) $version->version
					),
					'value' => sprintf( '%s — SHA-256 %s', (string) $version->original_filename, (string) $version->sha256 ),
				);
			}

			$data[] = array(
				'group_id'    => self::GROUP,
				'group_label' => __( 'Vehicle report orders', 'vehicle-report-orders' ),
				'item_id'     => 'vr-order-' . $order->get_id(),
				'data'        => $items,
			);
		}

		return array(
			'data' => $data,
			'done' => count( $data ) < $this->per_page(),
		);
	}

	/**
	 * Remove the identifying parts, keep the financial record.
	 *
	 * @param string $email Customer email address.
	 * @param int    $page  Page number, one-based.
	 *
	 * @return array{items_removed: bool, items_retained: bool, messages: list<string>, done: bool}
	 */
	public function erase( string $email, int $page = 1 ): array {
		$removed  = false;
		$retained = false;
		$messages = array();
		$orders   = $this->orders_for( $email, $page );

		foreach ( $orders as $order ) {
			foreach ( $this->versions->all_for( $order->get_id() ) as $version ) {
				$path = PrivateStorage::path_for( (string) $version->storage_key );

				if ( null !== $path && file_exists( $path ) ) {
					wp_delete_file( $path );
					$removed = true;
				}
			}

			$this->forget_report_files( $order->get_id() );

			foreach ( array( OrderMeta::ORIGINAL_REGISTRATION, OrderMeta::NORMALIZED_REGISTRATION, OrderMeta::ORIGINAL_VIN, OrderMeta::NORMALIZED_VIN, OrderMeta::CUSTOMER_EMAIL_SNAPSHOT, OrderMeta::STAFF_NOTES ) as $key ) {
				if ( '' !== (string) $order->get_meta( $key ) ) {
					$order->delete_meta_data( $key );
					$removed = true;
				}
			}

			// New secret: any link previously emailed stops working.
			$order->update_meta_data( '_vr_download_secret', bin2hex( random_bytes( 32 ) ) );
			$order->save();

			AuditLogger::log( $order->get_id(), 'privacy.erased', array( 'files_removed' => true ) );

			$retained   = true;
			$messages[] = sprintf(
				/* translators: %s: order number. */
				__( 'Order %s was kept as a financial record, without the registration, the stored report or the customer email snapshot. Its audit history is retained.', 'vehicle-report-orders' ),
				$order->get_order_number()
			);
		}

		return array(
			'items_removed'  => $removed,
			'items_retained' => $retained,
			'messages'       => $messages,
			'done'           => count( $orders ) < $this->per_page(),
		);
	}

	/**
	 * Mark report rows as erased, keeping the row for the audit trail.
	 *
	 * @param int $order_id Order identifier.
	 *
	 * @return void
	 */
	private function forget_report_files( int $order_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Dedicated plugin table.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE %i SET storage_key = '', original_filename = '[erased]', status = 'erased' WHERE order_id = %d",
				Schema::table( Schema::REPORT_VERSIONS ),
				$order_id
			)
		);
	}

	/**
	 * Orders belonging to an email address, one page at a time.
	 *
	 * @param string $email Customer email address.
	 * @param int    $page  Page number, one-based.
	 *
	 * @return list<\WC_Order>
	 */
	private function orders_for( string $email, int $page ): array {
		if ( ! is_email( $email ) || ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}

		$orders = wc_get_orders(
			array(
				'billing_email' => $email,
				'limit'         => $this->per_page(),
				'paged'         => max( 1, $page ),
				'type'          => 'shop_order',
			)
		);

		return is_array( $orders ) ? $orders : array();
	}

	/**
	 * Orders handled per request.
	 *
	 * @return int
	 */
	private function per_page(): int {
		return 20;
	}
}
