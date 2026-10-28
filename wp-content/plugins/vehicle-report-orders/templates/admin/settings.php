<?php
/**
 * Report settings screen.
 *
 * @package VehicleReportOrders
 *
 * @var \VehicleReportOrders\Admin\SettingsPage $this     Settings page controller.
 * @var array<string, mixed>                    $settings Current settings.
 * @var array<string, string>                   $missing  Outstanding items before live mode.
 * @var bool                                    $saved      Whether a save just happened.
 * @var array<int, string>                      $seed_lines Output of a content rebuild, if one just ran.
 * @var \VehicleReportOrders\Content\Seeder      $seeder     Used to report where the page copy lives.
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
				<th scope="row"><?php esc_html_e( 'Working days', 'vehicle-report-orders' ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e( 'Working days', 'vehicle-report-orders' ); ?></legend>
						<?php foreach ( $this->weekday_names() as $vr_day => $vr_name ) : ?>
							<label style="margin-right:12px;">
								<input type="checkbox" name="business_days[]" value="<?php echo esc_attr( (string) $vr_day ); ?>" <?php checked( in_array( $vr_day, (array) $settings['business_days'], true ) ); ?>>
								<?php echo esc_html( $vr_name ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
					<p class="description"><?php esc_html_e( 'The delivery window is counted only during these days and hours, so a Friday evening order is not promised for Saturday.', 'vehicle-report-orders' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="vr-open"><?php esc_html_e( 'Opening hours', 'vehicle-report-orders' ); ?></label></th>
				<td>
					<input name="business_open" id="vr-open" type="time" value="<?php echo esc_attr( (string) $settings['business_open'] ); ?>">
					<span>&ndash;</span>
					<input name="business_close" type="time" value="<?php echo esc_attr( (string) $settings['business_close'] ); ?>">
					<p class="description">
						<?php
						$vr_label = \VehicleReportOrders\Domain\Settings::business_hours_label();
						echo esc_html(
							'' === $vr_label
								? __( 'No working days selected yet.', 'vehicle-report-orders' )
								: sprintf(
									/* translators: %s: human description of the opening hours. */
									__( 'Customers will be told: %s', 'vehicle-report-orders' ),
									$vr_label
								)
						);
						?>
					</p>
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
			<tr>
				<th scope="row"><label for="vr-sample-url"><?php esc_html_e( 'Example report', 'vehicle-report-orders' ); ?></label></th>
				<td>
					<input name="sample_report_url" id="vr-sample-url" type="url" class="regular-text" value="<?php echo esc_attr( (string) $settings['sample_report_url'] ); ?>">
					<p class="description"><?php esc_html_e( 'Link to a real report you have permission to publish, with the vehicle and customer details removed. Until one is supplied the sample page says so instead of showing an invented report.', 'vehicle-report-orders' ); ?></p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Legal pages', 'vehicle-report-orders' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Copy reviewed', 'vehicle-report-orders' ); ?></th>
				<td>
					<label for="vr-legal-approved">
						<input name="legal_copy_approved" id="vr-legal-approved" type="checkbox" value="1" <?php checked( (bool) $settings['legal_copy_approved'] ); ?>>
						<?php esc_html_e( 'The terms, privacy, cookie and refund pages have been reviewed and are the operator\'s own wording.', 'vehicle-report-orders' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'The pages shipped with this site are drafting aids. While this box is clear every legal page carries a visible draft notice and the store cannot go live.', 'vehicle-report-orders' ); ?></p>
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

		<h2 class="title"><?php esc_html_e( 'How customers reach you', 'vehicle-report-orders' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'A UK trader selling online has to publish a geographic address and a way to be contacted quickly. Anything left blank is shown on the storefront as outstanding, never filled in with a plausible-looking value.', 'vehicle-report-orders' ); ?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="vr-phone"><?php esc_html_e( 'Phone number', 'vehicle-report-orders' ); ?></label></th>
				<td><input name="phone" id="vr-phone" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['phone'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="vr-address"><?php esc_html_e( 'Postal address', 'vehicle-report-orders' ); ?></label></th>
				<td>
					<textarea name="postal_address" id="vr-address" rows="4" class="large-text"><?php echo esc_textarea( (string) $settings['postal_address'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'One line per line. Shown on the contact page and in the business details search engines read.', 'vehicle-report-orders' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="vr-company"><?php esc_html_e( 'Company number', 'vehicle-report-orders' ); ?></label></th>
				<td><input name="company_number" id="vr-company" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['company_number'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="vr-vat"><?php esc_html_e( 'VAT number', 'vehicle-report-orders' ); ?></label></th>
				<td><input name="vat_number" id="vr-vat" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['vat_number'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="vr-whatsapp"><?php esc_html_e( 'WhatsApp number', 'vehicle-report-orders' ); ?></label></th>
				<td>
					<input name="whatsapp_number" id="vr-whatsapp" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['whatsapp_number'] ); ?>" placeholder="447700900000">
					<p class="description"><?php esc_html_e( 'International format, digits only. The floating WhatsApp button appears only once this is set, so leave it empty unless someone answers it.', 'vehicle-report-orders' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="vr-chat"><?php esc_html_e( 'Live chat embed', 'vehicle-report-orders' ); ?></label></th>
				<td>
					<input name="live_chat_src" id="vr-chat" type="url" class="large-text" value="<?php echo esc_attr( (string) $settings['live_chat_src'] ); ?>" placeholder="https://embed.example.com/your-id">
					<p class="description"><?php esc_html_e( 'The HTTPS script address from your own chat provider. It is a third party loading its own script and setting its own cookies on your visitors, so name it on the cookie page before switching it on.', 'vehicle-report-orders' ); ?></p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'What the order form asks for', 'vehicle-report-orders' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Vehicle types accepted', 'vehicle-report-orders' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( \VehicleReportOrders\Domain\VehicleType::catalogue() as $vr_slug => $vr_label ) : ?>
							<label for="vr-type-<?php echo esc_attr( $vr_slug ); ?>" style="display:block;margin-bottom:.35rem;">
								<input
									type="checkbox"
									name="vehicle_types[]"
									id="vr-type-<?php echo esc_attr( $vr_slug ); ?>"
									value="<?php echo esc_attr( $vr_slug ); ?>"
									<?php checked( in_array( $vr_slug, \VehicleReportOrders\Domain\Settings::vehicle_types(), true ) ); ?>
								>
								<?php echo esc_html( $vr_label ); ?>
							</label>
						<?php endforeach; ?>
						<p class="description"><?php esc_html_e( 'Tick only what you can actually research. Untick everything to stop asking the question at all.', 'vehicle-report-orders' ); ?></p>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Accept a VIN', 'vehicle-report-orders' ); ?></th>
				<td>
					<label for="vr-accept-vin">
						<input name="accept_vin" id="vr-accept-vin" type="checkbox" value="1" <?php checked( (bool) $settings['accept_vin'] ); ?>>
						<?php esc_html_e( 'Let a customer give a VIN where the vehicle has no current UK registration.', 'vehicle-report-orders' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Search and sharing', 'vehicle-report-orders' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="vr-seo"><?php esc_html_e( 'Fallback description', 'vehicle-report-orders' ); ?></label></th>
				<td>
					<textarea name="seo_description" id="vr-seo" rows="3" class="large-text"><?php echo esc_textarea( (string) $settings['seo_description'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Used only where a page has nothing of its own to describe it. Each page otherwise describes itself in its own words.', 'vehicle-report-orders' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="vr-social-image"><?php esc_html_e( 'Sharing image', 'vehicle-report-orders' ); ?></label></th>
				<td>
					<input name="social_image_id" id="vr-social-image" type="number" min="0" class="small-text" value="<?php echo esc_attr( (string) (int) $settings['social_image_id'] ); ?>">
					<p class="description"><?php esc_html_e( 'Media library id of the image shown when a link to this site is shared. Leave at 0 to use the site icon.', 'vehicle-report-orders' ); ?></p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Logos', 'vehicle-report-orders' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Only logos you are actually entitled to display: a licence you hold, a trade body you belong to, a payment provider you use. A publication\'s logo is not a licence to imply they endorsed you, and nothing here is supplied for you.', 'vehicle-report-orders' ); ?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="vr-trust-logos"><?php esc_html_e( 'Media library ids', 'vehicle-report-orders' ); ?></label></th>
				<td>
					<input
						name="trust_logos"
						id="vr-trust-logos"
						type="text"
						class="regular-text"
						value="<?php echo esc_attr( implode( ', ', \VehicleReportOrders\Domain\Settings::trust_logos() ) ); ?>"
						placeholder="12, 34, 56"
					>
					<p class="description"><?php esc_html_e( 'Comma-separated. Upload each logo to the media library, set its alt text there, and paste the ids here. An id that is not an image, or one deleted later, is simply left out.', 'vehicle-report-orders' ); ?></p>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Customer reviews', 'vehicle-report-orders' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Only reviews real customers actually gave you, in their words, with the name they agreed to be published under. Leave every quote empty and no reviews section appears anywhere, which is the honest state before anyone has bought a report.', 'vehicle-report-orders' ); ?>
		</p>
		<table class="form-table" role="presentation">
			<?php
			$vr_reviews   = \VehicleReportOrders\Domain\Settings::reviews();
			$vr_reviews[] = array(
				'quote' => '',
				'name'  => '',
				'date'  => '',
			);
			?>
			<?php foreach ( $vr_reviews as $vr_index => $vr_review ) : ?>
				<tr>
					<th scope="row">
						<label for="vr-review-<?php echo esc_attr( (string) $vr_index ); ?>">
							<?php
							printf(
								/* translators: %d: review number. */
								esc_html__( 'Review %d', 'vehicle-report-orders' ),
								(int) $vr_index + 1
							);
							?>
						</label>
					</th>
					<td>
						<textarea name="review_quote[]" id="vr-review-<?php echo esc_attr( (string) $vr_index ); ?>" rows="3" class="large-text"><?php echo esc_textarea( $vr_review['quote'] ); ?></textarea>
						<p>
							<input type="text" name="review_name[]" value="<?php echo esc_attr( $vr_review['name'] ); ?>" placeholder="<?php esc_attr_e( 'Name', 'vehicle-report-orders' ); ?>">
							<input type="text" name="review_date[]" value="<?php echo esc_attr( $vr_review['date'] ); ?>" placeholder="<?php esc_attr_e( 'Date of experience', 'vehicle-report-orders' ); ?>">
						</p>
					</td>
				</tr>
			<?php endforeach; ?>
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

	<hr>

	<h2 class="title"><?php esc_html_e( 'Storefront content', 'vehicle-report-orders' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Creates or refreshes the pages, the packages and the navigation from the copy shipped with the plugin. It only touches what it owns, and it never sets a price. Use it after installing, or after an update changes the wording.', 'vehicle-report-orders' ); ?>
	</p>

	<?php if ( ! $seeder->has_content() ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<?php
				printf(
					/* translators: %s: directory the page copy was expected in. */
					esc_html__( 'No page copy found. It should be in %s.', 'vehicle-report-orders' ),
					'<code>' . esc_html( $seeder->content_dir() . '/pages' ) . '</code>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( array() !== $seed_lines ) : ?>
		<div class="notice notice-success inline">
			<p><strong><?php esc_html_e( 'Storefront rebuilt.', 'vehicle-report-orders' ); ?></strong></p>
		</div>
		<pre class="vr-seed-log" style="background:#f6f7f7;border:1px solid #dcdcde;padding:1rem;max-height:20rem;overflow:auto;">
		<?php
		foreach ( $seed_lines as $vr_line ) {
			echo esc_html( $vr_line ) . "\n";
		}
		?>
		</pre>
	<?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'vr_seed_content', 'vr_seed_nonce' ); ?>
		<?php
		// Named anything but "submit": that name shadows the form's own submit().
		submit_button(
			__( 'Rebuild storefront content', 'vehicle-report-orders' ),
			'secondary',
			'vr_seed_submit',
			true,
			array( 'disabled' => ! $seeder->has_content() )
		);
		?>
	</form>
</div>
