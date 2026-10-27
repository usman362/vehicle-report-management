<?php
/**
 * Report panel on the order screen.
 *
 * @package VehicleReportOrders
 *
 * @var \WC_Order                                              $order        Order.
 * @var \VehicleReportOrders\Domain\FulfillmentStatus          $status       Current state.
 * @var \VehicleReportOrders\Domain\RegistrationNumber|null    $registration Registration mark.
 * @var float|null                                             $hours_left   Hours against the promise.
 * @var list<object>                                           $versions     Report versions.
 * @var object|null                                            $current      Current version.
 * @var int                                                    $assignee     Assigned user id.
 * @var list<\VehicleReportOrders\Domain\FulfillmentStatus>    $transitions  Allowed next states.
 */

declare( strict_types=1 );

use VehicleReportOrders\Admin\CapabilityManager;
use VehicleReportOrders\Admin\FulfillmentActions;
use VehicleReportOrders\Domain\FulfillmentStatus;

defined( 'ABSPATH' ) || exit;

$vr_user      = $assignee > 0 ? get_userdata( $assignee ) : null;
$vr_can_claim = current_user_can( CapabilityManager::CLAIM_ORDER );
$vr_can_up    = current_user_can( CapabilityManager::UPLOAD_REPORT );
$vr_can_send  = current_user_can( CapabilityManager::DELIVER_REPORT );
$vr_allowed   = array_map( static fn( FulfillmentStatus $state ): string => $state->value, $transitions );
?>
<div class="vr-panel">
	<table class="widefat striped" style="margin-bottom:12px;">
		<tbody>
			<tr>
				<th style="width:200px;"><?php esc_html_e( 'Fulfilment state', 'vehicle-report-orders' ); ?></th>
				<td><strong><?php echo esc_html( $status->label() ); ?></strong></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Registration', 'vehicle-report-orders' ); ?></th>
				<td>
					<?php if ( $registration ) : ?>
						<code><?php echo esc_html( $registration->for_display() ); ?></code>
					<?php else : ?>
						<span style="color:#b93838;"><?php esc_html_e( 'Not supplied — ask the customer before preparing anything.', 'vehicle-report-orders' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Promise', 'vehicle-report-orders' ); ?></th>
				<td>
					<?php
					if ( null === $hours_left ) {
						esc_html_e( 'No delivery window was agreed when this order was placed.', 'vehicle-report-orders' );
					} elseif ( $hours_left < 0 ) {
						printf(
							'<strong style="color:#b93838;">%s</strong>',
							esc_html(
								sprintf(
									/* translators: %s: hours. */
									__( 'Overdue by %s hours', 'vehicle-report-orders' ),
									number_format_i18n( abs( $hours_left ), 1 )
								)
							)
						);
					} else {
						echo esc_html(
							sprintf(
								/* translators: %s: hours. */
								__( '%s hours remaining', 'vehicle-report-orders' ),
								number_format_i18n( $hours_left, 1 )
							)
						);
					}
					?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Assigned to', 'vehicle-report-orders' ); ?></th>
				<td><?php echo esc_html( $vr_user ? $vr_user->display_name : __( 'Nobody', 'vehicle-report-orders' ) ); ?></td>
			</tr>
		</tbody>
	</table>

	<?php if ( ! empty( $versions ) ) : ?>
		<h4><?php esc_html_e( 'Report files', 'vehicle-report-orders' ); ?></h4>
		<table class="widefat striped" style="margin-bottom:12px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Version', 'vehicle-report-orders' ); ?></th>
					<th><?php esc_html_e( 'Uploaded', 'vehicle-report-orders' ); ?></th>
					<th><?php esc_html_e( 'File', 'vehicle-report-orders' ); ?></th>
					<th><?php esc_html_e( 'SHA-256', 'vehicle-report-orders' ); ?></th>
					<th><?php esc_html_e( 'Sources', 'vehicle-report-orders' ); ?></th>
					<th><?php esc_html_e( 'State', 'vehicle-report-orders' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $versions as $vr_version ) : ?>
					<tr>
						<td>#<?php echo esc_html( (string) $vr_version->version ); ?></td>
						<td><?php echo esc_html( (string) $vr_version->created_at ); ?></td>
						<td><?php echo esc_html( (string) $vr_version->original_filename ); ?> <span class="description">(<?php echo esc_html( size_format( (int) $vr_version->size_bytes ) ); ?>)</span></td>
						<td><code style="font-size:11px;"><?php echo esc_html( substr( (string) $vr_version->sha256, 0, 16 ) ); ?>…</code></td>
						<td><?php echo esc_html( (string) $vr_version->source_summary ); ?></td>
						<td><?php echo esc_html( (string) $vr_version->status ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
		<?php wp_nonce_field( FulfillmentActions::ACTION . '_' . $order->get_id() ); ?>
		<input type="hidden" name="action" value="<?php echo esc_attr( FulfillmentActions::ACTION ); ?>">
		<input type="hidden" name="order_id" value="<?php echo esc_attr( (string) $order->get_id() ); ?>">

		<p>
			<?php if ( $vr_can_claim && 0 === $assignee ) : ?>
				<button class="button" name="vr_action" value="claim"><?php esc_html_e( 'Assign to me', 'vehicle-report-orders' ); ?></button>
			<?php endif; ?>

			<?php if ( $vr_can_claim && in_array( FulfillmentStatus::IN_PROGRESS->value, $vr_allowed, true ) ) : ?>
				<button class="button" name="vr_action" value="start"><?php esc_html_e( 'Mark in progress', 'vehicle-report-orders' ); ?></button>
			<?php endif; ?>

			<?php if ( $vr_can_claim && in_array( FulfillmentStatus::NEEDS_CUSTOMER_INFO->value, $vr_allowed, true ) ) : ?>
				<button class="button" name="vr_action" value="request_info"><?php esc_html_e( 'Needs customer information', 'vehicle-report-orders' ); ?></button>
			<?php endif; ?>

			<?php if ( $vr_can_send && null !== $current && in_array( FulfillmentStatus::DELIVERED->value, $vr_allowed, true ) ) : ?>
				<button class="button button-primary" name="vr_action" value="deliver"><?php esc_html_e( 'Deliver to customer', 'vehicle-report-orders' ); ?></button>
			<?php endif; ?>

			<?php if ( $status->grants_customer_access() ) : ?>
				<button class="button" name="vr_action" value="resend"><?php esc_html_e( 'Resend link', 'vehicle-report-orders' ); ?></button>
				<?php if ( $vr_can_send ) : ?>
					<button class="button" name="vr_action" value="revoke" onclick="return confirm('<?php echo esc_js( __( 'Links already sent will stop working. Continue?', 'vehicle-report-orders' ) ); ?>');"><?php esc_html_e( 'Revoke links', 'vehicle-report-orders' ); ?></button>
				<?php endif; ?>
			<?php endif; ?>
		</p>

		<?php if ( $vr_can_up ) : ?>
			<hr>
			<h4><?php esc_html_e( 'Upload a report', 'vehicle-report-orders' ); ?></h4>
			<p class="description">
				<?php esc_html_e( 'PDF only. Uploading stores the file privately; the customer is told nothing until you choose to deliver it.', 'vehicle-report-orders' ); ?>
			</p>
			<p>
				<label for="vr-report-file"><strong><?php esc_html_e( 'Report file', 'vehicle-report-orders' ); ?></strong></label><br>
				<input type="file" name="report_file" id="vr-report-file" accept="application/pdf">
			</p>
			<p>
				<label for="vr-sources"><strong><?php esc_html_e( 'Sources used', 'vehicle-report-orders' ); ?></strong></label><br>
				<textarea name="source_summary" id="vr-sources" rows="2" class="large-text" placeholder="<?php esc_attr_e( 'e.g. DVSA MOT history (checked 27 Oct 2026), DVLA vehicle enquiry', 'vehicle-report-orders' ); ?>"></textarea>
			</p>
			<p>
				<label for="vr-coverage"><strong><?php esc_html_e( 'What could not be checked', 'vehicle-report-orders' ); ?></strong></label><br>
				<textarea name="coverage_notes" id="vr-coverage" rows="2" class="large-text" placeholder="<?php esc_attr_e( 'e.g. no finance check available for this vehicle', 'vehicle-report-orders' ); ?>"></textarea>
			</p>
			<p><button class="button" name="vr_action" value="upload"><?php esc_html_e( 'Store report', 'vehicle-report-orders' ); ?></button></p>
		<?php endif; ?>

		<?php if ( in_array( FulfillmentStatus::CANNOT_FULFILL->value, $vr_allowed, true ) && $vr_can_claim ) : ?>
			<hr>
			<p>
				<label for="vr-reason"><strong><?php esc_html_e( 'Cannot produce this report', 'vehicle-report-orders' ); ?></strong></label><br>
				<textarea name="reason" id="vr-reason" rows="2" class="large-text" placeholder="<?php esc_attr_e( 'Why, in words the customer can be told', 'vehicle-report-orders' ); ?>"></textarea>
			</p>
			<p><button class="button" name="vr_action" value="cannot_fulfill"><?php esc_html_e( 'Mark as cannot fulfil', 'vehicle-report-orders' ); ?></button></p>
		<?php endif; ?>
	</form>
</div>
