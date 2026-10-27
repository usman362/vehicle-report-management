<?php
/**
 * Order entry form.
 *
 * @package VehicleReportOrders
 *
 * @var string                                          $heading    Optional heading.
 * @var bool                                            $available  Whether ordering is configured.
 * @var string                                          $error      Error message, if any.
 * @var string                                          $entered    Previously entered mark.
 * @var string                                          $vin        Previously entered VIN.
 * @var array<int, \VehicleReportOrders\Domain\Package> $packages   Tiers offered.
 * @var string                                          $chosen     Selected tier slug.
 * @var bool                                            $locked     Whether the tier is fixed by the page.
 * @var array<string, string>                           $types      Vehicle types offered.
 * @var string                                          $type       Previously chosen type.
 * @var bool                                            $accept_vin Whether a VIN may be given.
 * @var string                                          $price      Formatted entry price, or empty.
 * @var int|null                                        $sla_hours  Promised business hours, or null.
 * @var bool                                            $compact    Whether to render the short version.
 */

declare( strict_types=1 );

use VehicleReportOrders\Domain\Settings;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Re-displaying what the visitor typed.
$vr_raw     = isset( $_GET['vr_entry'] ) ? sanitize_text_field( wp_unslash( $_GET['vr_entry'] ) ) : '';
$vr_raw_vin = isset( $_GET['vr_vin'] ) ? sanitize_text_field( wp_unslash( $_GET['vr_vin'] ) ) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended
$vr_prefill     = '' !== $vr_raw ? sanitize_text_field( rawurldecode( $vr_raw ) ) : $entered;
$vr_prefill_vin = '' !== $vr_raw_vin ? sanitize_text_field( rawurldecode( $vr_raw_vin ) ) : $vin;
$vr_use_vin     = $accept_vin && '' === $vr_prefill && '' !== $vr_prefill_vin;
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

		<?php if ( $accept_vin ) : ?>
			<fieldset class="vr-identifier-choice">
				<legend><strong><?php esc_html_e( 'What do you have?', 'vehicle-report-orders' ); ?></strong></legend>

				<label>
					<input type="radio" name="vr_identifier" value="registration" <?php checked( ! $vr_use_vin ); ?> data-vr-shows="vr-field-registration">
					<?php esc_html_e( 'A UK registration', 'vehicle-report-orders' ); ?>
				</label>

				<label>
					<input type="radio" name="vr_identifier" value="vin" <?php checked( $vr_use_vin ); ?> data-vr-shows="vr-field-vin">
					<?php esc_html_e( 'A VIN', 'vehicle-report-orders' ); ?>
				</label>
			</fieldset>
		<?php else : ?>
			<input type="hidden" name="vr_identifier" value="registration">
		<?php endif; ?>

		<p id="vr-field-registration" class="vr-identifier-field">
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
				aria-describedby="vr-registration-help"
			>
		</p>

		<?php if ( $accept_vin ) : ?>
			<p id="vr-field-vin" class="vr-identifier-field">
				<label for="vr-vin"><strong><?php esc_html_e( 'VIN', 'vehicle-report-orders' ); ?></strong></label><br>
				<input
					type="text"
					id="vr-vin"
					name="vr_vin"
					value="<?php echo esc_attr( $vr_prefill_vin ); ?>"
					placeholder="WVWZZZ1KZAW000000"
					autocomplete="off"
					autocapitalize="characters"
					spellcheck="false"
					maxlength="17"
					aria-describedby="vr-vin-help"
				>
				<span id="vr-vin-help" class="has-small-font-size">
					<?php esc_html_e( '17 characters, on your V5C logbook. Use this for an import or a vehicle with no current UK plate.', 'vehicle-report-orders' ); ?>
				</span>
			</p>
		<?php endif; ?>

		<?php if ( array() !== $types ) : ?>
			<p>
				<label for="vr-vehicle-type"><strong><?php esc_html_e( 'Kind of vehicle', 'vehicle-report-orders' ); ?></strong></label><br>
				<select id="vr-vehicle-type" name="vr_vehicle_type" required>
					<option value=""><?php esc_html_e( 'Choose one', 'vehicle-report-orders' ); ?></option>
					<?php foreach ( $types as $vr_slug => $vr_label ) : ?>
						<option value="<?php echo esc_attr( $vr_slug ); ?>" <?php selected( $type, $vr_slug ); ?>>
							<?php echo esc_html( $vr_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
		<?php endif; ?>

		<?php if ( $locked ) : ?>
			<input type="hidden" name="vr_package" value="<?php echo esc_attr( $chosen ); ?>">
		<?php elseif ( count( $packages ) > 1 ) : ?>
			<p>
				<label for="vr-package"><strong><?php esc_html_e( 'Package', 'vehicle-report-orders' ); ?></strong></label><br>
				<select id="vr-package" name="vr_package">
					<?php foreach ( $packages as $vr_package ) : ?>
						<?php $vr_package_price = $vr_package->formatted_price(); ?>
						<option value="<?php echo esc_attr( $vr_package->slug() ); ?>" <?php selected( $chosen, $vr_package->slug() ); ?> <?php disabled( ! $vr_package->is_purchasable() ); ?>>
							<?php
							echo esc_html(
								'' !== $vr_package_price
									? sprintf(
										/* translators: 1: package name, 2: price. */
										__( '%1$s — %2$s', 'vehicle-report-orders' ),
										$vr_package->name(),
										wp_strip_all_tags( $vr_package_price )
									)
									: sprintf(
										/* translators: %s: package name. */
										__( '%s — price to be confirmed', 'vehicle-report-orders' ),
										$vr_package->name()
									)
							);
							?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
		<?php elseif ( array() !== $packages ) : ?>
			<input type="hidden" name="vr_package" value="<?php echo esc_attr( $packages[0]->slug() ); ?>">
		<?php endif; ?>

		<p id="vr-registration-help" class="has-small-font-size">
			<?php esc_html_e( 'We check what you enter by hand before preparing your report. Entering it here does not look anything up yet.', 'vehicle-report-orders' ); ?>
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
					esc_html__( 'From %s.', 'vehicle-report-orders' ),
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
