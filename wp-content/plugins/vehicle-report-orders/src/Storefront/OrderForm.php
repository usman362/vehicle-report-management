<?php
/**
 * Public order entry form.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Storefront;

use VehicleReportOrders\Audit\AuditLogger;
use VehicleReportOrders\Checkout\RegistrationField;
use VehicleReportOrders\Domain\Package;
use VehicleReportOrders\Domain\PackageRepository;
use VehicleReportOrders\Domain\RegistrationNumber;
use VehicleReportOrders\Domain\Settings;
use VehicleReportOrders\Domain\VehicleType;
use VehicleReportOrders\Domain\Vin;

defined( 'ABSPATH' ) || exit;

/**
 * Starts an order from a registration mark or a VIN.
 *
 * The form does not look anything up: in this version nothing is known about a
 * vehicle until a member of staff researches it. It validates the shape of what
 * was entered, remembers it for checkout, and says plainly that the report is
 * prepared by hand. If the merchant has not configured a priced package yet, it
 * refuses to start an order rather than promising something undeliverable.
 */
final class OrderForm {

	public const SHORTCODE       = 'vehicle_report_order_form';
	public const SESSION_KEY     = 'vr_registration';
	public const SESSION_VIN     = 'vr_vin';
	public const SESSION_TYPE    = 'vr_vehicle_type';
	public const SESSION_PACKAGE = 'vr_package';
	private const NONCE          = 'vr_start_order';

	/**
	 * Hook the shortcode and its handler.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( self::SHORTCODE, array( $this, 'render' ) );
		add_action( 'template_redirect', array( $this, 'handle_submission' ) );
		add_filter( 'woocommerce_get_default_value_for_' . RegistrationField::BLOCK_FIELD_ID, array( $this, 'default_checkout_value' ) );
		add_filter( 'woocommerce_get_default_value_for_' . RegistrationField::BLOCK_VIN_ID, array( $this, 'default_vin_value' ) );
		add_filter( 'woocommerce_get_default_value_for_' . RegistrationField::BLOCK_TYPE_ID, array( $this, 'default_type_value' ) );
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
				'package' => '',
			),
			is_array( $attributes ) ? $attributes : array(),
			self::SHORTCODE
		);

		$missing    = Settings::missing_for_live();
		$available  = array() === $missing;
		$error      = $this->current_error();
		$entered    = $this->remembered( self::SESSION_KEY );
		$vin        = $this->remembered( self::SESSION_VIN );
		$packages   = PackageRepository::all();
		$chosen     = $this->chosen_package_slug( (string) $attributes['package'] );
		$types      = VehicleType::offered();
		$type       = $this->remembered( self::SESSION_TYPE );
		$accept_vin = Settings::accepts_vin();
		$price      = $this->formatted_price();
		$sla_hours  = Settings::sla_hours();
		$compact    = 'yes' === $attributes['compact'];
		$heading    = (string) $attributes['heading'];
		$locked     = '' !== (string) $attributes['package'];

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

		$entered = $this->posted( 'vr_registration' );
		$vin     = $this->posted( 'vr_vin' );
		$type    = $this->posted( 'vr_vehicle_type' );
		$slug    = $this->posted( 'vr_package' );
		$using   = 'vin' === $this->posted( 'vr_identifier' ) ? 'vin' : 'registration';

		if ( 'vin' === $using && Settings::accepts_vin() ) {
			if ( ! Vin::is_plausible( Vin::normalize( $vin ) ) ) {
				$this->redirect_back( 'invalid_vin', $entered, $vin );
			}

			$entered = '';
		} else {
			if ( ! RegistrationNumber::is_plausible( RegistrationNumber::normalize( $entered ) ) ) {
				$this->redirect_back( 'invalid', $entered, $vin );
			}

			$vin = '';
		}

		$types = VehicleType::offered();

		if ( array() !== $types && ! isset( $types[ $type ] ) ) {
			$this->redirect_back( 'vehicle_type', $entered, $vin );
		}

		if ( array() !== Settings::missing_for_live() ) {
			$this->redirect_back( 'unavailable', $entered, $vin );
		}

		$package = $this->resolve_package( $slug );

		if ( null === $package ) {
			$this->redirect_back( 'unavailable', $entered, $vin );
		}

		if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
			$this->redirect_back( 'unavailable', $entered, $vin );
		}

		// One report per order keeps the vehicle unambiguous.
		WC()->cart->empty_cart();
		$added = WC()->cart->add_to_cart( $package->product_id(), 1 );

		if ( ! $added ) {
			$this->redirect_back( 'unavailable', $entered, $vin );
		}

		$this->remember( self::SESSION_KEY, $entered );
		$this->remember( self::SESSION_VIN, $vin );
		$this->remember( self::SESSION_TYPE, $type );
		$this->remember( self::SESSION_PACKAGE, $package->slug() );

		AuditLogger::log(
			0,
			'storefront.order_started',
			array(
				'identifier' => $using,
				'package'    => $package->slug(),
			)
		);

		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}

	/**
	 * Registration remembered for the checkout field.
	 *
	 * @return string
	 */
	public function remembered_registration(): string {
		return $this->remembered( self::SESSION_KEY );
	}

	/**
	 * The package the visitor chose, when it is still sellable.
	 *
	 * @return Package|null
	 */
	public function remembered_package(): ?Package {
		$slug = $this->remembered( self::SESSION_PACKAGE );

		if ( '' === $slug ) {
			return null;
		}

		$package = PackageRepository::find( $slug );

		return $package && $package->is_purchasable() ? $package : null;
	}

	/**
	 * Pre-fill the block checkout registration field.
	 *
	 * @param mixed $value Current default.
	 *
	 * @return mixed
	 */
	public function default_checkout_value( $value ) {
		$remembered = $this->remembered( self::SESSION_KEY );

		return '' !== $remembered ? $remembered : $value;
	}

	/**
	 * Pre-fill the block checkout VIN field.
	 *
	 * @param mixed $value Current default.
	 *
	 * @return mixed
	 */
	public function default_vin_value( $value ) {
		$remembered = $this->remembered( self::SESSION_VIN );

		return '' !== $remembered ? $remembered : $value;
	}

	/**
	 * Pre-fill the block checkout vehicle type field.
	 *
	 * @param mixed $value Current default.
	 *
	 * @return mixed
	 */
	public function default_type_value( $value ) {
		$remembered = $this->remembered( self::SESSION_TYPE );

		return '' !== $remembered ? $remembered : $value;
	}

	/**
	 * Pre-fill the classic checkout fields.
	 *
	 * @param mixed  $value Current value.
	 * @param string $input Field name.
	 *
	 * @return mixed
	 */
	public function default_classic_value( $value, $input ) {
		$remembered = match ( $input ) {
			RegistrationField::CLASSIC_FIELD => $this->remembered( self::SESSION_KEY ),
			RegistrationField::CLASSIC_VIN   => $this->remembered( self::SESSION_VIN ),
			RegistrationField::CLASSIC_TYPE  => $this->remembered( self::SESSION_TYPE ),
			default                          => '',
		};

		return '' !== $remembered ? $remembered : $value;
	}

	/**
	 * The package to add to the cart.
	 *
	 * @param string $slug Requested tier.
	 *
	 * @return Package|null
	 */
	private function resolve_package( string $slug ): ?Package {
		$package = '' !== $slug ? PackageRepository::find( $slug ) : null;

		if ( $package && $package->is_purchasable() ) {
			return $package;
		}

		return PackageRepository::cheapest();
	}

	/**
	 * Which tier the form should show as selected.
	 *
	 * @param string $forced Tier fixed by the shortcode attribute.
	 *
	 * @return string
	 */
	private function chosen_package_slug( string $forced ): string {
		if ( '' !== $forced && null !== PackageRepository::find( $forced ) ) {
			return $forced;
		}

		$remembered = $this->remembered( self::SESSION_PACKAGE );

		if ( '' !== $remembered && null !== PackageRepository::find( $remembered ) ) {
			return $remembered;
		}

		$cheapest = PackageRepository::cheapest();

		if ( null !== $cheapest ) {
			return $cheapest->slug();
		}

		$all = PackageRepository::all();

		return array() !== $all ? $all[0]->slug() : '';
	}

	/**
	 * A sanitised posted value.
	 *
	 * @param string $key Field name.
	 *
	 * @return string
	 */
	private function posted( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The caller verifies the nonce first.
		return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}

	/**
	 * Read one remembered value from the WooCommerce session.
	 *
	 * @param string $key Session key.
	 *
	 * @return string
	 */
	private function remembered( string $key ): string {
		if ( ! function_exists( 'WC' ) || null === WC()->session ) {
			return '';
		}

		return (string) WC()->session->get( $key, '' );
	}

	/**
	 * Write one value to the WooCommerce session.
	 *
	 * @param string $key   Session key.
	 * @param string $value Value to keep.
	 *
	 * @return void
	 */
	private function remember( string $key, string $value ): void {
		if ( ! function_exists( 'WC' ) || null === WC()->session ) {
			return;
		}

		WC()->session->set( $key, $value );
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
			'invalid'      => __( 'Enter the registration as it appears on the number plate, for example AB12 CDE.', 'vehicle-report-orders' ),
			'invalid_vin'  => __( 'A VIN is 17 characters long and does not contain the letters I, O or Q. It is on the V5C logbook and usually stamped on the vehicle.', 'vehicle-report-orders' ),
			'vehicle_type' => __( 'Choose the kind of vehicle this report is about.', 'vehicle-report-orders' ),
			'unavailable'  => __( 'Report ordering is not available right now. Please contact us and we will help.', 'vehicle-report-orders' ),
			default        => '',
		};
	}

	/**
	 * The entry price across the published packages, or an empty string.
	 *
	 * @return string
	 */
	private function formatted_price(): string {
		$cheapest = PackageRepository::cheapest();

		return null !== $cheapest ? $cheapest->formatted_price() : '';
	}

	/**
	 * The page to come back to after a redirect.
	 *
	 * `wp_get_referer()` returns false when the referer matches the request,
	 * which is exactly what happens when a form posts back to its own page, so
	 * relying on it alone dropped the visitor on the front page.
	 *
	 * @return string
	 */
	private function return_url(): string {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised on the same line.
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( '' !== $uri ) {
			return home_url( $uri );
		}

		$referer = wp_get_referer();

		return false !== $referer ? $referer : home_url( '/' );
	}

	/**
	 * Send the customer back to the form with an explanation.
	 *
	 * @param string $code    Error code.
	 * @param string $entered Registration they typed, so they do not retype it.
	 * @param string $vin     VIN they typed.
	 *
	 * @return never
	 */
	private function redirect_back( string $code, string $entered, string $vin = '' ) {
		$target = $this->return_url();

		wp_safe_redirect(
			add_query_arg(
				array(
					'vr_error' => $code,
					'vr_entry' => rawurlencode( $entered ),
					'vr_vin'   => rawurlencode( $vin ),
				),
				$target
			)
		);
		exit;
	}
}
