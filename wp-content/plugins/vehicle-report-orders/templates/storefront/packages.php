<?php
/**
 * Package comparison cards.
 *
 * @package VehicleReportOrders
 *
 * @var string                                          $heading  Optional heading.
 * @var array<int, \VehicleReportOrders\Domain\Package> $packages Tiers to show.
 */

declare( strict_types=1 );

use VehicleReportOrders\Domain\VehicleCheck;

defined( 'ABSPATH' ) || exit;
?>
<section class="vr-packages">
	<?php if ( '' !== $heading ) : ?>
		<h2><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<div class="vr-package-grid">
		<?php foreach ( $packages as $vr_package ) : ?>
			<?php
			$vr_price = $vr_package->formatted_price();
			$vr_url   = $vr_package->url();
			?>
			<div class="vr-card vr-package-card">
				<h3><?php echo esc_html( $vr_package->name() ); ?></h3>

				<p class="vr-package-price">
					<?php if ( '' !== $vr_price ) : ?>
						<?php echo wp_kses_post( $vr_price ); ?>
					<?php else : ?>
						<em class="vr-pending">[<?php esc_html_e( 'price to be confirmed', 'vehicle-report-orders' ); ?>]</em>
					<?php endif; ?>
				</p>

				<?php if ( $vr_package->reports() > 1 ) : ?>
					<p class="has-small-font-size">
						<?php
						printf(
							/* translators: %d: number of vehicles covered. */
							esc_html( _n( 'Covers %d vehicle.', 'Covers %d vehicles.', $vr_package->reports(), 'vehicle-report-orders' ) ),
							(int) $vr_package->reports()
						);
						?>
					</p>
				<?php endif; ?>

				<ul class="vr-package-list">
					<?php foreach ( VehicleCheck::keys() as $vr_key ) : ?>
						<?php $vr_included = in_array( $vr_key, $vr_package->checks(), true ); ?>
						<li class="<?php echo $vr_included ? 'vr-included' : 'vr-excluded'; ?>">
							<span aria-hidden="true"><?php echo $vr_included ? '&#10003;' : '&#8212;'; ?></span>
							<span class="screen-reader-text">
								<?php echo $vr_included ? esc_html__( 'Included:', 'vehicle-report-orders' ) : esc_html__( 'Not included:', 'vehicle-report-orders' ); ?>
							</span>
							<?php echo esc_html( VehicleCheck::label( $vr_key ) ); ?>
						</li>
					<?php endforeach; ?>
				</ul>

				<p>
					<?php if ( '' !== $vr_url ) : ?>
						<a class="wp-element-button" href="<?php echo esc_url( $vr_url ); ?>">
							<?php
							printf(
								/* translators: %s: package name. */
								esc_html__( 'See the %s package', 'vehicle-report-orders' ),
								esc_html( $vr_package->name() )
							);
							?>
						</a>
					<?php else : ?>
						<em class="vr-pending">[<?php esc_html_e( 'this package has not been set up yet', 'vehicle-report-orders' ); ?>]</em>
					<?php endif; ?>
				</p>
			</div>
		<?php endforeach; ?>
	</div>

	<p class="has-small-font-size vr-package-note">
		<?php esc_html_e( 'A package says which checks we will run. It does not promise that every check returns a record for your vehicle: where one cannot be completed, your report says so and explains why.', 'vehicle-report-orders' ); ?>
	</p>
</section>
