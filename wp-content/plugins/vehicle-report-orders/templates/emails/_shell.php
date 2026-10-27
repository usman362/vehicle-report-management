<?php
/**
 * Shared email wrapper.
 *
 * @package VehicleReportOrders
 *
 * @var string $vr_heading Heading text.
 * @var string $vr_content Body HTML, already escaped.
 */

declare( strict_types=1 );

use VehicleReportOrders\Domain\Settings;

defined( 'ABSPATH' ) || exit;

$vr_trader  = (string) Settings::get( 'trader_name' );
$vr_support = (string) Settings::get( 'support_email' );
$vr_name    = '' !== $vr_trader ? $vr_trader : get_bloginfo( 'name' );
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
<head><meta charset="utf-8"><title><?php echo esc_html( $vr_heading ); ?></title></head>
<body style="margin:0;padding:24px;background:#f7f9fb;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#142638;">
	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #dce3ea;border-radius:12px;">
		<tr>
			<td style="padding:24px 28px;border-bottom:3px solid #1769aa;">
				<strong style="font-size:16px;"><?php echo esc_html( $vr_name ); ?></strong>
			</td>
		</tr>
		<tr>
			<td style="padding:28px;">
				<h1 style="margin:0 0 16px;font-size:20px;"><?php echo esc_html( $vr_heading ); ?></h1>
				<?php echo wp_kses_post( $vr_content ); ?>
			</td>
		</tr>
		<tr>
			<td style="padding:18px 28px;border-top:1px solid #dce3ea;font-size:12px;color:#5b6b7b;">
				<?php if ( is_email( $vr_support ) ) : ?>
					<?php
					printf(
						/* translators: %s: support email address. */
						esc_html__( 'Questions about this order? Reply to this email or write to %s.', 'vehicle-report-orders' ),
						esc_html( $vr_support )
					);
					?>
				<?php endif; ?>
			</td>
		</tr>
	</table>
</body>
</html>
