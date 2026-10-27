<?php
/**
 * Logos the merchant has supplied.
 *
 * @package VehicleReportOrders
 *
 * @var string             $heading Optional heading.
 * @var array<int, int>    $logos   Attachment ids, already checked.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;
?>
<section class="vr-trust">
	<?php if ( '' !== $heading ) : ?>
		<h2 class="vr-trust-heading"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>

	<ul class="vr-trust-logos">
		<?php foreach ( $logos as $vr_id ) : ?>
			<li>
				<?php
				// The alt text is whatever the merchant set in the media library,
				// so a logo cannot be captioned here with a claim they did not make.
				echo wp_get_attachment_image( $vr_id, 'medium', false, array( 'loading' => 'lazy' ) );
				?>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
