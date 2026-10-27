<?php
/**
 * Payment received, report pending.
 *
 * @package VehicleReportOrders
 *
 * @var array<string, mixed> $data Template data.
 */

declare( strict_types=1 );

use VehicleReportOrders\Domain\Settings;

defined( 'ABSPATH' ) || exit;

// The order this message is about.
$vr_order   = $data['order'];
$vr_due     = (string) $data['due_at'];
$vr_reg     = (string) $data['registration'];
$vr_hours   = Settings::sla_hours();
$vr_heading = __( 'We have your report request', 'vehicle-report-orders' );

ob_start();
?>
<p><?php esc_html_e( 'Thank you — your payment has been confirmed and your request is with our team.', 'vehicle-report-orders' ); ?></p>

<table role="presentation" cellpadding="0" cellspacing="0" style="margin:16px 0;font-size:14px;">
	<tr><td style="padding:4px 16px 4px 0;color:#5b6b7b;"><?php esc_html_e( 'Order', 'vehicle-report-orders' ); ?></td><td><?php echo esc_html( (string) $vr_order->get_order_number() ); ?></td></tr>
	<?php if ( '' !== $vr_reg ) : ?>
		<tr><td style="padding:4px 16px 4px 0;color:#5b6b7b;"><?php esc_html_e( 'Vehicle', 'vehicle-report-orders' ); ?></td><td><strong><?php echo esc_html( $vr_reg ); ?></strong></td></tr>
	<?php endif; ?>
	<tr><td style="padding:4px 16px 4px 0;color:#5b6b7b;"><?php esc_html_e( 'Paid', 'vehicle-report-orders' ); ?></td><td><?php echo wp_kses_post( $vr_order->get_formatted_order_total() ); ?></td></tr>
</table>

<?php if ( null !== $vr_hours ) : ?>
	<p>
		<?php
		printf(
			/* translators: 1: number of business hours, 2: business hours description. */
			esc_html__( 'A member of staff prepares each report by hand. Yours will be emailed within %1$d business hours (%2$s).', 'vehicle-report-orders' ),
			(int) $vr_hours,
			esc_html( Settings::business_hours_label() )
		);
		?>
	</p>
<?php else : ?>
	<p><?php esc_html_e( 'A member of staff prepares each report by hand and will email yours as soon as it is ready.', 'vehicle-report-orders' ); ?></p>
<?php endif; ?>

<p style="font-size:13px;color:#5b6b7b;">
	<?php esc_html_e( 'This message confirms your payment only. We will send a separate email when the report itself is ready.', 'vehicle-report-orders' ); ?>
</p>
<?php
$vr_content = (string) ob_get_clean();

require __DIR__ . '/_shell.php';
