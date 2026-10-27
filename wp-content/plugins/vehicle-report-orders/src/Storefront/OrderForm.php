<?php
/**
 * Public registration entry form.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Storefront;

use VehicleReportOrders\Audit\AuditLogger;
use VehicleReportOrders\Checkout\RegistrationField;
use VehicleReportOrders\Domain\RegistrationNumber;
use VehicleReportOrders\Domain\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Starts an order from a registration mark.
 *
 * The form does not look anything up: in this version nothing is known about a
 * vehicle until a member of staff researches it. It validates the shape of the
 * mark, remembers it for checkout, and says plainly that the report is prepared
 * by hand. If the merchant has not configured a priced product yet, it refuses
 * to start an order rather than promising something undeliverable.
 */
final class OrderForm {

	public const SHORTCODE   = 'vehicle_report_order_form';
	public const SESSION_KEY = 'vr_registration';
	private const NONCE      = 'vr_start_order';

	/**
	 * Hook the shortcode and its handler.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( self::SHORTCODE, array( $this, 'render' ) );
		add_action( 'template_redirect', array( $this, 'handle_submission' ) );
		add_filter( 'woocommerce_get_default_value_for_' . RegistrationField::BLOCK_FIELD_ID, array( $this, 'default_checkout_value' ) );
		add_filter( 'woocommerce_checkout_get_value', array( $this, 'default_classic_value' ), 10, 2 );
	}

	/**
	 * Render the form.
	 *
	 * @param array<string, mixed>|string $attributes Shortcode attributes.
	 *
	 * @return string
	 */
	public function render( $attributes = array() ): string {
		$attributes = shortcode_atts(
			array(
				'heading' => '',
				'compact' => 'no',
			),
			is_array( $attributes ) ? $attributes : array(),
			self::SHORTCODE
		);

		$missing   = Settings::missing_for_live();
		$available = array() === $missing;
		$error     = $this->current_error();
		$entered   = $this->remembered_registration();
		$price     = $this->formatted_price();
		$sla_hours = Settings::sla_hours();
		$compact   = 'yes' === $attributes['compact'];

		ob_start();
		include __DIR__ . '/../../templates/storefront/order-form.php';

		return (string) ob_get_clean();
	}

	/**
	 * Validate a submission and send the customer to checkout.
	 *
	 * @return void
	 */
	public function handle_submission(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified immediately below.
		if ( ! isset( $_POST['vr_start_order_nonce'] ) ) {
			return;
		}

		if ( ! check_admin_referer( self::NONCE, 'vr_start_order_nonce' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above.
		$entered = isset( $_POST['vr_registration'] ) ? sanitize_text_field( wp_unslash( $_POST['vr_registration'] ) ) : '';

		if ( ! RegistrationNumber::is_plausible( RegistrationNumber::normalize( $entered ) ) ) {
			$this->redirect_back( 'invalid', $entered );
		}

		if ( array() !== Settings::missing_for_live() ) {
			$this->redirect_back( 'unavailable', $entered );
		}

		$product_id = (int) Settings::get( 'report_product_id' );
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			$this->redirect_back( 'unavailable', $entered );
		}

		if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
			$this->redirect_back( 'unavailable', $entered );
		}

		// One report per order keeps the registration unambiguous.
		WC()->cart->empty_cart();
		$added = WC()->cart->add_to_cart( $product_id, 1 );

		if ( ! $added ) {
			$this->redirect_back( 'unavailable', $entered );
		}

		if ( null !== WC()->session ) {
			WC()->session->set( self::SESSION_KEY, $entered );
		}

		AuditLogger::log( 0, 'storefront.order_started', array( 'registration_valid' => true ) );

		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}

	/**
	 * Registration remembered for the checkout field.
	 *
	 * @return string
	 */
	public function remembered_registration(): string {
		if ( ! function_exists( 'WC' ) || null === WC()->session ) {
			return '';
		}

		return (string) WC()->session->get( self::SESSION_KEY, '' );
	}

	/**
	 * Pre-fill the block checkout field.
	 *
	 * @param mixed $value Current default.
	 *
	 * @return mixed
	 */
	public function default_checkout_value( $value ) {
		$remembered = $this->remembered_registration();

		return '' !== $remembered ? $remembered : $value;
	}

	/**
	 * Pre-fill the classic checkout field.
	 *
	 * @param mixed  $value Current value.
	 * @param string $input Field name.
	 *
	 * @return mixed
	 */
	public function default_classic_value( $value, $input ) {
		if ( RegistrationField::CLASSIC_FIELD !== $input ) {
			return $value;
		}

		$remembered = $this->remembered_registration();

		return '' !== $remembered ? $remembered : $value;
	}

	/**
	 * The error to show, if the last submission failed.
	 *
	 * @return string
	 */
	private function current_error(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only flag from a redirect.
		$code = isset( $_GET['vr_error'] ) ? sanitize_key( wp_unslash( $_GET['vr_error'] ) ) : '';

		return match ( $code ) {
			'invalid'     => __( 'Enter the registration as it appears on the number plate, for example AB12 CDE.', 'vehicle-report-orders' ),
			'unavailable' => __( 'Report ordering is not available right now. Please contact us and we will help.', 'vehicle-report-orders' ),
			default       => '',
		};
	}

	/**
	 * The report price as configured, or an empty string.
	 *
	 * @return string
	 */
	private function formatted_price(): string {
		$product = wc_get_product( (int) Settings::get( 'report_product_id' ) );

		if ( ! $product || '' === (string) $product->get_price() ) {
			return '';
		}

		return (string) wc_price( (float) $product->get_price() );
	}

	/**
	 * Send the customer back to the form with an explanation.
	 *
	 * @param string $code    Error code.
	 * @param string $entered What they typed, so they do not retype it.
	 *
	 * @return never
	 */
	private function redirect_back( string $code, string $entered ) {
		$referer = wp_get_referer();
		$target  = false !== $referer ? $referer : home_url( '/' );

		wp_safe_redirect(
			add_query_arg(
				array(
					'vr_error' => $code,
					'vr_entry' => rawurlencode( $entered ),
				),
				$target
			)
		);
		exit;
	}
}
