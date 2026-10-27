<?php
/**
 * Report settings screen.
 *
 * @package VehicleReportOrders
 *
 * @var \VehicleReportOrders\Admin\SettingsPage $this     Settings page controller.
 * @var array<string, mixed>                    $settings Current settings.
 * @var array<string, string>                   $missing  Outstanding items before live mode.
 * @var bool                                    $saved    Whether a save just happened.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

$vr_storage = $this->storage_status();
$vr_ready   = array() === $missing;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Report settings', 'vehicle-report-orders' ); ?></h1>

	<?php if ( $saved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'vehicle-report-orders' ); ?></p></div>
	<?php endif; ?>

	<?php if ( $vr_ready ) : ?>
		<div class="notice notice-success">
			<p>
				<strong><?php esc_html_e( 'Everything a paid order needs is configured.', 'vehicle-report-orders' ); ?></strong>
				<?php esc_html_e( 'Payments still run in whatever mode the gateway itself is set to; check that separately before launch.', 'vehicle-report-orders' ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="notice notice-warning">
			<p><strong><?php esc_html_e( 'This site is not ready to take a real payment.', 'vehicle-report-orders' ); ?></strong></p>
			<p><?php esc_html_e( 'The merchant must supply the following before the storefront may promise anything:', 'vehicle-report-orders' ); ?></p>
			<ul class="ul-disc">
				<?php foreach ( $missing as $vr_item ) : ?>
					<li><?php echo esc_html( $vr_item ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( 'vr_save_settings', 'vr_settings_nonce' ); ?>

		<h2 class="title"><?php esc_html_e( 'Delivery promise', 'vehicle-report-orders' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Shown to the customer before payment and used to flag overdue orders. Promise only what the team can actually meet.', 'vehicle-report-orders' ); ?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="vr-sla-hours"><?php esc_html_e( 'Delivery window (business hours)', 'vehicle-report-orders' ); ?></label></th>
				<td>
					<input name="sla_hours" id="vr-sla-hours" type="number" min="0" max="720" class="small-text" value="<?php echo esc_attr( (string) $settings['sla_hours'] ); ?>">
					<p class="description"><?php esc_html_e( 'Zero means no window has been agreed yet; the storefront will not state a delivery time.', 'vehicle-report-orders' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="vr-business-hours"><?php esc_html_e( 'Business hours', 'vehicle-report-orders' ); ?></label></th>
				<td>
					<input name="business_hours" id="vr-business-hours" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['business_hours'] ); ?>" placeholder="<?php esc_attr_e( 'Monday to Friday, 9am–5pm UK time', 'vehicle-report-orders' ); ?>">
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Trader and support', 'vehicle-report-orders' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="vr-trader-name"><?php esc_html_e( 'Registered trading name', 'vehicle-report-orders' ); ?></label></th>
				<td><input name="trader_name" id="vr-trader-name" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['trader_name'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="vr-support-email"><?php esc_html_e( 'Support email address', 'vehicle-report-orders' ); ?></label></th>
				<td><input name="support_email" id="vr-support-email" type="email" class="regular-text" value="<?php echo esc_attr( (string) $settings['support_email'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="vr-terms-version"><?php esc_html_e( 'Terms version', 'vehicle-report-orders' ); ?></label></th>
				<td>
					<input name="terms_version" id="vr-terms-version" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['terms_version'] ); ?>" placeholder="<?php esc_attr_e( '2026-10-27', 'vehicle-report-orders' ); ?>">
					<p class="description"><?php esc_html_e( 'Recorded against every order, so a later change to the terms never rewrites what a past customer agreed to.', 'vehicle-report-orders' ); ?></p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Report product and sources', 'vehicle-report-orders' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="vr-product"><?php esc_html_e( 'Report product', 'vehicle-report-orders' ); ?></label></th>
				<td>
					<select name="report_product_id" id="vr-product">
						<option value="0"><?php esc_html_e( '— not selected —', 'vehicle-report-orders' ); ?></option>
						<?php foreach ( $this->product_choices() as $vr_id => $vr_label ) : ?>
							<option value="<?php echo esc_attr( (string) $vr_id ); ?>" <?php selected( (int) $settings['report_product_id'], (int) $vr_id ); ?>>
								<?php echo esc_html( $vr_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'The price lives on the WooCommerce product, so tax settings and receipts stay consistent.', 'vehicle-report-orders' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="vr-data-sources"><?php esc_html_e( 'Approved data sources', 'vehicle-report-orders' ); ?></label></th>
				<td>
					<textarea name="data_sources" id="vr-data-sources" rows="5" class="large-text code"><?php echo esc_textarea( (string) $settings['data_sources'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One source per line, exactly as staff are licensed to use and cite. These are published on the data-sources page and attached to each report.', 'vehicle-report-orders' ); ?></p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Delivery mechanics', 'vehicle-report-orders' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="vr-ttl"><?php esc_html_e( 'Download link lifetime (hours)', 'vehicle-report-orders' ); ?></label></th>
				<td><input name="download_ttl_hours" id="vr-ttl" type="number" min="1" max="8760" class="small-text" value="<?php echo esc_attr( (string) $settings['download_ttl_hours'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="vr-tries"><?php esc_html_e( 'Maximum delivery attempts', 'vehicle-report-orders' ); ?></label></th>
				<td><input name="max_delivery_tries" id="vr-tries" type="number" min="1" max="20" class="small-text" value="<?php echo esc_attr( (string) $settings['max_delivery_tries'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Report file storage', 'vehicle-report-orders' ); ?></th>
				<td>
					<p>
						<code><?php echo esc_html( $vr_storage['path'] ); ?></code><br>
						<?php if ( $vr_storage['safe'] ) : ?>
							<span style="color:#13764b;">&#10003; <?php esc_html_e( 'Outside the web root: the server cannot serve these files directly.', 'vehicle-report-orders' ); ?></span>
						<?php else : ?>
							<span style="color:#b93838;">&#9888; <?php esc_html_e( 'Inside the web root. Protection depends on the web server honouring .htaccess, which nginx does not. Set VR_PRIVATE_STORAGE_DIR to a path outside the document root.', 'vehicle-report-orders' ); ?></span>
						<?php endif; ?>
					</p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Go-live confirmation', 'vehicle-report-orders' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Ready for real customers', 'vehicle-report-orders' ); ?></th>
				<td>
					<label for="vr-live">
						<input name="live_mode_confirmed" id="vr-live" type="checkbox" value="1" <?php checked( (bool) $settings['live_mode_confirmed'] ); ?> <?php disabled( ! $vr_ready ); ?>>
						<?php esc_html_e( 'Price, delivery window, data sources and policies are approved and deliverable.', 'vehicle-report-orders' ); ?>
					</label>
					<?php if ( ! $vr_ready ) : ?>
						<p class="description"><?php esc_html_e( 'Cannot be confirmed while anything above is outstanding.', 'vehicle-report-orders' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Save settings', 'vehicle-report-orders' ) ); ?>
	</form>
</div>
