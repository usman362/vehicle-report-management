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
	 * A line drawing for each check, as the inner markup of a 24×24 SVG.
	 *
	 * Drawn here rather than loaded from an icon font: no third-party request,
	 * no licence, and the stroke follows the surrounding text colour. Decorative
	 * only — every icon sits beside its own heading, so it is hidden from
	 * assistive technology rather than described twice.
	 *
	 * @param string $key Check slug.
	 *
	 * @return string
	 */
	public static function icon( string $key ): string {
		$paths = array(
			'vehicle_details' => '<path d="M3 13l2-5a2 2 0 0 1 1.9-1.4h10.2A2 2 0 0 1 19 8l2 5"/><path d="M3 13h18v4a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1v-1H6v1a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><circle cx="7" cy="15.5" r="1"/><circle cx="17" cy="15.5" r="1"/>',
			'tax_mot_status'  => '<path d="M12 3l7 3v5c0 4.3-2.9 8.2-7 9.4C7.9 19.2 5 15.3 5 11V6z"/><path d="M9 11.5l2 2 4-4"/>',
			'mot_history'     => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path d="M8.5 10h7M8.5 14h7M8.5 17.5h4"/>',
			'mileage'         => '<path d="M4 18a8 8 0 1 1 16 0"/><path d="M12 18l4.2-5"/><circle cx="12" cy="18" r="1.3"/>',
			'keepers'         => '<circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><path d="M16 5.5a3 3 0 0 1 0 5.8"/><path d="M17.5 14.4A5.5 5.5 0 0 1 20.5 19"/>',
			'plate_changes'   => '<rect x="2.5" y="7" width="19" height="10" rx="2"/><path d="M6.5 7v10"/><path d="M10 11h2M14 11h3M10 14h7"/>',
			'write_off'       => '<path d="M12 4.5L21 19H3z"/><path d="M12 10v4"/><path d="M12 16.6v.1"/>',
			'stolen'          => '<path d="M12 3l7 3v5c0 4.3-2.9 8.2-7 9.4C7.9 19.2 5 15.3 5 11V6z"/><path d="M9.5 9.5l5 5M14.5 9.5l-5 5"/>',
			'finance'         => '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6 12h.1M18 12h-.1"/>',
			'import_export'   => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17"/><path d="M12 3.5c2.3 2.4 3.5 5.4 3.5 8.5S14.3 18.1 12 20.5c-2.3-2.4-3.5-5.4-3.5-8.5S9.7 5.9 12 3.5z"/>',
			'valuation'       => '<path d="M11.4 3.2H20v8.6l-8.9 8.9a1.6 1.6 0 0 1-2.3 0L3.1 15a1.6 1.6 0 0 1 0-2.3z"/><circle cx="16.3" cy="7.1" r="1.3"/>',
		);

		return $paths[ $key ] ?? '<circle cx="12" cy="12" r="8.5"/><path d="M12 8.5v4M12 15.4v.1"/>';
	}

	/**
	 * The SVG elements `icon()` is allowed to return, for `wp_kses()`.
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function icon_markup_allowed(): array {
		$geometry = array(
			'd'      => true,
			'cx'     => true,
			'cy'     => true,
			'r'      => true,
			'x'      => true,
			'y'      => true,
			'rx'     => true,
			'ry'     => true,
			'width'  => true,
			'height' => true,
		);

		return array(
			'path'   => $geometry,
			'circle' => $geometry,
			'rect'   => $geometry,
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
