<?php
/**
 * Vehicle identification inputs at checkout.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Checkout;

use VehicleReportOrders\Domain\RegistrationNumber;
use VehicleReportOrders\Domain\Settings;
use VehicleReportOrders\Domain\VehicleType;
use VehicleReportOrders\Domain\Vin;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Collects which vehicle the report is about.
 *
 * A customer buying a UK car has a registration; someone buying an import, or a
 * vehicle between plates, may only have a VIN. Either identifies the vehicle, so
 * each field is optional on its own and the pair is validated together: a
 * checkout with neither is refused.
 *
 * WooCommerce has two checkouts in the wild, so both are supported: the block
 * checkout through the official additional-checkout-fields API, and the classic
 * checkout through its own hooks. Only one of them renders on a given site, and
 * both end up writing the same values, so the rest of the plugin never has to
 * care which was used.
 */
final class RegistrationField {

	public const BLOCK_FIELD_ID = 'vehicle-report/registration';
	public const BLOCK_VIN_ID   = 'vehicle-report/vin';
	public const BLOCK_TYPE_ID  = 'vehicle-report/vehicle-type';

	public const CLASSIC_FIELD = 'vr_registration_number';
	public const CLASSIC_VIN   = 'vr_vin';
	public const CLASSIC_TYPE  = 'vr_vehicle_type';

	/**
	 * Hook both checkout implementations.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_init', array( $this, 'register_block_field' ) );
		add_action( 'woocommerce_blocks_validate_location_order_fields', array( $this, 'validate_block_fields' ), 10, 2 );

		// Classic checkout.
		add_action( 'woocommerce_after_order_notes', array( $this, 'render_classic_field' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_classic_field' ) );
	}

	/**
	 * Register the fields with the block checkout.
	 *
	 * @return void
	 */
	public function register_block_field(): void {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		woocommerce_register_additional_checkout_field(
			array(
				'id'                => self::BLOCK_FIELD_ID,
				'label'             => __( 'Vehicle registration', 'vehicle-report-orders' ),
				'optionalLabel'     => __( 'Vehicle registration', 'vehicle-report-orders' ),
				'location'          => 'order',
				'type'              => 'text',
				'required'          => false,
				// WooCommerce spreads these onto the input itself, so they use
				// the HTML attribute spelling. React warns about the lower-case
				// names in the console, but that warning comes from WooCommerce's
				// own fields as well, and camelCase here is simply ignored.
				'attributes'        => array(
					'autocomplete'     => 'off',
					'autocapitalize'   => 'characters',
					'aria-describedby' => 'vr-registration-help',
				),
				'sanitize_callback' => static fn( $value ): string => is_string( $value ) ? trim( $value ) : '',
				'validate_callback' => static function ( $value ) {
					$entered = trim( (string) $value );

					if ( '' === $entered ) {
						return true; // The pair is validated together.
					}

					if ( ! RegistrationNumber::is_plausible( RegistrationNumber::normalize( $entered ) ) ) {
						return new WP_Error(
							'vr_invalid_registration',
							__( 'Enter the vehicle registration as it appears on the number plate.', 'vehicle-report-orders' )
						);
					}

					return true;
				},
			)
		);

		if ( Settings::accepts_vin() ) {
			woocommerce_register_additional_checkout_field(
				array(
					'id'                => self::BLOCK_VIN_ID,
					'label'             => __( 'VIN', 'vehicle-report-orders' ),
					'optionalLabel'     => __( 'VIN (if you have no UK registration)', 'vehicle-report-orders' ),
					'location'          => 'order',
					'type'              => 'text',
					'required'          => false,
					// WooCommerce rejects maxlength here, so the length is left to
					// validation, which has to run server-side regardless.
					'attributes'        => array(
						'autocomplete'   => 'off',
						'autocapitalize' => 'characters',
					),
					'sanitize_callback' => static fn( $value ): string => is_string( $value ) ? trim( $value ) : '',
					'validate_callback' => static function ( $value ) {
						$entered = trim( (string) $value );

						if ( '' === $entered ) {
							return true;
						}

						if ( ! Vin::is_plausible( Vin::normalize( $entered ) ) ) {
							return new WP_Error(
								'vr_invalid_vin',
								__( 'A VIN is 17 characters and does not contain the letters I, O or Q.', 'vehicle-report-orders' )
							);
						}

						return true;
					},
				)
			);
		}

		$types = VehicleType::offered();

		if ( array() !== $types ) {
			woocommerce_register_additional_checkout_field(
				array(
					'id'       => self::BLOCK_TYPE_ID,
					'label'    => __( 'Kind of vehicle', 'vehicle-report-orders' ),
					'location' => 'order',
					'type'     => 'select',
					'required' => true,
					'options'  => array_map(
						static fn( string $slug, string $label ): array => array(
							'value' => $slug,
							'label' => $label,
						),
						array_keys( $types ),
						array_values( $types )
					),
				)
			);
		}
	}

	/**
	 * Refuse a block checkout that names no vehicle at all.
	 *
	 * @param WP_Error             $errors Collected errors.
	 * @param array<string, mixed> $fields Submitted order fields.
	 *
	 * @return void
	 */
	public function validate_block_fields( $errors, $fields ): void {
		if ( ! $errors instanceof WP_Error || ! is_array( $fields ) ) {
			return;
		}

		$registration = trim( (string) ( $fields[ self::BLOCK_FIELD_ID ] ?? '' ) );
		$vin          = trim( (string) ( $fields[ self::BLOCK_VIN_ID ] ?? '' ) );

		if ( '' === $registration && '' === $vin ) {
			$errors->add( 'vr_vehicle_missing', $this->missing_identifier_message() );
		}
	}

	/**
	 * Render the classic checkout fields.
	 *
	 * @param \WC_Checkout|null $checkout Checkout object.
	 *
	 * @return void
	 */
	public function render_classic_field( $checkout = null ): void {
		if ( function_exists( 'wc_current_theme_is_fse_theme' ) && did_action( 'woocommerce_blocks_checkout_enqueue_data' ) ) {
			return; // The block checkout is rendering; its own fields are registered.
		}

		$value = $checkout instanceof \WC_Checkout ? $checkout->get_value( self::CLASSIC_FIELD ) : '';

		woocommerce_form_field(
			self::CLASSIC_FIELD,
			array(
				'type'              => 'text',
				'required'          => false,
				'class'             => array( 'form-row-wide', 'vr-registration-field' ),
				'label'             => __( 'Vehicle registration', 'vehicle-report-orders' ),
				'placeholder'       => __( 'AB12 CDE', 'vehicle-report-orders' ),
				'description'       => __( 'The registration of the vehicle this report is about. We check it by hand before the report is prepared.', 'vehicle-report-orders' ),
				'custom_attributes' => array(
					'autocomplete'   => 'off',
					'autocapitalize' => 'characters',
					'maxlength'      => '12',
				),
			),
			(string) $value
		);

		if ( Settings::accepts_vin() ) {
			$vin_value = $checkout instanceof \WC_Checkout ? $checkout->get_value( self::CLASSIC_VIN ) : '';

			woocommerce_form_field(
				self::CLASSIC_VIN,
				array(
					'type'              => 'text',
					'required'          => false,
					'class'             => array( 'form-row-wide', 'vr-vin-field' ),
					'label'             => __( 'VIN', 'vehicle-report-orders' ),
					'description'       => __( 'Give this instead of a registration for an import, or a vehicle with no current UK plate.', 'vehicle-report-orders' ),
					'custom_attributes' => array(
						'autocomplete'   => 'off',
						'autocapitalize' => 'characters',
						'maxlength'      => (string) Vin::LENGTH,
					),
				),
				(string) $vin_value
			);
		}

		$types = VehicleType::offered();

		if ( array() !== $types ) {
			$type_value = $checkout instanceof \WC_Checkout ? $checkout->get_value( self::CLASSIC_TYPE ) : '';

			woocommerce_form_field(
				self::CLASSIC_TYPE,
				array(
					'type'     => 'select',
					'required' => true,
					'class'    => array( 'form-row-wide', 'vr-vehicle-type-field' ),
					'label'    => __( 'Kind of vehicle', 'vehicle-report-orders' ),
					'options'  => array( '' => __( 'Choose one', 'vehicle-report-orders' ) ) + $types,
				),
				(string) $type_value
			);
		}
	}

	/**
	 * Validate the classic checkout fields.
	 *
	 * @return void
	 */
	public function validate_classic_field(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the checkout nonce before this hook.
		$registration = isset( $_POST[ self::CLASSIC_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::CLASSIC_FIELD ] ) ) : '';
		$vin          = isset( $_POST[ self::CLASSIC_VIN ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::CLASSIC_VIN ] ) ) : '';
		$type         = isset( $_POST[ self::CLASSIC_TYPE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::CLASSIC_TYPE ] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( '' === $registration && '' === $vin ) {
			wc_add_notice( $this->missing_identifier_message(), 'error' );
		}

		if ( '' !== $registration && ! RegistrationNumber::is_plausible( RegistrationNumber::normalize( $registration ) ) ) {
			wc_add_notice( __( 'Enter the vehicle registration as it appears on the number plate.', 'vehicle-report-orders' ), 'error' );
		}

		if ( '' !== $vin && ! Vin::is_plausible( Vin::normalize( $vin ) ) ) {
			wc_add_notice( __( 'A VIN is 17 characters and does not contain the letters I, O or Q.', 'vehicle-report-orders' ), 'error' );
		}

		if ( array() !== VehicleType::offered() && ! VehicleType::is_offered( $type ) ) {
			wc_add_notice( __( 'Choose the kind of vehicle this report is about.', 'vehicle-report-orders' ), 'error' );
		}
	}

	/**
	 * The registration submitted with the current request, whichever checkout
	 * produced it.
	 *
	 * @param \WC_Order $order Order being created.
	 *
	 * @return string Raw entered value, or an empty string.
	 */
	public static function submitted_value( \WC_Order $order ): string {
		return self::submitted( $order, self::BLOCK_FIELD_ID, self::CLASSIC_FIELD );
	}

	/**
	 * The VIN submitted with the current request.
	 *
	 * @param \WC_Order $order Order being created.
	 *
	 * @return string
	 */
	public static function submitted_vin( \WC_Order $order ): string {
		return self::submitted( $order, self::BLOCK_VIN_ID, self::CLASSIC_VIN );
	}

	/**
	 * The vehicle type submitted with the current request.
	 *
	 * @param \WC_Order $order Order being created.
	 *
	 * @return string
	 */
	public static function submitted_type( \WC_Order $order ): string {
		return self::submitted( $order, self::BLOCK_TYPE_ID, self::CLASSIC_TYPE );
	}

	/**
	 * One submitted value, from whichever checkout is in play.
	 *
	 * @param \WC_Order $order    Order being created.
	 * @param string    $block_id Block field id.
	 * @param string    $classic  Classic field name.
	 *
	 * @return string
	 */
	private static function submitted( \WC_Order $order, string $block_id, string $classic ): string {
		// Block checkout stores additional fields against the order itself.
		$block_value = $order->get_meta( '_wc_other/' . $block_id );

		if ( is_string( $block_value ) && '' !== trim( $block_value ) ) {
			return trim( $block_value );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the checkout nonce before order creation.
		$posted = isset( $_POST[ $classic ] ) ? sanitize_text_field( wp_unslash( $_POST[ $classic ] ) ) : '';

		return trim( $posted );
	}

	/**
	 * Wording used by both checkouts when no vehicle was named.
	 *
	 * @return string
	 */
	private function missing_identifier_message(): string {
		return Settings::accepts_vin()
			? __( 'Enter the vehicle registration, or a VIN if the vehicle has no current UK plate, so we know which vehicle to report on.', 'vehicle-report-orders' )
			: __( 'Please enter the vehicle registration so we know which vehicle to report on.', 'vehicle-report-orders' );
	}
}
