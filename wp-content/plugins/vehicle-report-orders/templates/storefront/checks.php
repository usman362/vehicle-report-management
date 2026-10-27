<?php
/**
 * One block per check a report can contain.
 *
 * @package VehicleReportOrders
 *
 * @var string            $heading Optional heading.
 * @var array<int,string> $keys    Check slugs to describe.
 */

declare( strict_types=1 );

use VehicleReportOrders\Domain\VehicleCheck;

defined( 'ABSPATH' ) || exit;
?>
<section class="vr-checks">
	<?php if ( '' !== $heading ) : ?>
		<h2><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<div class="vr-check-grid">
		<?php foreach ( $keys as $vr_key ) : ?>
			<?php $vr_check = VehicleCheck::get( $vr_key ); ?>
			<?php if ( null !== $vr_check ) : ?>
				<div class="vr-card vr-check-card">
					<h3><?php echo esc_html( $vr_check['label'] ); ?></h3>
					<p><?php echo esc_html( $vr_check['blurb'] ); ?></p>
					<p class="vr-check-limit">
						<strong><?php esc_html_e( 'What it does not tell you:', 'vehicle-report-orders' ); ?></strong>
						<?php echo esc_html( $vr_check['limit'] ); ?>
					</p>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</section>
