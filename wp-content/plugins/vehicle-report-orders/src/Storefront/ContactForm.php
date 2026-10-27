<?php
/**
 * Public contact form.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Storefront;

use VehicleReportOrders\Audit\AuditLogger;
use VehicleReportOrders\Domain\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Lets a visitor write to the merchant without publishing a mailbox to scrapers.
 *
 * The message is emailed to the support address and nowhere else: nothing is
 * stored, no list is built, and the visitor is not subscribed to anything. A
 * hidden field catches the simplest bots and a short per-visitor cooldown stops
 * the form being used to send mail in bulk. The audit trail records that a
 * message was sent, never its contents.
 */
final class ContactForm {

	public const SHORTCODE = 'vehicle_report_contact_form';

	private const NONCE      = 'vr_contact';
	private const HONEYPOT   = 'vr_website';
	private const COOLDOWN   = 60;
	private const MAX_LENGTH = 5000;

	/**
	 * Hook the shortcode and its handler.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( self::SHORTCODE, array( $this, 'render' ) );
		add_action( 'template_redirect', array( $this, 'handle_submission' ) );
	}

	/**
	 * Render the form.
	 *
	 * @return string
	 */
	public function render(): string {
		$email = (string) Settings::get( 'support_email' );

		if ( ! is_email( $email ) ) {
			return '<p><em class="vr-pending">['
				. esc_html__( 'the contact form is switched off until a support address is configured', 'vehicle-report-orders' )
				. ']</em></p>';
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only flags from a redirect.
		$sent  = isset( $_GET['vr_sent'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['vr_sent'] ) );
		$error = isset( $_GET['vr_contact_error'] ) ? sanitize_key( wp_unslash( $_GET['vr_contact_error'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$message = match ( $error ) {
			'fields'  => __( 'Please give your name, an email address we can reply to, and a message.', 'vehicle-report-orders' ),
			'email'   => __( 'That email address does not look right. We need a working address to reply to.', 'vehicle-report-orders' ),
			'slow'    => __( 'You have just sent a message. Give us a minute to read it before sending another.', 'vehicle-report-orders' ),
			'failed'  => __( 'The message could not be sent. Please email us directly instead.', 'vehicle-report-orders' ),
			default   => '',
		};

		ob_start();
		include __DIR__ . '/../../templates/storefront/contact-form.php';

		return (string) ob_get_clean();
	}

	/**
	 * Validate and send a submission.
	 *
	 * @return void
	 */
	public function handle_submission(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified immediately below.
		if ( ! isset( $_POST['vr_contact_nonce'] ) ) {
			return;
		}

		if ( ! check_admin_referer( self::NONCE, 'vr_contact_nonce' ) ) {
			return;
		}

		// A field no person sees, so anything in it came from a bot.
		if ( '' !== $this->posted( self::HONEYPOT ) ) {
			$this->redirect( 'sent' );
		}

		$name    = $this->posted( 'vr_name' );
		$email   = $this->posted( 'vr_email' );
		$subject = $this->posted( 'vr_subject' );
		$order   = $this->posted( 'vr_order_number' );
		$body    = $this->posted_textarea( 'vr_message' );

		if ( '' === $name || '' === $email || '' === $body ) {
			$this->redirect( 'fields' );
		}

		if ( ! is_email( $email ) ) {
			$this->redirect( 'email' );
		}

		$cooldown = 'vr_contact_' . md5( $this->visitor_key() );

		if ( false !== get_transient( $cooldown ) ) {
			$this->redirect( 'slow' );
		}

		$support = (string) Settings::get( 'support_email' );

		if ( ! is_email( $support ) ) {
			$this->redirect( 'failed' );
		}

		$lines = array(
			sprintf( /* translators: %s: visitor name. */ __( 'From: %s', 'vehicle-report-orders' ), $name ),
			sprintf( /* translators: %s: visitor email. */ __( 'Email: %s', 'vehicle-report-orders' ), $email ),
		);

		if ( '' !== $order ) {
			$lines[] = sprintf( /* translators: %s: order number. */ __( 'Order number: %s', 'vehicle-report-orders' ), $order );
		}

		$lines[] = '';
		$lines[] = $body;

		$sent = wp_mail(
			$support,
			'' !== $subject
				? sprintf( /* translators: %s: subject the visitor typed. */ __( 'Website enquiry: %s', 'vehicle-report-orders' ), $subject )
				: __( 'Website enquiry', 'vehicle-report-orders' ),
			implode( "\n", $lines ),
			array( 'Reply-To: ' . $name . ' <' . $email . '>' )
		);

		set_transient( $cooldown, 1, self::COOLDOWN );

		// The message itself is not recorded: it is the visitor's, not ours.
		AuditLogger::log( 0, 'storefront.enquiry_sent', array( 'delivered' => (bool) $sent ) );

		$this->redirect( $sent ? 'sent' : 'failed' );
	}

	/**
	 * One sanitised single-line field.
	 *
	 * @param string $key Field name.
	 *
	 * @return string
	 */
	private function posted( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The caller verifies the nonce first.
		return isset( $_POST[ $key ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ) : '';
	}

	/**
	 * The message body, kept as plain multi-line text.
	 *
	 * @param string $key Field name.
	 *
	 * @return string
	 */
	private function posted_textarea( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The caller verifies the nonce first.
		$raw = isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : '';

		return trim( mb_substr( $raw, 0, self::MAX_LENGTH ) );
	}

	/**
	 * A stable but non-identifying key for the visitor, used only to rate limit.
	 *
	 * The address is hashed with the site's own salt and never stored in clear.
	 *
	 * @return string
	 */
	private function visitor_key(): string {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Hashed immediately, never stored or printed.
		$address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';

		return wp_hash( $address );
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
	 * Return to the form with the outcome.
	 *
	 * @param string $outcome 'sent' or an error code.
	 *
	 * @return never
	 */
	private function redirect( string $outcome ) {
		$target = $this->return_url();

		$args = 'sent' === $outcome
			? array( 'vr_sent' => '1' )
			: array( 'vr_contact_error' => $outcome );

		wp_safe_redirect( add_query_arg( $args, remove_query_arg( array( 'vr_sent', 'vr_contact_error' ), $target ) ) );
		exit;
	}
}
