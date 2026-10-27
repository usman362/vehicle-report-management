<?php
/**
 * Audit log screen.
 *
 * @package VehicleReportOrders
 *
 * @var list<object> $events   Audit rows, newest first.
 * @var int          $total    Total rows.
 * @var int          $pages    Number of pages.
 * @var int          $paged    Current page.
 * @var int          $order_id Order filter, or 0.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Audit log', 'vehicle-report-orders' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Append-only record of fulfilment actions. Entries are never edited or removed, and sensitive values are redacted before they are stored.', 'vehicle-report-orders' ); ?>
	</p>

	<table class="wp-list-table widefat striped">
		<thead>
			<tr>
				<th scope="col" style="width:160px;"><?php esc_html_e( 'When (UTC)', 'vehicle-report-orders' ); ?></th>
				<th scope="col" style="width:90px;"><?php esc_html_e( 'Order', 'vehicle-report-orders' ); ?></th>
				<th scope="col" style="width:150px;"><?php esc_html_e( 'Who', 'vehicle-report-orders' ); ?></th>
				<th scope="col" style="width:190px;"><?php esc_html_e( 'Action', 'vehicle-report-orders' ); ?></th>
				<th scope="col" style="width:200px;"><?php esc_html_e( 'State change', 'vehicle-report-orders' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Detail', 'vehicle-report-orders' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $events ) ) : ?>
			<tr><td colspan="6"><?php esc_html_e( 'Nothing recorded yet.', 'vehicle-report-orders' ); ?></td></tr>
		<?php else : ?>
			<?php
			foreach ( $events as $vr_event ) :
				$vr_actor = $vr_event->actor_id ? get_userdata( (int) $vr_event->actor_id ) : null;
				?>
				<tr>
					<td><?php echo esc_html( (string) $vr_event->created_at ); ?></td>
					<td>
						<?php if ( (int) $vr_event->order_id > 0 ) : ?>
							<a href="<?php echo esc_url( admin_url( 'post.php?post=' . (int) $vr_event->order_id . '&action=edit' ) ); ?>">#<?php echo esc_html( (string) $vr_event->order_id ); ?></a>
						<?php else : ?>
							—
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $vr_actor ? $vr_actor->display_name : __( 'System', 'vehicle-report-orders' ) ); ?></td>
					<td><code><?php echo esc_html( (string) $vr_event->action ); ?></code></td>
					<td>
						<?php if ( $vr_event->from_state || $vr_event->to_state ) : ?>
							<?php echo esc_html( sprintf( '%s → %s', (string) $vr_event->from_state, (string) $vr_event->to_state ) ); ?>
						<?php else : ?>
							—
						<?php endif; ?>
					</td>
					<td><code style="font-size:11px;"><?php echo esc_html( (string) $vr_event->context ); ?></code></td>
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
						'current'   => $paged,
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
