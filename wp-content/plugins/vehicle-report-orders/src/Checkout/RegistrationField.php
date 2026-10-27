<?php
/**
 * Vehicle registration input at checkout.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Checkout;

use VehicleReportOrders\Domain\RegistrationNumber;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Collects the registration mark the report is about.
 *
 * WooCommerce has two checkouts in the wild, so both are supported: the block
 * checkout through the official additional-checkout-fields API, and the classic
 * checkout through its own hooks. Only one of them renders on a given site, and
 * both end up writing the same value, so the rest of the plugin never has to
 * care which was used.
 */
final class RegistrationField {

	public const BLOCK_FIELD_ID = 'vehicle-report/registration';
	public const CLASSIC_FIELD  = 'vr_registration_number';

	/**
	 * Hook both checkout implementations.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_init', array( $this, 'register_block_field' ) );

		// Classic checkout.
		add_action( 'woocommerce_after_order_notes', array( $this, 'render_classic_field' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_classic_field' ) );
	}

	/**
	 * Register the field with the block checkout.
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
				'required'          => true,
				'attributes'        => array(
					'autocomplete'     => 'off',
					'autocapitalize'   => 'characters',
					'aria-describedby' => 'vr-registration-help',
				),
				'sanitize_callback' => static function ( $value ) {
					return is_string( $value ) ? trim( $value ) : '';
				},
				'validate_callback' => static function ( $value ) {
					$normalized = RegistrationNumber::normalize( (string) $value );

					if ( ! RegistrationNumber::is_plausible( $normalized ) ) {
						return new WP_Error(
							'vr_invalid_registration',
							__( 'Enter the vehicle registration as it appears on the number plate.', 'vehicle-report-orders' )
						);
					}

					return true;
				},
			)
		);
	}

	/**
	 * Render the classic checkout field.
	 *
	 * @param \WC_Checkout|null $checkout Checkout object.
	 *
	 * @return void
	 */
	public function render_classic_field( $checkout = null ): void {
		if ( function_exists( 'wc_current_theme_is_fse_theme' ) && did_action( 'woocommerce_blocks_checkout_enqueue_data' ) ) {
			return; // The block checkout is rendering; its own field is registered.
		}

		$value = $checkout instanceof \WC_Checkout ? $checkout->get_value( self::CLASSIC_FIELD ) : '';

		woocommerce_form_field(
			self::CLASSIC_FIELD,
			array(
				'type'              => 'text',
				'required'          => true,
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
	}

	/**
	 * Validate the classic checkout field.
	 *
	 * @return void
	 */
	public function validate_classic_field(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the checkout nonce before this hook.
		$raw = isset( $_POST[ self::CLASSIC_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::CLASSIC_FIELD ] ) ) : '';

		if ( '' === $raw ) {
			wc_add_notice( __( 'Please enter the vehicle registration so we know which vehicle to report on.', 'vehicle-report-orders' ), 'error' );

			return;
		}

		if ( ! RegistrationNumber::is_plausible( RegistrationNumber::normalize( $raw ) ) ) {
			wc_add_notice( __( 'Enter the vehicle registration as it appears on the number plate.', 'vehicle-report-orders' ), 'error' );
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
		// Block checkout stores additional fields against the order itself.
		$block_value = $order->get_meta( '_wc_other/' . self::BLOCK_FIELD_ID );

		if ( is_string( $block_value ) && '' !== trim( $block_value ) ) {
			return trim( $block_value );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the checkout nonce before order creation.
		$posted = isset( $_POST[ self::CLASSIC_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::CLASSIC_FIELD ] ) ) : '';

		return trim( $posted );
	}
}
