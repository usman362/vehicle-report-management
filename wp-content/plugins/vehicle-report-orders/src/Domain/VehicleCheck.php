<?php
/**
 * The catalogue of checks a UK vehicle report can contain.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * What a UK vehicle report can answer, and what each answer does not mean.
 *
 * These are descriptions of checks that exist for UK vehicles, not a claim
 * that this site performs any of them: which ones the merchant is licensed to
 * run is listed in `Settings::source_list()`, and the fulfilment queue records
 * per source what could actually be checked for a given vehicle. Every entry
 * carries a `limit` because a check that is described without its limits reads
 * as a guarantee, which is exactly what this service does not sell.
 *
 * The catalogue drives three things, so they cannot drift apart: the package
 * feature lists, the explanatory sections on the storefront, and the wording
 * used when a check is sold as part of a tier.
 */
final class VehicleCheck {

	/**
	 * Every check, keyed by slug.
	 *
	 * @return array<string, array{label: string, blurb: string, limit: string}>
	 */
	public static function catalogue(): array {
		return array(
			'vehicle_details' => array(
				'label' => __( 'Vehicle details and specification', 'vehicle-report-orders' ),
				'blurb' => __( 'Make, model, variant, engine size, fuel, transmission, colour and the date the vehicle was first registered.', 'vehicle-report-orders' ),
				'limit' => __( 'Describes the vehicle as recorded, not its present condition. A vehicle can be correctly described and still be a poor buy.', 'vehicle-report-orders' ),
			),
			'tax_mot_status'  => array(
				'label' => __( 'Current tax and MOT status', 'vehicle-report-orders' ),
				'blurb' => __( 'Whether the vehicle is currently taxed and holds a valid MOT, and when each is due to expire.', 'vehicle-report-orders' ),
				'limit' => __( 'A snapshot taken on the day we research it. Status can change the day after your report is sent.', 'vehicle-report-orders' ),
			),
			'mot_history'     => array(
				'label' => __( 'MOT history, advisories and failures', 'vehicle-report-orders' ),
				'blurb' => __( 'Every recorded MOT test, the result, the recorded mileage at the time, and the advisories and failure items noted by the tester.', 'vehicle-report-orders' ),
				'limit' => __( 'Only covers tests that were recorded. A vehicle under three years old will usually have no history at all.', 'vehicle-report-orders' ),
			),
			'mileage'         => array(
				'label' => __( 'Mileage records and inconsistencies', 'vehicle-report-orders' ),
				'blurb' => __( 'Recorded mileage readings in date order, so a reading that falls rather than rises stands out.', 'vehicle-report-orders' ),
				'limit' => __( 'Built from readings that were recorded somewhere. A consistent set of readings is not proof that the mileage is genuine.', 'vehicle-report-orders' ),
			),
			'keepers'         => array(
				'label' => __( 'Previous keepers', 'vehicle-report-orders' ),
				'blurb' => __( 'How many keepers the vehicle has had and when each change was recorded.', 'vehicle-report-orders' ),
				'limit' => __( 'A keeper is the person responsible for the vehicle, which is not always its legal owner. No names or addresses are supplied, and we would not pass them on if they were.', 'vehicle-report-orders' ),
			),
			'plate_changes'   => array(
				'label' => __( 'Registration (plate) changes', 'vehicle-report-orders' ),
				'blurb' => __( 'Whether the vehicle has carried a different registration before, and when it changed.', 'vehicle-report-orders' ),
				'limit' => __( 'Changing a plate is lawful and common. It is a question to ask the seller, not a finding against the vehicle.', 'vehicle-report-orders' ),
			),
			'write_off'       => array(
				'label' => __( 'Insurance write-off category', 'vehicle-report-orders' ),
				'blurb' => __( 'Whether an insurer has recorded the vehicle as a write-off, and under which category (A, B, S or N).', 'vehicle-report-orders' ),
				'limit' => __( 'Only covers write-offs that were reported to the register we search. Damage settled privately leaves no record anywhere.', 'vehicle-report-orders' ),
			),
			'stolen'          => array(
				'label' => __( 'Stolen vehicle marker', 'vehicle-report-orders' ),
				'blurb' => __( 'Whether the vehicle is recorded as stolen and not recovered on the register we search.', 'vehicle-report-orders' ),
				'limit' => __( 'A clear result means no marker was found on that register on that day. It is not a statement that the vehicle was never stolen, and it is not a police check.', 'vehicle-report-orders' ),
			),
			'finance'         => array(
				'label' => __( 'Outstanding finance', 'vehicle-report-orders' ),
				'blurb' => __( 'Whether a finance agreement is recorded against the vehicle, with the lender and the date where that is published.', 'vehicle-report-orders' ),
				'limit' => __( 'Only covers agreements registered with the finance register we search. A lender who has not registered an agreement will not appear.', 'vehicle-report-orders' ),
			),
			'import_export'   => array(
				'label' => __( 'Import, export and scrapped markers', 'vehicle-report-orders' ),
				'blurb' => __( 'Whether the vehicle was imported, has been exported, or has been recorded as scrapped or unscrapped.', 'vehicle-report-orders' ),
				'limit' => __( 'An import marker says where the vehicle came from, not whether it was looked after there.', 'vehicle-report-orders' ),
			),
			'valuation'       => array(
				'label' => __( 'Vehicle valuation', 'vehicle-report-orders' ),
				'blurb' => __( 'An indicative trade, private and forecourt value for the vehicle as described.', 'vehicle-report-orders' ),
				'limit' => __( 'An estimate from the recorded specification and age. Nobody has seen the vehicle, so condition, service history and mileage can move it a long way.', 'vehicle-report-orders' ),
			),
		);
	}

	/**
	 * Whether a slug names a check in the catalogue.
	 *
	 * @param string $key Check slug.
	 *
	 * @return bool
	 */
	public static function exists( string $key ): bool {
		return isset( self::catalogue()[ $key ] );
	}

	/**
	 * One check's definition, or null.
	 *
	 * @param string $key Check slug.
	 *
	 * @return array{label: string, blurb: string, limit: string}|null
	 */
	public static function get( string $key ): ?array {
		return self::catalogue()[ $key ] ?? null;
	}

	/**
	 * The customer-facing name of a check, falling back to the raw slug.
	 *
	 * @param string $key Check slug.
	 *
	 * @return string
	 */
	public static function label( string $key ): string {
		$check = self::get( $key );

		return null !== $check ? $check['label'] : $key;
	}

	/**
	 * Every slug in the catalogue, in the order it is published.
	 *
	 * @return array<int, string>
	 */
	public static function keys(): array {
		return array_keys( self::catalogue() );
	}

	/**
	 * Keep only slugs that exist, in catalogue order, without duplicates.
	 *
	 * @param array<int, mixed> $keys Candidate slugs.
	 *
	 * @return array<int, string>
	 */
	public static function filter( array $keys ): array {
		$wanted = array_map( 'strval', $keys );

		return array_values( array_filter( self::keys(), static fn( string $key ): bool => in_array( $key, $wanted, true ) ) );
	}
}
