<?php
/**
 * Validation of staff-uploaded report files.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * Decides whether an uploaded file may become a customer's report.
 *
 * The checks are deliberately about the bytes, not the file name: a PHP script
 * renamed to report.pdf is still a PHP script, and it must never reach the
 * storage directory. Pure functions so they can be unit tested without an
 * upload.
 */
final class UploadValidator {

	public const MAX_BYTES = 25 * 1024 * 1024;

	/**
	 * First bytes of a PDF file.
	 *
	 * @var string
	 */
	private const PDF_MAGIC = '%PDF-';

	/**
	 * Check a file that is already on disk.
	 *
	 * @param string $path      Temporary path.
	 * @param string $file_name Name the browser supplied.
	 * @param int    $size      Size in bytes.
	 *
	 * @return array{ok: bool, code: string, message: string}
	 */
	public static function check( string $path, string $file_name, int $size ): array {
		if ( ! is_readable( $path ) ) {
			return self::fail( 'unreadable', __( 'The uploaded file could not be read.', 'vehicle-report-orders' ) );
		}

		if ( $size <= 0 ) {
			return self::fail( 'empty', __( 'The uploaded file is empty.', 'vehicle-report-orders' ) );
		}

		if ( $size > self::MAX_BYTES ) {
			return self::fail(
				'too_large',
				sprintf(
					/* translators: %s: maximum size, already formatted. */
					__( 'Report files must be %s or smaller.', 'vehicle-report-orders' ),
					size_format( self::MAX_BYTES )
				)
			);
		}

		if ( ! self::has_pdf_extension( $file_name ) ) {
			return self::fail( 'extension', __( 'Only PDF files can be delivered as reports.', 'vehicle-report-orders' ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Reading the magic bytes of an upload; WP_Filesystem has no partial read.
		$handle = fopen( $path, 'rb' );

		if ( false === $handle ) {
			return self::fail( 'unreadable', __( 'The uploaded file could not be read.', 'vehicle-report-orders' ) );
		}

		$head = (string) fread( $handle, 1024 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		if ( ! self::looks_like_pdf( $head ) ) {
			return self::fail( 'not_pdf', __( 'That file is not a PDF, whatever its name says.', 'vehicle-report-orders' ) );
		}

		if ( self::contains_executable_markup( $head ) ) {
			return self::fail( 'embedded_code', __( 'The file contains script markup and was rejected.', 'vehicle-report-orders' ) );
		}

		return array(
			'ok'      => true,
			'code'    => 'ok',
			'message' => '',
		);
	}

	/**
	 * Whether the name ends in .pdf.
	 *
	 * @param string $file_name File name.
	 *
	 * @return bool
	 */
	public static function has_pdf_extension( string $file_name ): bool {
		return 'pdf' === strtolower( (string) pathinfo( $file_name, PATHINFO_EXTENSION ) );
	}

	/**
	 * Whether the leading bytes are a PDF header.
	 *
	 * @param string $head First bytes of the file.
	 *
	 * @return bool
	 */
	public static function looks_like_pdf( string $head ): bool {
		return str_starts_with( $head, self::PDF_MAGIC );
	}

	/**
	 * Whether the leading bytes contain script markup.
	 *
	 * @param string $head First bytes of the file.
	 *
	 * @return bool
	 */
	public static function contains_executable_markup( string $head ): bool {
		return str_contains( $head, '<?php' ) || 1 === preg_match( '/<script[\s>]/i', $head );
	}

	/**
	 * A storage key that reveals nothing and cannot collide.
	 *
	 * @param int $order_id Order identifier.
	 * @param int $version  Version number.
	 *
	 * @return string
	 */
	public static function storage_key( int $order_id, int $version ): string {
		return sprintf(
			'%d/%d-%s.pdf',
			$order_id,
			$version,
			bin2hex( random_bytes( 16 ) )
		);
	}

	/**
	 * Build a failure result.
	 *
	 * @param string $code    Machine-readable reason.
	 * @param string $message Message for staff.
	 *
	 * @return array{ok: bool, code: string, message: string}
	 */
	private static function fail( string $code, string $message ): array {
		return array(
			'ok'      => false,
			'code'    => $code,
			'message' => $message,
		);
	}
}
