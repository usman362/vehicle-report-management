<?php
/**
 * Private storage for generated report files.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps report PDFs out of reach of the web server.
 *
 * A customer's report is their own vehicle and payment history, so it must
 * never sit behind a guessable URL. Preference order:
 *
 *   1. the path in the VR_PRIVATE_STORAGE_DIR constant, if the host defines one;
 *   2. a directory one level above the WordPress root — outside the document
 *      root, so no web-server rule is needed to protect it;
 *   3. the uploads directory, protected by .htaccess, an unguessable name and
 *      an index.php, used only when nothing above the root is writable.
 *
 * Option 3 depends on the web server honouring .htaccess, which nginx and the
 * PHP development server do not. `is_outside_web_root()` reports which option
 * is in force so the administrator sees it and deployment can fix it.
 */
final class PrivateStorage {

	public const DIRECTORY_OPTION = 'vr_private_storage_dir';
	public const LOCATION_OPTION  = 'vr_private_storage_location';

	public const LOCATION_CONSTANT = 'constant';
	public const LOCATION_ABOVE    = 'above_web_root';
	public const LOCATION_UPLOADS  = 'uploads';

	/**
	 * Directory name used for this site, generated once and then reused.
	 *
	 * @return string
	 */
	private static function directory_name(): string {
		$name = (string) get_option( self::DIRECTORY_OPTION, '' );

		if ( '' === $name ) {
			$name = 'vehicle-reports-' . substr( hash_hmac( 'sha256', 'vr-private-storage', wp_salt( 'auth' ) ), 0, 16 );
			update_option( self::DIRECTORY_OPTION, $name );
		}

		return $name;
	}

	/**
	 * Absolute path of the protected directory.
	 *
	 * @return string
	 */
	public static function base_path(): string {
		$name = self::directory_name();

		if ( defined( 'VR_PRIVATE_STORAGE_DIR' ) && '' !== (string) VR_PRIVATE_STORAGE_DIR ) {
			return untrailingslashit( (string) VR_PRIVATE_STORAGE_DIR );
		}

		$above = untrailingslashit( dirname( self::web_root() ) ) . '/' . $name;

		if ( is_dir( $above ) || wp_is_writable( dirname( $above ) ) ) {
			return $above;
		}

		$uploads = wp_upload_dir();

		return trailingslashit( $uploads['basedir'] ) . $name;
	}

	/**
	 * The WordPress root as a resolved absolute path, with no trailing slash.
	 *
	 * ABSPATH is whatever the entry point made it, and WP-CLI's `--path=.`
	 * leaves it as `/site/./`. Taking dirname() of that walks up to the site
	 * itself rather than above it, which would put private report files inside
	 * the document root — the one thing this class exists to prevent.
	 *
	 * @return string
	 */
	private static function web_root(): string {
		$root = untrailingslashit( ABSPATH );
		$real = realpath( $root );

		return is_string( $real ) && '' !== $real ? untrailingslashit( $real ) : $root;
	}

	/**
	 * Which of the three locations is in use.
	 *
	 * @return string One of the LOCATION_* constants.
	 */
	public static function location(): string {
		if ( defined( 'VR_PRIVATE_STORAGE_DIR' ) && '' !== (string) VR_PRIVATE_STORAGE_DIR ) {
			return self::LOCATION_CONSTANT;
		}

		$uploads = wp_upload_dir();

		return str_starts_with( self::base_path(), trailingslashit( $uploads['basedir'] ) )
			? self::LOCATION_UPLOADS
			: self::LOCATION_ABOVE;
	}

	/**
	 * Whether the current location cannot be reached by the web server at all.
	 *
	 * @return bool
	 */
	public static function is_outside_web_root(): bool {
		return ! str_starts_with( self::base_path(), trailingslashit( self::web_root() ) );
	}

	/**
	 * Create the directory and, when it sits inside the web root, block access.
	 *
	 * @return bool True when the directory exists and is writable.
	 */
	public static function prepare(): bool {
		$path = self::base_path();

		if ( ! wp_mkdir_p( $path ) ) {
			return false;
		}

		update_option( self::LOCATION_OPTION, self::location() );

		if ( ! self::is_outside_web_root() ) {
			self::write_protection_files( $path );
		}

		return wp_is_writable( $path );
	}

	/**
	 * Last-resort protection for the uploads fallback.
	 *
	 * @param string $path Directory to protect.
	 *
	 * @return void
	 */
	private static function write_protection_files( string $path ): void {
		$htaccess = trailingslashit( $path ) . '.htaccess';

		if ( ! file_exists( $htaccess ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Protection file written once; WP_Filesystem would need credentials during activation.
			file_put_contents(
				$htaccess,
				"# Report files are served only through the plugin's authorised download route.\n"
				. "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
				. "<IfModule !mod_authz_core.c>\n\tOrder deny,allow\n\tDeny from all\n</IfModule>\n"
			);
		}

		$index = trailingslashit( $path ) . 'index.php';

		if ( ! file_exists( $index ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- As above.
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}
	}

	/**
	 * Absolute path for a stored key, rejecting any attempt to escape the
	 * protected directory.
	 *
	 * @param string $storage_key Key as recorded against the report version.
	 *
	 * @return string|null Null when the key is not inside the directory.
	 */
	public static function path_for( string $storage_key ): ?string {
		$base = realpath( self::base_path() );

		if ( false === $base ) {
			return null;
		}

		$full = trailingslashit( $base ) . ltrim( $storage_key, '/' );
		$real = realpath( $full );

		if ( false === $real || ! str_starts_with( $real, trailingslashit( $base ) ) ) {
			return null;
		}

		return $real;
	}

	/**
	 * Whether the protected directory is usable.
	 *
	 * @return bool
	 */
	public static function is_ready(): bool {
		$path = self::base_path();

		return is_dir( $path ) && wp_is_writable( $path );
	}
}
