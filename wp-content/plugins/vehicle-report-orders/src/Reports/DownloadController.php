<?php
/**
 * Serves report files to the customer who paid for them.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Reports;

use VehicleReportOrders\Audit\AuditLogger;
use VehicleReportOrders\Domain\OrderRepository;

defined( 'ABSPATH' ) || exit;

/**
 * The single authorised route to a report file.
 *
 * Every request is checked before a byte is written: the token must verify
 * against that order's own secret, it must not have expired, the order must
 * still be in a state that grants access, and the file must sit inside the
 * private directory. A failure returns a flat status code and reveals nothing
 * about whether the order, the version or the file exists.
 */
final class DownloadController {

	public const QUERY_VAR = 'vr-report';

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
	 * Listen for download requests.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'maybe_serve' ), 1 );
		add_action( 'send_headers', array( $this, 'discourage_indexing' ) );
	}

	/**
	 * Keep report URLs out of search engines and caches.
	 *
	 * @return void
	 */
	public function discourage_indexing(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Presence check only.
		if ( ! isset( $_GET[ self::QUERY_VAR ] ) ) {
			return;
		}

		header( 'X-Robots-Tag: noindex, nofollow, noarchive' );
		nocache_headers();
	}

	/**
	 * Serve the file when the request carries a valid token.
	 *
	 * @return void
	 */
	public function maybe_serve(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- A signed token is the credential here; a nonce would break emailed links.
		$raw = isset( $_GET[ self::QUERY_VAR ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::QUERY_VAR ] ) ) : '';

		if ( '' === $raw ) {
			return;
		}

		$claim = DownloadToken::claim( $raw );

		if ( null === $claim ) {
			$this->refuse( 403, 'malformed', 0 );
		}

		$order = wc_get_order( $claim['order_id'] );

		if ( ! $order instanceof \WC_Order ) {
			$this->refuse( 403, 'unknown_order', $claim['order_id'] );
		}

		$result = DownloadToken::verify( $raw, $this->reports->order_secret( $order ), time() );

		if ( ! $result['valid'] ) {
			// An expired link is a different situation from a forged one: the
			// customer did nothing wrong and should be told to ask for a resend.
			$this->refuse( 'expired' === $result['reason'] ? 410 : 403, $result['reason'], $order->get_id() );
		}

		if ( ! OrderRepository::status_of( $order )->grants_customer_access() ) {
			$this->refuse( 403, 'not_delivered', $order->get_id() );
		}

		$version = $this->reports->versions()->find_for_order( $claim['version_id'], $order->get_id() );

		if ( null === $version ) {
			$this->refuse( 403, 'unknown_version', $order->get_id() );
		}

		$path = PrivateStorage::path_for( (string) $version->storage_key );

		if ( null === $path || ! is_readable( $path ) ) {
			$this->refuse( 410, 'file_missing', $order->get_id() );
		}

		$this->stream( $path, $order, $version );
	}

	/**
	 * Send the file.
	 *
	 * @param string    $path    Absolute path inside private storage.
	 * @param \WC_Order $order   Order.
	 * @param object    $version Report version row.
	 *
	 * @return void
	 */
	private function stream( string $path, \WC_Order $order, object $version ): void {
		AuditLogger::log(
			$order->get_id(),
			'report.downloaded',
			array(
				'version_id' => (int) $version->id,
				'bytes'      => (int) $version->size_bytes,
			)
		);

		$filename = sprintf(
			'vehicle-report-%s.pdf',
			preg_replace( '/[^A-Za-z0-9\-]/', '', (string) $order->get_order_number() )
		);

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . (string) filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Robots-Tag: noindex, nofollow, noarchive' );
		header( 'Referrer-Policy: no-referrer' );

		if ( function_exists( 'wp_ob_end_flush_all' ) ) {
			wp_ob_end_flush_all();
		}

		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Streaming a private file; WP_Filesystem would read it into memory.
		exit;
	}

	/**
	 * Stop the request with a status code and an explanation that gives
	 * nothing away.
	 *
	 * @param int    $status   HTTP status.
	 * @param string $reason   Machine reason for the audit trail.
	 * @param int    $order_id Order the attempt claimed, when known.
	 *
	 * @return never
	 */
	private function refuse( int $status, string $reason, int $order_id ) {
		AuditLogger::log(
			$order_id,
			'report.download_refused',
			array(
				'reason' => $reason,
				'status' => $status,
			)
		);

		$message = 410 === $status
			? __( 'This report link has expired. Please contact support and we will send a new one.', 'vehicle-report-orders' )
			: __( 'This report link is not valid.', 'vehicle-report-orders' );

		status_header( $status );
		nocache_headers();
		wp_die(
			esc_html( $message ),
			esc_html__( 'Report unavailable', 'vehicle-report-orders' ),
			array( 'response' => absint( $status ) )
		);
	}
}
