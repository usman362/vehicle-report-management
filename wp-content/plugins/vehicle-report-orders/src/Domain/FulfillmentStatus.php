<?php
/**
 * Report fulfilment state machine.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * Where a paid order stands in manual report preparation.
 *
 * This is deliberately separate from the WooCommerce payment status: money
 * received and report delivered are different facts, and conflating them is how
 * a customer ends up told their report is ready when nobody has produced it.
 * Only `delivered` may ever be shown to a customer as a completed report.
 */
enum FulfillmentStatus: string {

	case NOT_READY           = 'not_ready';
	case AWAITING_STAFF      = 'awaiting_staff';
	case IN_PROGRESS         = 'in_progress';
	case NEEDS_CUSTOMER_INFO = 'needs_customer_info';
	case REPORT_UPLOADED     = 'report_uploaded';
	case DELIVERED           = 'delivered';
	case DELIVERY_FAILED     = 'delivery_failed';
	case CANNOT_FULFILL      = 'cannot_fulfill';
	case CANCELLED           = 'cancelled';

	/**
	 * Transitions the application is allowed to make.
	 *
	 * Anything not listed here is a bug or a manual database edit, and the
	 * audit trail records the attempt rather than silently accepting it.
	 *
	 * @return array<string, list<self>>
	 */
	public static function transitions(): array {
		return array(
			self::NOT_READY->value           => array( self::AWAITING_STAFF, self::CANCELLED ),
			self::AWAITING_STAFF->value      => array( self::IN_PROGRESS, self::NEEDS_CUSTOMER_INFO, self::CANNOT_FULFILL, self::CANCELLED ),
			self::IN_PROGRESS->value         => array( self::NEEDS_CUSTOMER_INFO, self::REPORT_UPLOADED, self::CANNOT_FULFILL, self::AWAITING_STAFF, self::CANCELLED ),
			self::NEEDS_CUSTOMER_INFO->value => array( self::IN_PROGRESS, self::CANNOT_FULFILL, self::CANCELLED ),
			self::REPORT_UPLOADED->value     => array( self::DELIVERED, self::DELIVERY_FAILED, self::IN_PROGRESS, self::CANCELLED ),
			self::DELIVERED->value           => array( self::REPORT_UPLOADED, self::DELIVERY_FAILED ),
			self::DELIVERY_FAILED->value     => array( self::DELIVERED, self::REPORT_UPLOADED, self::CANNOT_FULFILL ),
			self::CANNOT_FULFILL->value      => array( self::AWAITING_STAFF, self::CANCELLED ),
			self::CANCELLED->value           => array(),
		);
	}

	/**
	 * Whether a move from this state to another is permitted.
	 *
	 * @param self $target Proposed next state.
	 *
	 * @return bool
	 */
	public function can_move_to( self $target ): bool {
		if ( $this === $target ) {
			return false;
		}

		return in_array( $target, self::transitions()[ $this->value ], true );
	}

	/**
	 * States from which no further work happens on its own.
	 *
	 * @return bool
	 */
	public function is_final(): bool {
		return self::CANCELLED === $this;
	}

	/**
	 * Whether staff still owe the customer something.
	 *
	 * @return bool
	 */
	public function is_outstanding(): bool {
		return in_array(
			$this,
			array( self::AWAITING_STAFF, self::IN_PROGRESS, self::NEEDS_CUSTOMER_INFO, self::REPORT_UPLOADED, self::DELIVERY_FAILED ),
			true
		);
	}

	/**
	 * Whether the customer may be given access to a report file.
	 *
	 * Uploading a file is not delivery: a manager may still be reviewing it.
	 *
	 * @return bool
	 */
	public function grants_customer_access(): bool {
		return self::DELIVERED === $this;
	}

	/**
	 * Translated label for admin screens and emails.
	 *
	 * @return string
	 */
	public function label(): string {
		return match ( $this ) {
			self::NOT_READY           => __( 'Not ready', 'vehicle-report-orders' ),
			self::AWAITING_STAFF      => __( 'Awaiting staff', 'vehicle-report-orders' ),
			self::IN_PROGRESS         => __( 'In progress', 'vehicle-report-orders' ),
			self::NEEDS_CUSTOMER_INFO => __( 'Needs customer information', 'vehicle-report-orders' ),
			self::REPORT_UPLOADED     => __( 'Report uploaded', 'vehicle-report-orders' ),
			self::DELIVERED           => __( 'Delivered', 'vehicle-report-orders' ),
			self::DELIVERY_FAILED     => __( 'Delivery failed', 'vehicle-report-orders' ),
			self::CANNOT_FULFILL      => __( 'Cannot fulfil', 'vehicle-report-orders' ),
			self::CANCELLED           => __( 'Cancelled', 'vehicle-report-orders' ),
		};
	}

	/**
	 * Resolve a stored string, falling back to the starting state.
	 *
	 * @param string|null $value Stored value.
	 *
	 * @return self
	 */
	public static function from_stored( ?string $value ): self {
		return self::tryFrom( (string) $value ) ?? self::NOT_READY;
	}
}
