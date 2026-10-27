<?php
/**
 * The three answers a check can come back with.
 *
 * @package VehicleReportOrders
 *
 * @var string $heading Optional heading.
 * @var string $intro   Optional standfirst.
 * @var array<int, array{chip: string, class: string, text: string}> $rows The outcomes.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;
?>
<section class="vr-outcomes alignwide">
	<?php if ( '' !== $heading ) : ?>
		<h2><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<?php if ( '' !== $intro ) : ?>
		<p class="vr-section-intro"><?php echo esc_html( $intro ); ?></p>
	<?php endif; ?>

	<ol class="vr-outcome-grid">
		<?php foreach ( $rows as $vr_row ) : ?>
			<li class="vr-card vr-outcome">
				<span class="vr-chip <?php echo esc_attr( $vr_row['class'] ); ?>"><?php echo esc_html( $vr_row['chip'] ); ?></span>
				<p><?php echo esc_html( $vr_row['text'] ); ?></p>
			</li>
		<?php endforeach; ?>
	</ol>

	<p class="vr-outcome-foot">
		<?php esc_html_e( 'Every source we publish gets one of these three, in writing, on every report. A gap is never left for you to read as a pass.', 'vehicle-report-orders' ); ?>
	</p>
</section>
