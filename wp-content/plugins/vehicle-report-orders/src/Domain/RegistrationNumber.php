<?php
/**
 * UK vehicle registration mark.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Domain;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * A registration mark as entered by a customer.
 *
 * The customer's typing is kept exactly as entered for the audit trail, while a
 * normalised form is stored for matching. Validation is deliberately permissive:
 * current, prefix, suffix, dateless and cherished marks all exist, so a strict
 * "AB12 CDE" pattern would reject legitimate customers. A mark that looks well
 * formed still proves nothing about a vehicle existing — that is established
 * only by a real data source during manual fulfilment.
 */
final class RegistrationNumber {

	/**
	 * Shortest mark accepted.
	 *
	 * @var int
	 */
	public const MIN_LENGTH = 2;

	/**
	 * Longest mark accepted.
	 *
	 * @var int
	 */
	public const MAX_LENGTH = 8;

	/**
	 * Constructor.
	 *
	 * @param string $entered    Raw customer input, trimmed only.
	 * @param string $normalized Uppercase alphanumeric matching key.
	 */
	private function __construct(
		private readonly string $entered,
		private readonly string $normalized,
	) {}

	/**
	 * Build a mark from customer input.
	 *
	 * @param string $input Raw input from the order form.
	 *
	 * @return self
	 * @throws InvalidArgumentException When the input cannot be a UK mark.
	 */
	public static function from_input( string $input ): self {
		$entered    = trim( $input );
		$normalized = self::normalize( $entered );

		if ( ! self::is_plausible( $normalized ) ) {
			throw new InvalidArgumentException( 'Registration mark is not a plausible UK plate.' );
		}

		return new self( $entered, $normalized );
	}

	/**
	 * Reduce input to uppercase A-Z and 0-9; separators are presentation only.
	 *
	 * @param string $input Raw input.
	 *
	 * @return string
	 */
	public static function normalize( string $input ): string {
		$upper = strtoupper( trim( $input ) );

		return (string) preg_replace( '/[^A-Z0-9]/', '', $upper );
	}

	/**
	 * Whether a normalised string could be a UK registration mark.
	 *
	 * @param string $normalized Normalised candidate.
	 *
	 * @return bool
	 */
	public static function is_plausible( string $normalized ): bool {
		$length = strlen( $normalized );

		if ( $length < self::MIN_LENGTH || $length > self::MAX_LENGTH ) {
			return false;
		}

		if ( 1 !== preg_match( '/^[A-Z0-9]+$/', $normalized ) ) {
			return false;
		}

		// Every UK format mixes letters with numbers; "ABCDEFG" alone is not a mark.
		return 1 === preg_match( '/[A-Z]/', $normalized ) && 1 === preg_match( '/[0-9]/', $normalized );
	}

	/**
	 * Exactly what the customer typed, for the order record and disputes.
	 *
	 * @return string
	 */
	public function entered(): string {
		return $this->entered;
	}

	/**
	 * Matching key: uppercase, no separators.
	 *
	 * @return string
	 */
	public function normalized(): string {
		return $this->normalized;
	}

	/**
	 * Spaced form for emails and the admin queue.
	 *
	 * Current-style marks are split 4 + 3 ("AB12CDE" becomes "AB12 CDE");
	 * anything else is shown as entered, because inventing a grouping for a
	 * dateless mark would be wrong.
	 *
	 * @return string
	 */
	public function for_display(): string {
		if ( 1 === preg_match( '/^[A-Z]{2}[0-9]{2}[A-Z]{3}$/', $this->normalized ) ) {
			return substr( $this->normalized, 0, 4 ) . ' ' . substr( $this->normalized, 4 );
		}

		return '' !== $this->entered ? $this->entered : $this->normalized;
	}

	/**
	 * Partially hidden form for notifications that may be forwarded.
	 *
	 * @return string
	 */
	public function masked(): string {
		$length = strlen( $this->normalized );

		if ( $length <= 3 ) {
			return str_repeat( '•', $length );
		}

		return substr( $this->normalized, 0, 2 ) . str_repeat( '•', $length - 3 ) . substr( $this->normalized, -1 );
	}
}
