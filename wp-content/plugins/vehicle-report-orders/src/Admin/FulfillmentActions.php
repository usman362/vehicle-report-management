<?php
/**
 * Staff actions on a single order.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Admin;

use VehicleReportOrders\Audit\AuditLogger;
use VehicleReportOrders\Domain\FulfillmentStatus;
use VehicleReportOrders\Domain\OrderMeta;
use VehicleReportOrders\Domain\OrderRepository;
use VehicleReportOrders\Notifications\EmailNotifier;
use VehicleReportOrders\Domain\Settings;
use VehicleReportOrders\Reports\Coverage;
use VehicleReportOrders\Reports\ReportService;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the buttons on the order screen.
 *
 * Every action checks a capability and a nonce, refuses a transition the state
 * machine does not allow, and writes to the audit trail whether it succeeded or
 * not. A failed permission check is itself worth recording.
 */
final class FulfillmentActions {

	public const ACTION = 'vr_fulfilment_action';

	/**
	 * Report storage.
	 *
	 * @var ReportService
	 */
	private ReportService $reports;

	/**
	 * Email.
	 *
	 * @var EmailNotifier
	 */
	private EmailNotifier $notifier;

	/**
	 * Constructor.
	 *
	 * @param ReportService $reports  Report storage.
	 * @param EmailNotifier $notifier Email.
	 */
	public function __construct( ReportService $reports, EmailNotifier $notifier ) {
		$this->reports  = $reports;
		$this->notifier = $notifier;
	}

	/**
	 * Listen for submissions.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Route a submitted action.
	 *
	 * @return void
	 */
	public function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified immediately below.
		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
		$action   = isset( $_POST['vr_action'] ) ? sanitize_key( wp_unslash( $_POST['vr_action'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		check_admin_referer( self::ACTION . '_' . $order_id );

		$this->finish( $order_id, $this->run( $order_id, $action ) );
	}

	/**
	 * Carry out an action on behalf of the current user.
	 *
	 * Separate from handle() so the request plumbing — the nonce and the
	 * redirect — is not in the way of exercising the permission rules.
	 * Every action re-checks the current user's capability for itself;
	 * reaching this method is not permission to do anything.
	 *
	 * @param int    $order_id Order identifier.
	 * @param string $action   Action name as submitted.
	 *
	 * @return string|WP_Error Message for the operator, or the refusal.
	 */
	public function run( int $order_id, string $action ) {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {
			return new WP_Error( 'vr_unknown_order', __( 'That order no longer exists.', 'vehicle-report-orders' ) );
		}

		return match ( $action ) {
			'claim'          => $this->claim( $order ),
			'start'          => $this->move( $order, FulfillmentStatus::IN_PROGRESS, 'fulfilment.started' ),
			'request_info'   => $this->move( $order, FulfillmentStatus::NEEDS_CUSTOMER_INFO, 'fulfilment.info_requested' ),
			'cannot_fulfill' => $this->cannot_fulfill( $order ),
			'upload'         => $this->upload( $order ),
			'deliver'        => $this->deliver( $order ),
			'resend'         => $this->resend( $order ),
			'revoke'         => $this->revoke( $order ),
			default          => new WP_Error( 'vr_unknown_action', __( 'Unknown action.', 'vehicle-report-orders' ) ),
		};
	}

	/**
	 * Take ownership of an order.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return string|WP_Error
	 */
	private function claim( \WC_Order $order ) {
		if ( ! current_user_can( CapabilityManager::CLAIM_ORDER ) ) {
			return $this->denied( $order, 'claim' );
		}

		$order->update_meta_data( OrderMeta::ASSIGNED_STAFF_ID, get_current_user_id() );
		$order->save();

		AuditLogger::log( $order->get_id(), 'fulfilment.claimed' );

		return __( 'Order assigned to you.', 'vehicle-report-orders' );
	}

	/**
	 * Move an order to another fulfilment state.
	 *
	 * @param \WC_Order         $order  Order.
	 * @param FulfillmentStatus $target Desired state.
	 * @param string            $event  Audit event name.
	 *
	 * @return string|WP_Error
	 */
	private function move( \WC_Order $order, FulfillmentStatus $target, string $event ) {
		if ( ! current_user_can( CapabilityManager::CLAIM_ORDER ) ) {
			return $this->denied( $order, $event );
		}

		$current = OrderRepository::status_of( $order );

		if ( ! $current->can_move_to( $target ) ) {
			return new WP_Error(
				'vr_bad_transition',
				sprintf(
					/* translators: 1: current state, 2: requested state. */
					__( 'An order cannot go from "%1$s" to "%2$s".', 'vehicle-report-orders' ),
					$current->label(),
					$target->label()
				)
			);
		}

		$order->update_meta_data( OrderMeta::REPORT_STATUS, $target->value );
		$order->save();

		AuditLogger::log( $order->get_id(), $event, array(), $current->value, $target->value );

		return sprintf(
			/* translators: %s: new state. */
			__( 'Order moved to "%s".', 'vehicle-report-orders' ),
			$target->label()
		);
	}

	/**
	 * Record that the report cannot be produced.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return string|WP_Error
	 */
	private function cannot_fulfill( \WC_Order $order ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in handle().
		$reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';

		if ( '' === trim( $reason ) ) {
			return new WP_Error( 'vr_reason_required', __( 'Say why the report cannot be produced; the customer is owed an explanation.', 'vehicle-report-orders' ) );
		}

		$order->update_meta_data( OrderMeta::CANNOT_FULFILL_REASON, $reason );
		$order->save();

		return $this->move( $order, FulfillmentStatus::CANNOT_FULFILL, 'fulfilment.cannot_fulfill' );
	}

	/**
	 * Store an uploaded report.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return string|WP_Error
	 */
	private function upload( \WC_Order $order ) {
		if ( ! current_user_can( CapabilityManager::UPLOAD_REPORT ) ) {
			return $this->denied( $order, 'upload' );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified in handle().
		$sources = isset( $_POST['source_summary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['source_summary'] ) ) : '';
		$notes   = isset( $_POST['coverage_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['coverage_notes'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$outcomes = $this->posted_list( 'coverage_outcome', 'sanitize_key' );
		$reasons  = $this->posted_list( 'coverage_reason', 'sanitize_textarea_field' );

		$coverage = Coverage::from_input( $outcomes, $reasons, Settings::source_list() );

		if ( is_wp_error( $coverage ) ) {
			return $coverage;
		}

		if ( '' === trim( $sources ) ) {
			return new WP_Error(
				'vr_sources_required',
				__( 'List the sources this report is based on. A report with no stated source cannot be sent.', 'vehicle-report-orders' )
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The nonce is checked in handle(); the upload itself is validated by UploadValidator.
		$file = isset( $_FILES['report_file'] ) ? (array) $_FILES['report_file'] : array();

		if ( array() === $file || UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
			return new WP_Error( 'vr_no_file', __( 'Choose a PDF to upload.', 'vehicle-report-orders' ) );
		}

		$version_id = $this->reports->store_upload( $order, $file, $sources, $notes, $coverage );

		if ( is_wp_error( $version_id ) ) {
			return $version_id;
		}

		return $coverage->is_complete()
			? __( 'Report stored. Nothing has been sent to the customer yet.', 'vehicle-report-orders' )
			: sprintf(
				/* translators: %s: coverage summary, for example "2 of 3 sources checked". */
				__( 'Report stored (%s). The customer will be told which checks were not possible. Nothing has been sent yet.', 'vehicle-report-orders' ),
				$coverage->summary()
			);
	}

	/**
	 * Release the report and email the customer.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return string|WP_Error
	 */
	private function deliver( \WC_Order $order ) {
		if ( ! current_user_can( CapabilityManager::DELIVER_REPORT ) ) {
			return $this->denied( $order, 'deliver' );
		}

		$delivered = $this->reports->mark_delivered( $order );

		if ( is_wp_error( $delivered ) ) {
			return $delivered;
		}

		if ( ! $this->notifier->send_report_ready( $order ) ) {
			return new WP_Error(
				'vr_delivery_email_failed',
				__( 'The report was released but the email could not be sent. A retry has been scheduled.', 'vehicle-report-orders' )
			);
		}

		return __( 'Report delivered and emailed to the customer.', 'vehicle-report-orders' );
	}

	/**
	 * Send the delivery email again.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return string|WP_Error
	 */
	private function resend( \WC_Order $order ) {
		if ( ! current_user_can( CapabilityManager::RESEND_REPORT ) ) {
			return $this->denied( $order, 'resend' );
		}

		if ( OrderRepository::is_fully_refunded( $order ) ) {
			AuditLogger::log( $order->get_id(), 'report.resend_refused', array( 'reason' => 'full_refund' ) );

			return new WP_Error( 'vr_refunded', __( 'This order has been refunded in full, so the report cannot be sent again.', 'vehicle-report-orders' ) );
		}

		if ( ! OrderRepository::status_of( $order )->grants_customer_access() ) {
			return new WP_Error( 'vr_not_delivered', __( 'Only a delivered report can be resent.', 'vehicle-report-orders' ) );
		}

		AuditLogger::log( $order->get_id(), 'report.resent' );

		return $this->notifier->send_report_ready( $order )
			? __( 'A fresh link has been emailed to the customer.', 'vehicle-report-orders' )
			: new WP_Error( 'vr_resend_failed', __( 'The email could not be sent. A retry has been scheduled.', 'vehicle-report-orders' ) );
	}

	/**
	 * Invalidate links already sent.
	 *
	 * @param \WC_Order $order Order.
	 *
	 * @return string|WP_Error
	 */
	private function revoke( \WC_Order $order ) {
		if ( ! current_user_can( CapabilityManager::DELIVER_REPORT ) ) {
			return $this->denied( $order, 'revoke' );
		}

		$this->reports->revoke_links( $order );

		return __( 'Existing download links no longer work. Use resend to issue a new one.', 'vehicle-report-orders' );
	}

	/**
	 * Record and report a refused action.
	 *
	 * @param \WC_Order $order  Order.
	 * @param string    $action Attempted action.
	 *
	 * @return WP_Error
	 */
	private function denied( \WC_Order $order, string $action ): WP_Error {
		AuditLogger::log( $order->get_id(), 'permission.denied', array( 'attempted' => $action ) );

		return new WP_Error( 'vr_forbidden', __( 'You do not have permission to do that.', 'vehicle-report-orders' ) );
	}

	/**
	 * A submitted list of scalars, each passed through a sanitiser.
	 *
	 * Written out rather than inlined so there is one place where raw input is
	 * read, and so nested arrays — which would blow up a sanitiser expecting a
	 * string — are dropped rather than passed along.
	 *
	 * @param string   $key       Key in the request body.
	 * @param callable $sanitizer Sanitiser applied to each value.
	 *
	 * @return array<int|string, string>
	 */
	private function posted_list( string $key, callable $sanitizer ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The nonce is checked in handle(); every value is sanitised immediately below.
		$raw = isset( $_POST[ $key ] ) ? (array) wp_unslash( $_POST[ $key ] ) : array();
		$out = array();

		foreach ( $raw as $index => $value ) {
			if ( is_scalar( $value ) ) {
				$out[ $index ] = (string) call_user_func( $sanitizer, (string) $value );
			}
		}

		return $out;
	}

	/**
	 * Return to the order screen with a message.
	 *
	 * @param int             $order_id Order identifier.
	 * @param string|WP_Error $result   Outcome.
	 *
	 * @return never
	 */
	private function finish( int $order_id, $result ) {
		$redirect = $order_id > 0
			? admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id )
			: Menu::url( Menu::SLUG_QUEUE );

		$redirect = add_query_arg(
			is_wp_error( $result )
				? array(
					'vr_notice'  => 'error',
					'vr_message' => rawurlencode( $result->get_error_message() ),
				)
				: array(
					'vr_notice'  => 'success',
					'vr_message' => rawurlencode( (string) $result ),
				),
			$redirect
		);

		wp_safe_redirect( $redirect );
		exit;
	}
}
