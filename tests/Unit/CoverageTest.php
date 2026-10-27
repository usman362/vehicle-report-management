<?php
/**
 * Coverage rules.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Tests\Unit;

use PHPUnit\Framework\TestCase;
use VehicleReportOrders\Domain\CoverageOutcome;
use VehicleReportOrders\Reports\Coverage;

/**
 * Every source answered, and "could not be checked" explained.
 */
final class CoverageTest extends TestCase {

	/**
	 * The sources a merchant has published.
	 *
	 * @var array<int, string>
	 */
	private array $sources = array( 'DVSA MOT history', 'DVLA vehicle enquiry', 'Outstanding finance' );

	/**
	 * A complete answer is accepted and keeps its order.
	 *
	 * @return void
	 */
	public function test_accepts_a_complete_answer(): void {
		$coverage = Coverage::from_input(
			array( 'found', 'none', 'found' ),
			array( '', '', '' ),
			$this->sources
		);

		$this->assertInstanceOf( Coverage::class, $coverage );
		$this->assertCount( 3, $coverage->rows() );
		$this->assertSame( 'DVLA vehicle enquiry', $coverage->rows()[1]['source'] );
		$this->assertSame( CoverageOutcome::NONE, $coverage->rows()[1]['outcome'] );
		$this->assertTrue( $coverage->is_complete() );
	}

	/**
	 * A source nobody answered stops the upload.
	 *
	 * @return void
	 */
	public function test_refuses_an_unanswered_source(): void {
		$result = Coverage::from_input(
			array( 'found', 'none' ),
			array(),
			$this->sources
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'vr_coverage_incomplete', $result->get_error_code() );
		$this->assertStringContainsString( 'Outstanding finance', $result->get_error_message() );
	}

	/**
	 * An invented outcome is not an outcome.
	 *
	 * @return void
	 */
	public function test_refuses_an_unknown_outcome(): void {
		$result = Coverage::from_input(
			array( 'found', 'probably_fine', 'found' ),
			array(),
			$this->sources
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'vr_coverage_incomplete', $result->get_error_code() );
	}

	/**
	 * "Could not be checked" has to say why.
	 *
	 * @return void
	 */
	public function test_requires_a_reason_when_a_check_was_not_possible(): void {
		$result = Coverage::from_input(
			array( 'found', 'unavailable', 'found' ),
			array( '', '   ', '' ),
			$this->sources
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'vr_coverage_reason_required', $result->get_error_code() );
		$this->assertStringContainsString( 'DVLA vehicle enquiry', $result->get_error_message() );
	}

	/**
	 * With a reason, it is accepted and reported as incomplete coverage.
	 *
	 * @return void
	 */
	public function test_accepts_an_explained_gap(): void {
		$coverage = Coverage::from_input(
			array( 'found', 'unavailable', 'none' ),
			array( '', 'The service was down all afternoon', '' ),
			$this->sources
		);

		$this->assertInstanceOf( Coverage::class, $coverage );
		$this->assertFalse( $coverage->is_complete() );
		$this->assertCount( 1, $coverage->unavailable() );
		$this->assertSame( 'The service was down all afternoon', $coverage->unavailable()[0]['note'] );
		$this->assertSame( '2 of 3 sources checked', $coverage->summary() );
	}

	/**
	 * Nothing to record against means nothing to store.
	 *
	 * @return void
	 */
	public function test_refuses_when_no_sources_are_published(): void {
		$result = Coverage::from_input( array(), array(), array() );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'vr_no_sources', $result->get_error_code() );
	}

	/**
	 * A stored answer comes back the same.
	 *
	 * @return void
	 */
	public function test_survives_a_round_trip(): void {
		$coverage = Coverage::from_input(
			array( 'found', 'unavailable', 'none' ),
			array( '', 'Not covered for this vehicle', '' ),
			$this->sources
		);

		$restored = Coverage::from_json( $coverage->to_json() );

		$this->assertInstanceOf( Coverage::class, $restored );
		$this->assertEquals( $coverage->rows(), $restored->rows() );
	}

	/**
	 * A report from before this record existed simply has none.
	 *
	 * @return void
	 */
	public function test_reads_nothing_from_an_older_report(): void {
		$this->assertNull( Coverage::from_json( '' ) );
		$this->assertNull( Coverage::from_json( '[]' ) );
		$this->assertNull( Coverage::from_json( 'not json at all' ) );
	}

	/**
	 * Only "could not be checked" means a check did not happen.
	 *
	 * @return void
	 */
	public function test_distinguishes_an_empty_record_from_an_unchecked_source(): void {
		$this->assertTrue( CoverageOutcome::NONE->was_checked() );
		$this->assertFalse( CoverageOutcome::UNAVAILABLE->was_checked() );
		$this->assertFalse( CoverageOutcome::NONE->needs_reason() );
		$this->assertTrue( CoverageOutcome::UNAVAILABLE->needs_reason() );
		$this->assertNull( CoverageOutcome::from_stored( 'clear' ) );
	}
}
