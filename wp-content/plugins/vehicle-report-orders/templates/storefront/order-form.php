<?php
/**
 * Registration entry form.
 *
 * @package VehicleReportOrders
 *
 * @var string   $heading   Optional heading.
 * @var bool     $available Whether ordering is configured.
 * @var string   $error     Error message, if any.
 * @var string   $entered   Previously entered mark.
 * @var string   $price     Formatted price, or empty.
 * @var int|null $sla_hours Promised business hours, or null.
 * @var bool     $compact   Whether to render the short version.
 */

declare( strict_types=1 );

use VehicleReportOrders\Domain\Settings;

defined( 'ABSPATH' ) || exit;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Re-displaying what the visitor typed.
$vr_raw     = isset( $_GET['vr_entry'] ) ? sanitize_text_field( wp_unslash( $_GET['vr_entry'] ) ) : '';
$vr_prefill = '' !== $vr_raw ? sanitize_text_field( rawurldecode( $vr_raw ) ) : $entered;
?>
<div class="vr-card vr-order-form">
	<?php if ( '' !== (string) $heading ) : ?>
		<h2><?php echo esc_html( (string) $heading ); ?></h2>
	<?php endif; ?>

	<?php if ( '' !== $error ) : ?>
		<p class="vr-error" role="alert"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>

	<form method="post" class="vr-registration-plate">
		<?php wp_nonce_field( 'vr_start_order', 'vr_start_order_nonce' ); ?>

		<p>
			<label for="vr-registration"><strong><?php esc_html_e( 'Vehicle registration', 'vehicle-report-orders' ); ?></strong></label><br>
			<input
				type="text"
				id="vr-registration"
				name="vr_registration"
				value="<?php echo esc_attr( $vr_prefill ); ?>"
				placeholder="AB12 CDE"
				autocomplete="off"
				autocapitalize="characters"
				spellcheck="false"
				maxlength="12"
				required
				aria-describedby="vr-registration-help"
			>
		</p>

		<p id="vr-registration-help" class="has-small-font-size">
			<?php esc_html_e( 'We check the registration by hand before preparing your report. Entering it here does not look anything up yet.', 'vehicle-report-orders' ); ?>
		</p>

		<p>
			<button type="submit" class="wp-element-button" <?php disabled( ! $available ); ?>>
				<?php esc_html_e( 'Continue to report order', 'vehicle-report-orders' ); ?>
			</button>
		</p>
	</form>

	<?php if ( ! $compact ) : ?>
		<p class="has-small-font-size">
			<?php if ( '' !== $price ) : ?>
				<?php
				printf(
					/* translators: %s: price, already formatted. */
					esc_html__( 'Price: %s.', 'vehicle-report-orders' ),
					wp_kses_post( $price )
				);
				?>
			<?php endif; ?>

			<?php if ( null !== $sla_hours ) : ?>
				<?php
				printf(
					/* translators: 1: business hours, 2: opening hours description. */
					esc_html__( 'Emailed within %1$d business hours (%2$s).', 'vehicle-report-orders' ),
					(int) $sla_hours,
					esc_html( Settings::business_hours_label() )
				);
				?>
			<?php endif; ?>
		</p>
	<?php endif; ?>

	<?php if ( ! $available ) : ?>
		<p class="vr-error">
			<?php esc_html_e( 'Ordering is switched off until the price, delivery window and report sources are confirmed.', 'vehicle-report-orders' ); ?>
		</p>
	<?php endif; ?>
</div>
