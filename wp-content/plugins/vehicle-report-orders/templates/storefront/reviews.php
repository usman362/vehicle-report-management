<?php
/**
 * Customer reviews the merchant has supplied.
 *
 * @package VehicleReportOrders
 *
 * @var string                                                 $heading Optional heading.
 * @var array<int, array{quote: string, name: string, date: string}> $reviews Real reviews.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;
?>
<section class="vr-reviews">
	<?php if ( '' !== $heading ) : ?>
		<h2><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<div class="vr-review-grid">
		<?php foreach ( $reviews as $vr_review ) : ?>
			<figure class="vr-card vr-review">
				<blockquote><p><?php echo esc_html( $vr_review['quote'] ); ?></p></blockquote>
				<figcaption>
					<?php if ( '' !== $vr_review['name'] ) : ?>
						<strong><?php echo esc_html( $vr_review['name'] ); ?></strong>
					<?php endif; ?>
					<?php if ( '' !== $vr_review['date'] ) : ?>
						<span class="vr-review-date"><?php echo esc_html( $vr_review['date'] ); ?></span>
					<?php endif; ?>
				</figcaption>
			</figure>
		<?php endforeach; ?>
	</div>
</section>
