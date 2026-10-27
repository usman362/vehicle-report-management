<?php
/**
 * Contact form.
 *
 * @package VehicleReportOrders
 *
 * @var bool   $sent    Whether the last submission was sent.
 * @var string $message Error to show, if any.
 * @var string $email   Support address, already validated.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;
?>
<div class="vr-card vr-contact-form">
	<?php if ( $sent ) : ?>
		<p class="vr-note" role="status">
			<?php esc_html_e( 'Thank you — your message has been sent. We reply during our working hours.', 'vehicle-report-orders' ); ?>
		</p>
	<?php endif; ?>

	<?php if ( '' !== $message ) : ?>
		<p class="vr-error" role="alert"><?php echo esc_html( $message ); ?></p>
	<?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'vr_contact', 'vr_contact_nonce' ); ?>

		<p>
			<label for="vr-name"><strong><?php esc_html_e( 'Your name', 'vehicle-report-orders' ); ?></strong></label><br>
			<input type="text" id="vr-name" name="vr_name" autocomplete="name" required>
		</p>

		<p>
			<label for="vr-email"><strong><?php esc_html_e( 'Your email address', 'vehicle-report-orders' ); ?></strong></label><br>
			<input type="email" id="vr-email" name="vr_email" autocomplete="email" required>
		</p>

		<p>
			<label for="vr-order-number"><?php esc_html_e( 'Order number', 'vehicle-report-orders' ); ?></label><br>
			<input type="text" id="vr-order-number" name="vr_order_number" autocomplete="off">
			<span class="has-small-font-size"><?php esc_html_e( 'If you are asking about an order you have already placed, this helps us find it straight away.', 'vehicle-report-orders' ); ?></span>
		</p>

		<p>
			<label for="vr-subject"><?php esc_html_e( 'Subject', 'vehicle-report-orders' ); ?></label><br>
			<input type="text" id="vr-subject" name="vr_subject" autocomplete="off">
		</p>

		<p>
			<label for="vr-message"><strong><?php esc_html_e( 'Message', 'vehicle-report-orders' ); ?></strong></label><br>
			<textarea id="vr-message" name="vr_message" rows="6" required></textarea>
		</p>

		<p class="vr-honeypot" aria-hidden="true">
			<label for="vr-website"><?php esc_html_e( 'Leave this field empty', 'vehicle-report-orders' ); ?></label>
			<input type="text" id="vr-website" name="vr_website" tabindex="-1" autocomplete="off">
		</p>

		<p>
			<button type="submit" class="wp-element-button"><?php esc_html_e( 'Send message', 'vehicle-report-orders' ); ?></button>
		</p>

		<p class="has-small-font-size">
			<?php
			printf(
				/* translators: %s: support email address. */
				esc_html__( 'Your message goes to %s and nowhere else. We use your address to reply, and we do not add you to any mailing list.', 'vehicle-report-orders' ),
				esc_html( antispambot( $email ) )
			);
			?>
		</p>
	</form>
</div>
