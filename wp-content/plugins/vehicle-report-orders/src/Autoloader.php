<?php
/**
 * PSR-4 autoloader for the plugin's own classes.
 *
 * Deliberately dependency-free: the plugin must install on any host without a
 * Composer vendor directory. Composer is used for development tooling only.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders;

defined( 'ABSPATH' ) || exit;

/**
 * Maps the plugin namespace onto the src directory.
 */
final class Autoloader {

	/**
	 * Namespace prefix handled by this autoloader.
	 *
	 * @var string
	 */
	private const PREFIX = __NAMESPACE__ . '\\';

	/**
	 * Register the autoloader with PHP.
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register( array( self::class, 'load' ) );
	}

	/**
	 * Load a class file when it belongs to this plugin.
	 *
	 * @param string $class_name Fully qualified class name.
	 *
	 * @return void
	 */
	public static function load( string $class_name ): void {
		if ( ! str_starts_with( $class_name, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( self::PREFIX ) );
		$path     = __DIR__ . '/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
