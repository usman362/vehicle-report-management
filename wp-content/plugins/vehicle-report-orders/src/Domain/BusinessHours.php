<?php
/**
 * Business-hours arithmetic for the delivery promise.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Domain;

use DateTimeImmutable;
use DateTimeZone;

defined( 'ABSPATH' ) || exit;

/**
 * Turns "within 8 business hours" into a real deadline.
 *
 * Counting calendar hours would promise a Friday-evening customer their report
 * by Saturday morning, when nobody is working. This walks the merchant's actual
 * opening hours, so the deadline shown before payment is the one staff are
 * measured against afterwards.
 */
final class BusinessHours {

	/**
	 * Open days as ISO-8601 weekday numbers (1 = Monday … 7 = Sunday).
	 *
	 * @var list<int>
	 */
	private array $days;

	/**
	 * Opening time in minutes from midnight.
	 *
	 * @var int
	 */
	private int $opens;

	/**
	 * Closing time in minutes from midnight.
	 *
	 * @var int
	 */
	private int $closes;

	/**
	 * Timezone the hours are expressed in.
	 *
	 * @var DateTimeZone
	 */
	private DateTimeZone $timezone;

	/**
	 * Constructor.
	 *
	 * @param array<int, int> $days     Open weekdays, ISO-8601 numbering.
	 * @param string          $opens    Opening time, "HH:MM".
	 * @param string          $closes   Closing time, "HH:MM".
	 * @param DateTimeZone    $timezone Timezone of the opening hours.
	 */
	public function __construct( array $days, string $opens, string $closes, DateTimeZone $timezone ) {
		$this->days = array_values(
			array_unique(
				array_filter(
					array_map( 'intval', $days ),
					static fn( int $day ): bool => $day >= 1 && $day <= 7
				)
			)
		);
		sort( $this->days );

		$this->opens    = self::to_minutes( $opens );
		$this->closes   = self::to_minutes( $closes );
		$this->timezone = $timezone;

		if ( $this->closes <= $this->opens ) {
			// A closing time before opening would never let an hour elapse.
			$this->closes = min( 1440, $this->opens + 60 );
		}
	}

	/**
	 * Parse "HH:MM" into minutes from midnight.
	 *
	 * @param string $time Time of day.
	 *
	 * @return int
	 */
	private static function to_minutes( string $time ): int {
		if ( 1 !== preg_match( '/^(\d{1,2}):(\d{2})$/', trim( $time ), $matches ) ) {
			return 9 * 60;
		}

		$hours   = min( 23, max( 0, (int) $matches[1] ) );
		$minutes = min( 59, max( 0, (int) $matches[2] ) );

		return ( $hours * 60 ) + $minutes;
	}

	/**
	 * Whether the business is open at all.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return array() !== $this->days;
	}

	/**
	 * Minutes the business is open each working day.
	 *
	 * @return int
	 */
	public function minutes_per_day(): int {
		return $this->closes - $this->opens;
	}

	/**
	 * Deadline reached after a number of open hours from a starting moment.
	 *
	 * Time outside opening hours does not count: an order placed at 23:00 on
	 * Friday starts consuming its window when the business next opens.
	 *
	 * @param DateTimeImmutable $from  Starting moment (any timezone).
	 * @param float             $hours Business hours to add.
	 *
	 * @return DateTimeImmutable|null Null when no opening days are configured.
	 */
	public function deadline_after( DateTimeImmutable $from, float $hours ): ?DateTimeImmutable {
		if ( ! $this->is_configured() || $hours <= 0 ) {
			return null;
		}

		$cursor    = $from->setTimezone( $this->timezone );
		$remaining = (int) round( $hours * 60 );
		$guard     = 0;

		while ( $remaining > 0 && $guard < 400 ) {
			++$guard;

			if ( ! $this->is_open_on( $cursor ) ) {
				$cursor = $this->next_opening( $cursor );

				continue;
			}

			$minutes_today = (int) $cursor->format( 'G' ) * 60 + (int) $cursor->format( 'i' );

			if ( $minutes_today < $this->opens ) {
				$cursor = $this->at_minutes( $cursor, $this->opens );

				continue;
			}

			if ( $minutes_today >= $this->closes ) {
				$cursor = $this->next_opening( $cursor );

				continue;
			}

			$available = $this->closes - $minutes_today;

			if ( $available >= $remaining ) {
				return $cursor->modify( sprintf( '+%d minutes', $remaining ) );
			}

			$remaining -= $available;
			$cursor     = $this->next_opening( $cursor );
		}

		return $cursor;
	}

	/**
	 * Whether a given day is an opening day.
	 *
	 * @param DateTimeImmutable $moment Moment to test.
	 *
	 * @return bool
	 */
	private function is_open_on( DateTimeImmutable $moment ): bool {
		return in_array( (int) $moment->format( 'N' ), $this->days, true );
	}

	/**
	 * Opening time of the next working day after the given moment.
	 *
	 * @param DateTimeImmutable $moment Current moment.
	 *
	 * @return DateTimeImmutable
	 */
	private function next_opening( DateTimeImmutable $moment ): DateTimeImmutable {
		$next = $this->at_minutes( $moment, $this->opens )->modify( '+1 day' );

		for ( $attempt = 0; $attempt < 8; $attempt++ ) {
			if ( $this->is_open_on( $next ) ) {
				return $next;
			}

			$next = $next->modify( '+1 day' );
		}

		return $next;
	}

	/**
	 * The same date at a given number of minutes past midnight.
	 *
	 * @param DateTimeImmutable $moment  Date to keep.
	 * @param int               $minutes Minutes from midnight.
	 *
	 * @return DateTimeImmutable
	 */
	private function at_minutes( DateTimeImmutable $moment, int $minutes ): DateTimeImmutable {
		return $moment->setTime( intdiv( $minutes, 60 ), $minutes % 60, 0 );
	}

	/**
	 * Human description such as "Monday to Friday, 9:00am–5:00pm".
	 *
	 * @param array<int, string> $day_names Weekday names keyed 1-7.
	 *
	 * @return string
	 */
	public function describe( array $day_names ): string {
		if ( ! $this->is_configured() ) {
			return '';
		}

		$names      = array_map( static fn( int $day ): string => $day_names[ $day ] ?? (string) $day, $this->days );
		$contiguous = count( $this->days ) > 1 && ( (int) end( $this->days ) - (int) reset( $this->days ) ) === count( $this->days ) - 1;
		$days       = $contiguous
			? sprintf( '%s–%s', (string) reset( $names ), (string) end( $names ) )
			: implode( ', ', $names );

		return sprintf( '%s, %s–%s', $days, $this->format_minutes( $this->opens ), $this->format_minutes( $this->closes ) );
	}

	/**
	 * Minutes from midnight as "H:MM".
	 *
	 * @param int $minutes Minutes from midnight.
	 *
	 * @return string
	 */
	private function format_minutes( int $minutes ): string {
		return sprintf( '%d:%02d', intdiv( $minutes, 60 ), $minutes % 60 );
	}
}
