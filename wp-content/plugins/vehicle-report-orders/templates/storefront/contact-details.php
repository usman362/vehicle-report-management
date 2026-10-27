<?php
/**
 * How to reach the merchant.
 *
 * @package VehicleReportOrders
 *
 * @var string $email   Support address.
 * @var string $phone   Phone number.
 * @var string $address Postal address, one line per line break.
 * @var string $company Companies House number.
 * @var string $vat     VAT registration number.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;
?>
<ul class="vr-contact-details">
	<li>
		<strong><?php esc_html_e( 'Email', 'vehicle-report-orders' ); ?>:</strong>
		<?php if ( is_email( $email ) ) : ?>
			<a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a>
		<?php else : ?>
			<em class="vr-pending">[<?php esc_html_e( 'support address to be confirmed', 'vehicle-report-orders' ); ?>]</em>
		<?php endif; ?>
	</li>

	<li>
		<strong><?php esc_html_e( 'Phone', 'vehicle-report-orders' ); ?>:</strong>
		<?php if ( '' !== $phone ) : ?>
			<a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
		<?php else : ?>
			<em class="vr-pending">[<?php esc_html_e( 'phone number to be confirmed', 'vehicle-report-orders' ); ?>]</em>
		<?php endif; ?>
	</li>

	<li>
		<strong><?php esc_html_e( 'Address', 'vehicle-report-orders' ); ?>:</strong>
		<?php if ( '' !== $address ) : ?>
			<?php echo wp_kses_post( nl2br( esc_html( $address ) ) ); ?>
		<?php else : ?>
			<em class="vr-pending">[<?php esc_html_e( 'postal address to be confirmed', 'vehicle-report-orders' ); ?>]</em>
		<?php endif; ?>
	</li>

	<?php if ( '' !== $company ) : ?>
		<li><strong><?php esc_html_e( 'Company number', 'vehicle-report-orders' ); ?>:</strong> <?php echo esc_html( $company ); ?></li>
	<?php endif; ?>

	<?php if ( '' !== $vat ) : ?>
		<li><strong><?php esc_html_e( 'VAT number', 'vehicle-report-orders' ); ?>:</strong> <?php echo esc_html( $vat ); ?></li>
	<?php endif; ?>
</ul>
