<?php
/**
 * What one source could tell us about one vehicle.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * The three honest answers a check can have.
 *
 * The distinction between NONE and UNAVAILABLE is the whole point: "we looked
 * and this vehicle has no record" and "we could not look" mean completely
 * different things to someone deciding whether to buy a car, and collapsing
 * them into a single tick is how a report ends up implying a vehicle is clear
 * when nobody checked.
 */
enum CoverageOutcome: string {

	case FOUND       = 'found';
	case NONE        = 'none';
	case UNAVAILABLE = 'unavailable';

	/**
	 * Wording for staff.
	 *
	 * @return string
	 */
	public function staff_label(): string {
		return match ( $this ) {
			self::FOUND       => __( 'Checked — a record was found', 'vehicle-report-orders' ),
			self::NONE        => __( 'Checked — no record for this vehicle', 'vehicle-report-orders' ),
			self::UNAVAILABLE => __( 'Could not be checked', 'vehicle-report-orders' ),
		};
	}

	/**
	 * Wording for the customer.
	 *
	 * @return string
	 */
	public function customer_label(): string {
		return match ( $this ) {
			self::FOUND       => __( 'Checked — see the report', 'vehicle-report-orders' ),
			self::NONE        => __( 'Checked — nothing recorded for this vehicle', 'vehicle-report-orders' ),
			self::UNAVAILABLE => __( 'Not checked — this information was not available to us', 'vehicle-report-orders' ),
		};
	}

	/**
	 * Whether the outcome has to be explained.
	 *
	 * @return bool
	 */
	public function needs_reason(): bool {
		return self::UNAVAILABLE === $this;
	}

	/**
	 * Whether this outcome means a check actually happened.
	 *
	 * @return bool
	 */
	public function was_checked(): bool {
		return self::UNAVAILABLE !== $this;
	}

	/**
	 * Read a stored value, refusing anything unrecognised.
	 *
	 * @param string $raw Stored value.
	 *
	 * @return self|null
	 */
	public static function from_stored( string $raw ): ?self {
		return self::tryFrom( $raw );
	}
}
