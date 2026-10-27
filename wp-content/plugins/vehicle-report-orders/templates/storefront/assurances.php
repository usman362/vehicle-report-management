<?php
/**
 * What the service undertakes, as a band under the hero.
 *
 * @package VehicleReportOrders
 *
 * @var array<int, array{label: string, value: string}> $rows Undertakings.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;
?>
<section class="vr-assurances">
	<dl class="vr-assurance-grid alignwide">
		<?php foreach ( $rows as $vr_row ) : ?>
			<div class="vr-assurance">
				<dt><?php echo esc_html( $vr_row['label'] ); ?></dt>
				<dd><?php echo esc_html( $vr_row['value'] ); ?></dd>
			</div>
		<?php endforeach; ?>
	</dl>
</section>
