<?php
/**
 * Optional support channels in the corner of every page.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Storefront;

use VehicleReportOrders\Domain\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * A WhatsApp button and a live chat widget, both off until configured.
 *
 * Neither is invented: the WhatsApp button appears only once the merchant has
 * given a number they actually answer, and the chat widget only once they have
 * supplied their own provider's embed address. A chat widget is a third party
 * loading its own script and setting its own cookies, so it is named on the
 * cookie page and the merchant is told as much on the settings screen.
 */
final class SupportWidgets {

	/**
	 * Hook the footer output.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_footer', array( $this, 'render' ), 20 );
		add_shortcode( 'vehicle_report_whatsapp', array( $this, 'whatsapp_link' ) );
		add_shortcode( 'vehicle_report_chat_notice', array( $this, 'chat_notice' ) );
	}

	/**
	 * Print whichever channels are configured.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( is_admin() ) {
			return;
		}

		$whatsapp = Settings::whatsapp_url();
		$chat     = $this->chat_src();

		if ( '' === $whatsapp && '' === $chat ) {
			return;
		}

		if ( '' !== $whatsapp ) {
			// Wrapped in a landmark: a link floating outside every region is
			// one a screen-reader user has no way to reach by structure.
			printf(
				'<aside class="vr-support" aria-label="%1$s"><a class="vr-whatsapp" href="%2$s" rel="noopener nofollow" target="_blank"><span aria-hidden="true">&#128172;</span><span class="vr-whatsapp-label">%3$s</span></a></aside>',
				esc_attr__( 'Support', 'vehicle-report-orders' ),
				esc_url( $whatsapp ),
				esc_html__( 'Message us on WhatsApp', 'vehicle-report-orders' )
			);
		}

		if ( '' !== $chat ) {
			wp_enqueue_script( 'vr-live-chat', $chat, array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- The provider versions its own embed.
		}
	}

	/**
	 * A WhatsApp link for use inside page copy.
	 *
	 * @return string
	 */
	public function whatsapp_link(): string {
		$url = Settings::whatsapp_url();

		if ( '' === $url ) {
			return '<em class="vr-pending">[' . esc_html__( 'WhatsApp number to be confirmed', 'vehicle-report-orders' ) . ']</em>';
		}

		return sprintf(
			'<a href="%1$s" rel="noopener nofollow" target="_blank">%2$s</a>',
			esc_url( $url ),
			esc_html__( 'Message us on WhatsApp', 'vehicle-report-orders' )
		);
	}

	/**
	 * What the cookie page has to say about live chat.
	 *
	 * Nothing at all while chat is switched off, because describing a cookie
	 * that is not being set is as misleading as hiding one that is.
	 *
	 * @return string
	 */
	public function chat_notice(): string {
		$src = $this->chat_src();

		if ( '' === $src ) {
			return '';
		}

		$host = (string) wp_parse_url( $src, PHP_URL_HOST );

		return '<p>' . esc_html(
			sprintf(
				/* translators: %s: chat provider hostname. */
				__( 'Live chat on this site is provided by %s. Opening any page loads their script, and they set their own cookies to keep your conversation together between pages. We do not control those cookies, and we do not receive anything from them beyond the conversation itself.', 'vehicle-report-orders' ),
				'' !== $host ? $host : __( 'a third-party provider', 'vehicle-report-orders' )
			)
		) . '</p>';
	}

	/**
	 * The configured chat embed, when it is a URL we are willing to load.
	 *
	 * @return string
	 */
	private function chat_src(): string {
		$src = trim( (string) Settings::get( 'live_chat_src' ) );

		if ( '' === $src || ! wp_http_validate_url( $src ) ) {
			return '';
		}

		return 0 === strpos( $src, 'https://' ) ? $src : '';
	}
}
