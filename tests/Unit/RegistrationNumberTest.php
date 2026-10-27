<?php

declare( strict_types=1 );

namespace Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use VehicleReportOrders\Domain\RegistrationNumber;

final class RegistrationNumberTest extends TestCase {

	/**
	 * @dataProvider plausibleMarks
	 */
	public function test_it_normalizes_what_the_customer_typed( string $input, string $expected ): void {
		$registration = RegistrationNumber::from_input( $input );

		$this->assertSame( $expected, $registration->normalized() );
		$this->assertSame( trim( $input ), $registration->entered(), 'The original input is kept for the audit trail.' );
	}

	public static function plausibleMarks(): array {
		return [
			'current style'      => [ 'AB12 CDE', 'AB12CDE' ],
			'lower case'         => [ 'ab12cde', 'AB12CDE' ],
			'prefix style'       => [ 'A123 BCD', 'A123BCD' ],
			'suffix style'       => [ 'ABC 123A', 'ABC123A' ],
			'dateless cherished' => [ '1 ABC', '1ABC' ],
			'hyphen and dots'    => [ 'a.b-12.cde', 'AB12CDE' ],
			'padded whitespace'  => [ '  AB12CDE  ', 'AB12CDE' ],
		];
	}

	/**
	 * @dataProvider implausibleMarks
	 */
	public function test_it_rejects_input_that_cannot_be_a_plate( string $input ): void {
		$this->expectException( InvalidArgumentException::class );

		RegistrationNumber::from_input( $input );
	}

	public static function implausibleMarks(): array {
		return [
			'empty'          => [ '' ],
			'only spaces'    => [ '   ' ],
			'letters only'   => [ 'ABCDE' ],
			'digits only'    => [ '12345' ],
			'too long'       => [ 'AB12CDEFG' ],
			'single char'    => [ 'A' ],
			'script payload' => [ '<script>alert(1)</script>' ],
		];
	}

	public function test_it_spaces_only_the_current_style_mark(): void {
		$this->assertSame( 'AB12 CDE', RegistrationNumber::from_input( 'ab12cde' )->for_display() );
		// A dateless mark has no standard grouping, so it is shown as entered.
		$this->assertSame( '1 ABC', RegistrationNumber::from_input( '1 ABC' )->for_display() );
	}

	public function test_it_masks_the_mark_for_forwardable_messages(): void {
		$this->assertSame( 'AB••••E', RegistrationNumber::from_input( 'AB12 CDE' )->masked() );
		$this->assertSame( '1A••C', RegistrationNumber::from_input( '1A99C' )->masked() );
	}

	public function test_normalize_is_usable_before_validation(): void {
		$this->assertSame( 'AB12CDE', RegistrationNumber::normalize( ' ab-12 cde ' ) );
		$this->assertFalse( RegistrationNumber::is_plausible( RegistrationNumber::normalize( 'hello world' ) ) );
	}
}
