<?php
/**
 * Vehicle identification number.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * A VIN as entered by a customer.
 *
 * Offered alongside the registration mark because an import, a vehicle between
 * plates, or one being bought from abroad may have no current UK registration.
 * Validation checks the shape only: a well-formed VIN proves nothing about a
 * vehicle existing, which is established during manual fulfilment.
 */
final class Vin {

	/**
	 * Length of a modern VIN.
	 *
	 * @var int
	 */
	public const LENGTH = 17;

	/**
	 * Uppercase a VIN and strip anything that is not part of it.
	 *
	 * @param string $input Raw customer input.
	 *
	 * @return string
	 */
	public static function normalize( string $input ): string {
		$upper = strtoupper( trim( $input ) );
		$clean = preg_replace( '/[^A-Z0-9]/', '', $upper );

		return is_string( $clean ) ? $clean : '';
	}

	/**
	 * Whether a normalised value could be a VIN.
	 *
	 * @param string $normalized Output of `normalize()`.
	 *
	 * @return bool
	 */
	public static function is_plausible( string $normalized ): bool {
		if ( self::LENGTH !== strlen( $normalized ) ) {
			return false;
		}

		// I, O and Q are excluded by the character class: they read as 1 and 0.
		return 1 === preg_match( '/^[A-HJ-NPR-Z0-9]+$/', $normalized );
	}
}
