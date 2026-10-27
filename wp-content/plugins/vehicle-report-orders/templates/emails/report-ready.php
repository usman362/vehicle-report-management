<?php
/**
 * Report ready for download.
 *
 * @package VehicleReportOrders
 *
 * @var array<string, mixed> $data Template data.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

// The order this message is about.
$vr_order   = $data['order'];
$vr_link    = (string) $data['link'];
$vr_reg     = (string) $data['registration'];
$vr_ttl     = (int) $data['ttl_hours'];
$vr_heading = __( 'Your vehicle report is ready', 'vehicle-report-orders' );

ob_start();
?>
<p>
	<?php
	if ( '' !== $vr_reg ) {
		printf(
			/* translators: %s: vehicle registration. */
			esc_html__( 'The report for %s is ready to download.', 'vehicle-report-orders' ),
			'<strong>' . esc_html( $vr_reg ) . '</strong>'
		);
	} else {
		esc_html_e( 'Your report is ready to download.', 'vehicle-report-orders' );
	}
	?>
</p>

<p style="margin:24px 0;">
	<a href="<?php echo esc_url( $vr_link ); ?>" style="display:inline-block;background:#1769aa;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:600;">
		<?php esc_html_e( 'Download your report (PDF)', 'vehicle-report-orders' ); ?>
	</a>
</p>

<p style="font-size:13px;color:#5b6b7b;">
	<?php
	printf(
		/* translators: %d: number of hours the link lasts. */
		esc_html__( 'The link works for %d hours and only for this order. Please do not forward it — anyone who opens it can read your report.', 'vehicle-report-orders' ),
		esc_html( (string) $vr_ttl )
	);
	?>
</p>

<p style="font-size:13px;color:#5b6b7b;">
	<?php esc_html_e( 'If the link has expired, reply to this email and we will send a new one.', 'vehicle-report-orders' ); ?>
</p>
<?php
$vr_content = (string) ob_get_clean();

require __DIR__ . '/_shell.php';
