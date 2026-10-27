<?php
/**
 * One full-width row per check.
 *
 * @package VehicleReportOrders
 *
 * @var string            $heading   Optional section heading.
 * @var string            $intro     Optional standfirst.
 * @var array<int,string> $keys      Check slugs, in catalogue order.
 * @var string            $order_url Where the call to action points.
 */

declare( strict_types=1 );

use VehicleReportOrders\Domain\VehicleCheck;

defined( 'ABSPATH' ) || exit;
?>
<section class="vr-check-rows alignwide">
	<?php if ( '' !== $heading ) : ?>
		<h2><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<?php if ( '' !== $intro ) : ?>
		<p class="vr-section-intro"><?php echo esc_html( $intro ); ?></p>
	<?php endif; ?>

	<?php foreach ( $keys as $vr_index => $vr_key ) : ?>
		<?php $vr_check = VehicleCheck::get( $vr_key ); ?>
		<?php if ( null !== $vr_check ) : ?>
			<div class="vr-check-row<?php echo 1 === $vr_index % 2 ? ' vr-check-row-flipped' : ''; ?>">
				<div class="vr-check-row-copy">
					<span class="vr-check-icon">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
							<?php echo wp_kses( VehicleCheck::icon( $vr_key ), VehicleCheck::icon_markup_allowed() ); ?>
						</svg>
					</span>
					<h3><?php echo esc_html( $vr_check['label'] ); ?></h3>
					<p><?php echo esc_html( $vr_check['blurb'] ); ?></p>
					<p class="vr-check-limit">
						<strong><?php esc_html_e( 'What it does not tell you:', 'vehicle-report-orders' ); ?></strong>
						<?php echo esc_html( $vr_check['limit'] ); ?>
					</p>
					<p>
						<a class="vr-text-cta" href="<?php echo esc_url( $order_url ); ?>">
							<?php esc_html_e( 'Order a report', 'vehicle-report-orders' ); ?>
							<span aria-hidden="true">&rarr;</span>
						</a>
					</p>
				</div>

				<?php
				/*
				 * The illustration is the report's own wording, not a picture of
				 * a vehicle: three outcomes, every one of them written down.
				 * Nothing here is a finding about any particular vehicle.
				 */
				?>
				<div class="vr-check-row-figure" aria-hidden="true">
					<div class="vr-sheet">
						<p class="vr-sheet-title"><?php echo esc_html( $vr_check['label'] ); ?></p>
						<ul class="vr-sheet-rows">
							<li>
								<span class="vr-chip vr-chip-found"><?php esc_html_e( 'Found', 'vehicle-report-orders' ); ?></span>
								<span>
									<?php
									printf(
										/* translators: %s: the name of the check, lower-cased. */
										esc_html__( 'a %s record exists, and the report sets it out', 'vehicle-report-orders' ),
										esc_html( strtolower( $vr_check['label'] ) )
									);
									?>
								</span>
							</li>
							<li>
								<span class="vr-chip vr-chip-none"><?php esc_html_e( 'Nothing recorded', 'vehicle-report-orders' ); ?></span>
								<span><?php esc_html_e( 'the source was searched and held nothing for your vehicle', 'vehicle-report-orders' ); ?></span>
							</li>
							<li>
								<span class="vr-chip vr-chip-unavailable"><?php esc_html_e( 'Not checked', 'vehicle-report-orders' ); ?></span>
								<span><?php esc_html_e( 'we could not run it, and the reason is written next to it', 'vehicle-report-orders' ); ?></span>
							</li>
						</ul>
						<p class="vr-sheet-foot"><?php echo esc_html( $vr_check['limit'] ); ?></p>
					</div>
				</div>
			</div>
		<?php endif; ?>
	<?php endforeach; ?>
</section>
