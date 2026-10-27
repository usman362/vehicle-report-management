<?php
/**
 * This site's own delivery record.
 *
 * @package VehicleReportOrders
 *
 * @var array{delivered: int, median_minutes: int} $stats Counted from delivered orders.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$vr_median = $stats['median_minutes'];
$vr_window = $vr_median >= 120
	? sprintf(
		/* translators: %d: whole hours. */
		_n( '%d hour', '%d hours', (int) round( $vr_median / 60 ), 'vehicle-report-orders' ),
		(int) round( $vr_median / 60 )
	)
	: sprintf(
		/* translators: %d: minutes. */
		_n( '%d minute', '%d minutes', $vr_median, 'vehicle-report-orders' ),
		$vr_median
	);
?>
<section class="vr-stats">
	<dl class="vr-stat-grid">
		<div class="vr-stat">
			<dt><?php esc_html_e( 'Reports delivered', 'vehicle-report-orders' ); ?></dt>
			<dd><?php echo esc_html( number_format_i18n( $stats['delivered'] ) ); ?></dd>
		</div>
		<div class="vr-stat">
			<dt><?php esc_html_e( 'Typical delivery', 'vehicle-report-orders' ); ?></dt>
			<dd><?php echo esc_html( $vr_window ); ?></dd>
		</div>
	</dl>
	<p class="has-small-font-size">
		<?php esc_html_e( 'Counted from this site\'s own delivered orders, not from a marketing figure. "Typical" is the middle of those deliveries, so half took longer.', 'vehicle-report-orders' ); ?>
	</p>
</section>
