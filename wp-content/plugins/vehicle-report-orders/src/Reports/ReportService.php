<?php
/**
 * Storing and releasing report files.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Reports;

use VehicleReportOrders\Audit\AuditLogger;
use VehicleReportOrders\Domain\FulfillmentStatus;
use VehicleReportOrders\Domain\OrderMeta;
use VehicleReportOrders\Domain\OrderRepository;
use VehicleReportOrders\Domain\Settings;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * The only route a report file takes from staff to customer.
 *
 * Uploading is not delivering: a file sits in private storage until somebody
 * with the delivery capability releases it, and every step is written to the
 * audit trail.
 */
final class ReportService {

	private const SECRET_META = '_vr_download_secret';

	/**
	 * Version storage.
	 *
	 * @var ReportVersionRepository
	 */
	private ReportVersionRepository $versions;

	/**
	 * Constructor.
	 *
	 * @param ReportVersionRepository $versions Version storage.
	 */
	public function __construct( ReportVersionRepository $versions ) {
		$this->versions = $versions;
	}

	/**
	 * Validate an upload, move it into private storage and record the version.
	 *
	 * @param \WC_Order            $order   Order the report belongs to.
	 * @param array<string, mixed> $file    Entry from $_FILES.
	 * @param string               $sources Sources staff actually used.
	 * @param string               $notes   Coverage limitations, in the preparer's own words.
	 * @param Coverage|null        $coverage Source-by-source record of what could be checked.
	 *
	 * @return int|WP_Error New version identifier.
	 */
	public function store_upload( \WC_Order $order, array $file, string $sources, string $notes, ?Coverage $coverage = null ) {
		if ( ! PrivateStorage::prepare() ) {
			return new WP_Error( 'vr_storage_unavailable', __( 'The private report directory is not writable.', 'vehicle-report-orders' ) );
		}

		$tmp_name  = (string) ( $file['tmp_name'] ?? '' );
		$file_name = sanitize_file_name( (string) ( $file['name'] ?? '' ) );
		$size      = (int) ( $file['size'] ?? 0 );

		if ( ! is_uploaded_file( $tmp_name ) && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return new WP_Error( 'vr_not_an_upload', __( 'That file did not arrive through an upload.', 'vehicle-report-orders' ) );
		}

		$check = UploadValidator::check( $tmp_name, $file_name, $size );

		if ( ! $check['ok'] ) {
			AuditLogger::log( $order->get_id(), 'report.upload_rejected', array( 'reason' => $check['code'] ) );

			return new WP_Error( 'vr_invalid_upload_' . $check['code'], $check['message'] );
		}

		$hash = (string) hash_file( 'sha256', $tmp_name );

		$version_number = count( $this->versions->all_for( $order->get_id() ) ) + 1;
		$storage_key    = UploadValidator::storage_key( $order->get_id(), $version_number );
		$destination    = trailingslashit( PrivateStorage::base_path() ) . $storage_key;

		if ( ! wp_mkdir_p( dirname( $destination ) ) ) {
			return new WP_Error( 'vr_storage_unavailable', __( 'The report could not be stored.', 'vehicle-report-orders' ) );
		}

		$moved = ( defined( 'WP_CLI' ) && WP_CLI )
			? copy( $tmp_name, $destination ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy -- CLI-side helper for tests and migrations.
			: move_uploaded_file( $tmp_name, $destination );

		if ( ! $moved ) {
			return new WP_Error( 'vr_storage_failed', __( 'The report could not be stored.', 'vehicle-report-orders' ) );
		}

		// Readable by the web user only; the file is served by PHP, not Apache.
		chmod( $destination, 0600 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Hardening a file the plugin just created.

		$version_id = $this->versions->add(
			$order->get_id(),
			array(
				'storage_key'       => $storage_key,
				'original_filename' => $file_name,
				'mime_type'         => 'application/pdf',
				'size_bytes'        => $size,
				'sha256'            => $hash,
				'source_summary'    => $sources,
				'coverage_notes'    => $notes,
				'coverage_json'     => $coverage instanceof Coverage ? $coverage->to_json() : '',
			)
		);

		if ( 0 === $version_id ) {
			return new WP_Error( 'vr_version_not_recorded', __( 'The report file was stored but could not be recorded.', 'vehicle-report-orders' ) );
		}

		$previous = OrderRepository::status_of( $order );

		$order->update_meta_data( OrderMeta::REPORT_STATUS, FulfillmentStatus::REPORT_UPLOADED->value );
		$order->update_meta_data( OrderMeta::REPORT_UPLOADED_AT, gmdate( 'Y-m-d H:i:s' ) );
		$order->save();

		AuditLogger::log(
			$order->get_id(),
			'report.uploaded',
			array(
				'version_id' => $version_id,
				'sha256'     => $hash,
				'size_bytes' => $size,
				'sources'    => $sources,
				'coverage'   => $coverage instanceof Coverage ? $coverage->summary() : 'not recorded',
			),
			$previous->value,
			FulfillmentStatus::REPORT_UPLOADED->value
		);

		return $version_id;
	}

	/**
	 * Per-order signing secret, created on first use.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return string
	 */
	public function order_secret( \WC_Order $order ): string {
		$secret = (string) $order->get_meta( self::SECRET_META );

		if ( '' === $secret ) {
			$secret = bin2hex( random_bytes( 32 ) );
			$order->update_meta_data( self::SECRET_META, $secret );
			$order->save();
		}

		return $secret;
	}

	/**
	 * Invalidate every link already sent for an order.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return void
	 */
	public function revoke_links( \WC_Order $order ): void {
		$order->update_meta_data( self::SECRET_META, bin2hex( random_bytes( 32 ) ) );
		$order->save();

		AuditLogger::log( $order->get_id(), 'report.links_revoked' );
	}

	/**
	 * A time-limited link to the current report.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return string Empty string when there is nothing to link to.
	 */
	public function download_url( \WC_Order $order ): string {
		$version = $this->versions->current_for( $order->get_id() );

		if ( null === $version ) {
			return '';
		}

		$ttl_hours = max( 1, (int) Settings::get( 'download_ttl_hours' ) );
		$token     = DownloadToken::create(
			$order->get_id(),
			(int) $version->id,
			time() + ( $ttl_hours * HOUR_IN_SECONDS ),
			$this->order_secret( $order )
		);

		return add_query_arg( 'vr-report', rawurlencode( $token ), home_url( '/' ) );
	}

	/**
	 * Release the current report to the customer.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return true|WP_Error
	 */
	public function mark_delivered( \WC_Order $order ) {
		$version = $this->versions->current_for( $order->get_id() );

		if ( null === $version ) {
			return new WP_Error( 'vr_nothing_to_deliver', __( 'There is no uploaded report to deliver.', 'vehicle-report-orders' ) );
		}

		$previous = OrderRepository::status_of( $order );

		if ( ! $previous->can_move_to( FulfillmentStatus::DELIVERED ) ) {
			return new WP_Error(
				'vr_bad_transition',
				sprintf(
					/* translators: %s: current fulfilment state. */
					__( 'A report cannot be delivered from the state "%s".', 'vehicle-report-orders' ),
					$previous->label()
				)
			);
		}

		$order->update_meta_data( OrderMeta::REPORT_STATUS, FulfillmentStatus::DELIVERED->value );
		$order->update_meta_data( OrderMeta::DELIVERED_AT, gmdate( 'Y-m-d H:i:s' ) );
		$order->save();

		AuditLogger::log(
			$order->get_id(),
			'report.delivered',
			array( 'version_id' => (int) $version->id ),
			$previous->value,
			FulfillmentStatus::DELIVERED->value
		);

		return true;
	}

	/**
	 * The version repository, for callers that need the history.
	 *
	 * @return ReportVersionRepository
	 */
	public function versions(): ReportVersionRepository {
		return $this->versions;
	}
}
