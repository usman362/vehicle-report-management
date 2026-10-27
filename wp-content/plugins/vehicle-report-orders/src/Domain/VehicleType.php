<?php
/**
 * The kinds of vehicle this service will research.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * Vehicle types the merchant can accept orders for.
 *
 * The type matters because the sources differ: a motorcycle and a heavy goods
 * vehicle do not carry the same records as a car. The merchant chooses which
 * types to offer; anything not offered cannot be ordered.
 */
final class VehicleType {

	/**
	 * Every type this plugin knows how to label, keyed by slug.
	 *
	 * @return array<string, string>
	 */
	public static function catalogue(): array {
		return array(
			'car'        => __( 'Car', 'vehicle-report-orders' ),
			'motorcycle' => __( 'Motorcycle', 'vehicle-report-orders' ),
			'van'        => __( 'Van or light commercial', 'vehicle-report-orders' ),
			'truck'      => __( 'Truck or heavy goods vehicle', 'vehicle-report-orders' ),
			'motorhome'  => __( 'Motorhome or caravan', 'vehicle-report-orders' ),
			'other'      => __( 'Something else', 'vehicle-report-orders' ),
		);
	}

	/**
	 * The types the merchant currently accepts, keyed by slug.
	 *
	 * @return array<string, string>
	 */
	public static function offered(): array {
		$catalogue = self::catalogue();
		$offered   = array();

		foreach ( Settings::vehicle_types() as $slug ) {
			if ( isset( $catalogue[ $slug ] ) ) {
				$offered[ $slug ] = $catalogue[ $slug ];
			}
		}

		return $offered;
	}

	/**
	 * Whether the merchant accepts this type.
	 *
	 * @param string $slug Type identifier.
	 *
	 * @return bool
	 */
	public static function is_offered( string $slug ): bool {
		return isset( self::offered()[ $slug ] );
	}

	/**
	 * A type's label, falling back to the raw slug.
	 *
	 * @param string $slug Type identifier.
	 *
	 * @return string
	 */
	public static function label( string $slug ): string {
		return self::catalogue()[ $slug ] ?? $slug;
	}
}
