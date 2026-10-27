<?php
/**
 * Internal warning that a delivery failed.
 *
 * @package VehicleReportOrders
 *
 * @var array<string, mixed> $data Template data.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

// The order this message is about.
$vr_order   = $data['order'];
$vr_reason  = (string) $data['reason'];
$vr_heading = __( 'A report could not be delivered', 'vehicle-report-orders' );

ob_start();
?>
<p>
	<?php
	printf(
		/* translators: %s: order number. */
		esc_html__( 'Order %s has an uploaded report that could not be emailed to the customer.', 'vehicle-report-orders' ),
		esc_html( (string) $vr_order->get_order_number() )
	);
	?>
</p>

<p><strong><?php esc_html_e( 'Reason:', 'vehicle-report-orders' ); ?></strong> <?php echo esc_html( $vr_reason ); ?></p>

<p>
	<a href="<?php echo esc_url( $vr_order->get_edit_order_url() ); ?>"><?php esc_html_e( 'Open the order', 'vehicle-report-orders' ); ?></a>
</p>

<p style="font-size:13px;color:#5b6b7b;">
	<?php esc_html_e( 'The customer has not been told the report is ready. The file is still stored against the order.', 'vehicle-report-orders' ); ?>
</p>
<?php
$vr_content = (string) ob_get_clean();

require __DIR__ . '/_shell.php';
