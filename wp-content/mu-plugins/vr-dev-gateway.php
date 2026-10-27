<?php
/**
 * Plugin Name: Vehicle Report — development payment gateway
 * Description: A payment method that can approve, decline or pretend the provider is unreachable, so the failure paths can be exercised before a real merchant account exists. Local development only; it refuses to run in any other environment.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

if ( ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
	return;
}

add_action(
	'plugins_loaded',
	static function (): void {
		if ( ! class_exists( 'WC_Payment_Gateway' ) || class_exists( 'VR_Dev_Gateway' ) ) {
			return;
		}

		/**
		 * A gateway whose outcome is chosen rather than earned.
		 *
		 * Real cards are not available here and will not be until the merchant
		 * account exists, but the site's response to a decline, a refund and an
		 * outage must be right long before then. This stands in for the
		 * provider so those paths can be run and tested; it never moves money
		 * and it never loads outside a development environment.
		 */
		class VR_Dev_Gateway extends WC_Payment_Gateway {

			/**
			 * The outcome to produce for the next payment.
			 *
			 * Set by the customer at checkout, or by a test through the
			 * vr_dev_gateway_outcome filter.
			 */
			public const OUTCOMES = array(
				'approve' => 'Approved — payment succeeds',
				'decline' => 'Declined — the provider refuses the card',
				'outage'  => 'Unreachable — the provider does not answer',
			);

			/**
			 * Set the gateway up.
			 */
			public function __construct() {
				$this->id                 = 'vr_dev';
				$this->method_title       = 'Development payments';
				$this->method_description = 'Chooses its own outcome. Development only: no money moves and nothing is sent to a payment provider.';
				$this->has_fields         = true;
				$this->title              = 'Test payment (development only)';
				$this->description        = 'No money moves. Choose what the payment provider should do.';

				$this->init_form_fields();
				$this->init_settings();

				$this->enabled = $this->get_option( 'enabled', 'yes' );

				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			}

			/**
			 * Admin options.
			 *
			 * @return void
			 */
			public function init_form_fields(): void {
				$this->form_fields = array(
					'enabled' => array(
						'title'   => 'Enable',
						'type'    => 'checkbox',
						'label'   => 'Accept development payments',
						'default' => 'yes',
					),
				);
			}

			/**
			 * The outcome chooser shown at checkout.
			 *
			 * @return void
			 */
			public function payment_fields(): void {
				echo '<p>' . esc_html( $this->description ) . '</p>';
				echo '<fieldset style="border:0;padding:0;margin:0"><legend class="screen-reader-text">What should the payment provider do?</legend>';

				foreach ( self::OUTCOMES as $value => $label ) {
					printf(
						'<p><label><input type="radio" name="vr_dev_outcome" value="%1$s" %2$s> %3$s</label></p>',
						esc_attr( $value ),
						checked( 'approve', $value, false ),
						esc_html( $label )
					);
				}

				echo '</fieldset>';
			}

			/**
			 * Which outcome this payment should produce.
			 *
			 * @return string One of the OUTCOMES keys.
			 */
			private function outcome(): string {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the checkout request before calling a gateway.
				$chosen = isset( $_POST['vr_dev_outcome'] ) ? sanitize_key( wp_unslash( $_POST['vr_dev_outcome'] ) ) : 'approve';

				/**
				 * Lets an automated test say what the provider should do.
				 *
				 * @param string $chosen One of the OUTCOMES keys.
				 */
				$chosen = (string) apply_filters( 'vr_dev_gateway_outcome', $chosen );

				return isset( self::OUTCOMES[ $chosen ] ) ? $chosen : 'approve';
			}

			/**
			 * Take, refuse, or fail to reach the provider.
			 *
			 * @param int $order_id Order identifier.
			 *
			 * @return array<string, string>
			 *
			 * @throws Exception When the provider is unreachable.
			 */
			public function process_payment( $order_id ): array {
				$order   = wc_get_order( $order_id );
				$outcome = $this->outcome();

				if ( 'outage' === $outcome ) {
					// Thrown, not returned: this is what an unreachable
					// provider looks like to the checkout, and the order must
					// not be left looking paid.
					$order->update_status( 'failed', 'Development gateway: the provider could not be reached.' );

					throw new Exception( esc_html__( 'The payment provider could not be reached. Nothing has been charged. Please try again in a moment.', 'woocommerce' ) );
				}

				if ( 'decline' === $outcome ) {
					$order->update_status( 'failed', 'Development gateway: the payment was declined.' );
					wc_add_notice( 'The payment was declined. Nothing has been charged and no report has been ordered.', 'error' );

					return array( 'result' => 'failure' );
				}

				$order->payment_complete( 'vr-dev-' . $order->get_id() );
				WC()->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			}
		}
	}
);

/**
 * Make the gateway available in the block checkout.
 *
 * A gateway with no block integration simply does not appear there, which
 * looks exactly like a broken store.
 */
add_action(
	'woocommerce_blocks_loaded',
	static function (): void {
		if ( ! class_exists( 'Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType' ) ) {
			return;
		}

		/**
		 * Block checkout registration for the development gateway.
		 */
		class VR_Dev_Gateway_Blocks extends Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {

			/**
			 * Gateway identifier.
			 *
			 * @var string
			 */
			protected $name = 'vr_dev';

			/**
			 * Read the gateway's settings.
			 *
			 * @return void
			 */
			public function initialize(): void {
				$this->settings = get_option( 'woocommerce_vr_dev_settings', array() );
			}

			/**
			 * Whether to offer it.
			 *
			 * @return bool
			 */
			public function is_active(): bool {
				return true;
			}

			/**
			 * Register and return the front-end script.
			 *
			 * @return string[]
			 */
			public function get_payment_method_script_handles(): array {
				$path = __DIR__ . '/vr-dev-gateway/blocks.js';

				wp_register_script(
					'vr-dev-gateway-blocks',
					content_url( 'mu-plugins/vr-dev-gateway/blocks.js' ),
					array( 'wc-blocks-registry', 'wp-element', 'wc-settings' ),
					(string) ( file_exists( $path ) ? filemtime( $path ) : '1' ),
					true
				);

				return array( 'vr-dev-gateway-blocks' );
			}

			/**
			 * Data handed to the script.
			 *
			 * @return array<string, mixed>
			 */
			public function get_payment_method_data(): array {
				return array(
					'title'       => 'Test payment (development only)',
					'description' => 'No money moves. Choose what the payment provider should do.',
					'outcomes'    => VR_Dev_Gateway::OUTCOMES,
					'supports'    => array( 'products' ),
				);
			}
		}

		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			static function ( $registry ): void {
				$registry->register( new VR_Dev_Gateway_Blocks() );
			}
		);
	}
);

add_filter(
	'woocommerce_payment_gateways',
	static function ( array $gateways ): array {
		$gateways[] = 'VR_Dev_Gateway';

		return $gateways;
	}
);
