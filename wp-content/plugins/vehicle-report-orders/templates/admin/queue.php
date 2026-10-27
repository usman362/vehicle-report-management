<?php
/**
 * Fulfilment queue screen.
 *
 * @package VehicleReportOrders
 *
 * @var \VehicleReportOrders\Admin\FulfillmentQueuePage        $this       Queue controller.
 * @var list<\WC_Order>                                        $orders     Orders on this page.
 * @var int                                                    $total      Total matching orders.
 * @var int                                                    $pages      Number of pages.
 * @var array<string, mixed>                                   $filters    Active filters.
 * @var list<\VehicleReportOrders\Domain\FulfillmentStatus>    $statuses   All fulfilment statuses.
 * @var \VehicleReportOrders\Domain\OrderRepository            $repository Order helper.
 */

declare( strict_types=1 );

use VehicleReportOrders\Admin\Menu;
use VehicleReportOrders\Domain\OrderRepository;

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Fulfilment queue', 'vehicle-report-orders' ); ?></h1>
	<span class="subtitle">
		<?php
		printf(
			/* translators: %s: number of matching orders. */
			esc_html__( '%s matching paid orders', 'vehicle-report-orders' ),
			esc_html( number_format_i18n( $total ) )
		);
		?>
	</span>

	<form method="get" style="margin:16px 0;">
		<input type="hidden" name="page" value="<?php echo esc_attr( Menu::SLUG_QUEUE ); ?>">
		<select name="status">
			<option value="outstanding" <?php selected( 'outstanding', $filters['status'] ); ?>><?php esc_html_e( 'Outstanding work', 'vehicle-report-orders' ); ?></option>
			<option value="" <?php selected( '', $filters['status'] ); ?>><?php esc_html_e( 'All report orders', 'vehicle-report-orders' ); ?></option>
			<?php foreach ( $statuses as $vr_status ) : ?>
				<option value="<?php echo esc_attr( $vr_status->value ); ?>" <?php selected( $vr_status->value, $filters['status'] ); ?>>
					<?php echo esc_html( $vr_status->label() ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<select name="assigned">
			<option value="0"><?php esc_html_e( 'Anyone', 'vehicle-report-orders' ); ?></option>
			<?php foreach ( $this->assignable_staff() as $vr_id => $vr_name ) : ?>
				<option value="<?php echo esc_attr( (string) $vr_id ); ?>" <?php selected( (int) $filters['assigned'], (int) $vr_id ); ?>>
					<?php echo esc_html( $vr_name ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<label style="margin:0 8px;">
			<input type="checkbox" name="overdue" value="1" <?php checked( (bool) $filters['overdue'] ); ?>>
			<?php esc_html_e( 'Overdue only', 'vehicle-report-orders' ); ?>
		</label>

		<input type="search" name="s" value="<?php echo esc_attr( (string) $filters['search'] ); ?>" placeholder="<?php esc_attr_e( 'Order number, registration, VIN or email', 'vehicle-report-orders' ); ?>" style="width:280px;">
		<?php submit_button( __( 'Filter', 'vehicle-report-orders' ), 'secondary', '', false ); ?>
	</form>

	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th scope="col" style="width:90px;white-space:nowrap;"><?php esc_html_e( 'Order', 'vehicle-report-orders' ); ?></th>
				<th scope="col" style="width:130px;"><?php esc_html_e( 'Registration', 'vehicle-report-orders' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Customer', 'vehicle-report-orders' ); ?></th>
				<th scope="col" style="width:110px;"><?php esc_html_e( 'Paid', 'vehicle-report-orders' ); ?></th>
				<th scope="col" style="width:170px;"><?php esc_html_e( 'Fulfilment', 'vehicle-report-orders' ); ?></th>
				<th scope="col" style="width:140px;"><?php esc_html_e( 'Promise', 'vehicle-report-orders' ); ?></th>
				<th scope="col" style="width:140px;"><?php esc_html_e( 'Assigned', 'vehicle-report-orders' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $orders ) ) : ?>
			<tr>
				<td colspan="7">
					<?php esc_html_e( 'No report orders match. Paid orders appear here automatically once the gateway confirms payment.', 'vehicle-report-orders' ); ?>
				</td>
			</tr>
		<?php else : ?>
			<?php
			foreach ( $orders as $vr_order ) :
				$vr_status       = OrderRepository::status_of( $vr_order );
				$vr_registration = OrderRepository::registration_of( $vr_order );
				$vr_hours        = OrderRepository::hours_until_due( $vr_order );
				$vr_vin          = (string) $vr_order->get_meta( \VehicleReportOrders\Domain\OrderMeta::ORIGINAL_VIN );
				$vr_snapshot     = \VehicleReportOrders\Domain\PackageRepository::snapshot_for( $vr_order );
				$vr_tier         = is_array( $vr_snapshot ) ? (string) ( $vr_snapshot['name'] ?? '' ) : '';
				$vr_assignee     = (int) $vr_order->get_meta( \VehicleReportOrders\Domain\OrderMeta::ASSIGNED_STAFF_ID );
				$vr_user         = $vr_assignee > 0 ? get_userdata( $vr_assignee ) : null;
				?>
				<tr>
					<td>
						<a href="<?php echo esc_url( $vr_order->get_edit_order_url() ); ?>">#<?php echo esc_html( (string) $vr_order->get_order_number() ); ?></a>
					</td>
					<td>
						<?php if ( $vr_registration ) : ?>
							<code><?php echo esc_html( $vr_registration->for_display() ); ?></code>
						<?php elseif ( '' !== $vr_vin ) : ?>
							<code><?php echo esc_html( $vr_vin ); ?></code>
							<br><span class="description"><?php esc_html_e( 'VIN', 'vehicle-report-orders' ); ?></span>
						<?php else : ?>
							<span style="color:#b93838;"><?php esc_html_e( 'missing', 'vehicle-report-orders' ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== $vr_tier ) : ?>
							<br><span class="description"><?php echo esc_html( $vr_tier ); ?></span>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $vr_order->get_billing_email() ); ?></td>
					<td><?php echo esc_html( $vr_order->get_date_paid() ? $vr_order->get_date_paid()->date_i18n( 'j M H:i' ) : '—' ); ?></td>
					<td><?php echo esc_html( $vr_status->label() ); ?></td>
					<td>
						<?php
						if ( null === $vr_hours ) {
							esc_html_e( 'No window agreed', 'vehicle-report-orders' );
						} elseif ( $vr_hours < 0 ) {
							printf(
								'<strong style="color:#b93838;">%s</strong>',
								esc_html(
									sprintf(
										/* translators: %s: number of hours. */
										__( '%s h overdue', 'vehicle-report-orders' ),
										number_format_i18n( abs( $vr_hours ), 1 )
									)
								)
							);
						} else {
							echo esc_html(
								sprintf(
									/* translators: %s: number of hours. */
									__( '%s h left', 'vehicle-report-orders' ),
									number_format_i18n( $vr_hours, 1 )
								)
							);
						}
						?>
					</td>
					<td><?php echo esc_html( $vr_user ? $vr_user->display_name : __( 'Unassigned', 'vehicle-report-orders' ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $pages > 1 ) : ?>
		<div class="tablenav"><div class="tablenav-pages">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'current'   => (int) $filters['page'],
						'total'     => $pages,
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
					)
				)
			);
			?>
		</div></div>
	<?php endif; ?>
</div>
