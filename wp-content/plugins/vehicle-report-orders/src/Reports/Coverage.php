<?php
/**
 * Source-by-source coverage recorded against a report.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Reports;

use VehicleReportOrders\Domain\CoverageOutcome;

defined( 'ABSPATH' ) || exit;

/**
 * One answer per configured source, and no way to skip one.
 *
 * The site promises the customer that a report says which checks were possible
 * and which were not. That promise is only as good as the slowest afternoon in
 * the fulfilment queue, so it is enforced here instead of being left to a
 * free-text box: every source the merchant has published must be answered
 * before a report can be stored, and "could not be checked" must say why.
 */
final class Coverage {

	/**
	 * Rows, each with source, outcome and note.
	 *
	 * @var array<int, array{source: string, outcome: CoverageOutcome, note: string}>
	 */
	private array $rows;

	/**
	 * Constructor.
	 *
	 * @param array<int, array{source: string, outcome: CoverageOutcome, note: string}> $rows Coverage rows.
	 */
	private function __construct( array $rows ) {
		$this->rows = $rows;
	}

	/**
	 * Build from submitted values.
	 *
	 * @param array<string, mixed> $outcomes Submitted outcome per source index.
	 * @param array<string, mixed> $notes    Submitted note per source index.
	 * @param array<int, string>   $sources  The sources the merchant has published.
	 *
	 * @return self|\WP_Error
	 */
	public static function from_input( array $outcomes, array $notes, array $sources ) {
		if ( array() === $sources ) {
			return new \WP_Error(
				'vr_no_sources',
				__( 'No data sources are configured, so there is nothing to record against this report.', 'vehicle-report-orders' )
			);
		}

		$rows = array();

		foreach ( array_values( $sources ) as $index => $source ) {
			$raw     = isset( $outcomes[ $index ] ) ? sanitize_key( (string) $outcomes[ $index ] ) : '';
			$outcome = CoverageOutcome::from_stored( $raw );

			if ( null === $outcome ) {
				return new \WP_Error(
					'vr_coverage_incomplete',
					sprintf(
						/* translators: %s: the name of a data source. */
						__( 'Say what happened with "%s". Every source has to be answered before the report can be stored.', 'vehicle-report-orders' ),
						$source
					)
				);
			}

			$note = isset( $notes[ $index ] ) ? sanitize_textarea_field( (string) $notes[ $index ] ) : '';

			if ( $outcome->needs_reason() && '' === trim( $note ) ) {
				return new \WP_Error(
					'vr_coverage_reason_required',
					sprintf(
						/* translators: %s: the name of a data source. */
						__( 'Say why "%s" could not be checked. A check nobody can explain reads like a check that passed.', 'vehicle-report-orders' ),
						$source
					)
				);
			}

			$rows[] = array(
				'source'  => $source,
				'outcome' => $outcome,
				'note'    => $note,
			);
		}

		return new self( $rows );
	}

	/**
	 * Rebuild from what was stored.
	 *
	 * @param string $json Stored JSON.
	 *
	 * @return self|null Null when nothing was stored, as for reports predating this record.
	 */
	public static function from_json( string $json ): ?self {
		$decoded = json_decode( $json, true );

		if ( ! is_array( $decoded ) || array() === $decoded ) {
			return null;
		}

		$rows = array();

		foreach ( $decoded as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$outcome = CoverageOutcome::from_stored( (string) ( $row['outcome'] ?? '' ) );

			if ( null === $outcome ) {
				continue;
			}

			$rows[] = array(
				'source'  => (string) ( $row['source'] ?? '' ),
				'outcome' => $outcome,
				'note'    => (string) ( $row['note'] ?? '' ),
			);
		}

		return array() === $rows ? null : new self( $rows );
	}

	/**
	 * The rows.
	 *
	 * @return array<int, array{source: string, outcome: CoverageOutcome, note: string}>
	 */
	public function rows(): array {
		return $this->rows;
	}

	/**
	 * Sources that could not be checked.
	 *
	 * @return array<int, array{source: string, outcome: CoverageOutcome, note: string}>
	 */
	public function unavailable(): array {
		return array_values(
			array_filter(
				$this->rows,
				static fn( array $row ): bool => ! $row['outcome']->was_checked()
			)
		);
	}

	/**
	 * Whether every source could be checked.
	 *
	 * @return bool
	 */
	public function is_complete(): bool {
		return array() === $this->unavailable();
	}

	/**
	 * For storage.
	 *
	 * @return string
	 */
	public function to_json(): string {
		$plain = array_map(
			static fn( array $row ): array => array(
				'source'  => $row['source'],
				'outcome' => $row['outcome']->value,
				'note'    => $row['note'],
			),
			$this->rows
		);

		return (string) wp_json_encode( $plain );
	}

	/**
	 * A one-line summary for the audit trail.
	 *
	 * @return string
	 */
	public function summary(): string {
		$checked = count( $this->rows ) - count( $this->unavailable() );

		return sprintf(
			/* translators: 1: number of sources checked, 2: total number of sources. */
			__( '%1$d of %2$d sources checked', 'vehicle-report-orders' ),
			$checked,
			count( $this->rows )
		);
	}
}
