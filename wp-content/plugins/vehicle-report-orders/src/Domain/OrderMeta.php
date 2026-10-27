<?php
/**
 * Canonical names of the order meta keys this plugin owns.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

namespace VehicleReportOrders\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * Meta keys are listed in one place so a rename cannot drift between the
 * checkout, the queue, the emails and the audit trail.
 */
final class OrderMeta {

	public const ORIGINAL_REGISTRATION   = '_vr_original_registration';
	public const NORMALIZED_REGISTRATION = '_vr_normalized_registration';
	public const CUSTOMER_EMAIL_SNAPSHOT = '_vr_customer_email_snapshot';
	public const PACKAGE_SNAPSHOT        = '_vr_package_snapshot_json';
	public const DELIVERY_SLA_SNAPSHOT   = '_vr_delivery_sla_snapshot';
	public const TERMS_VERSION           = '_vr_terms_version_accepted';
	public const TERMS_ACCEPTED_AT       = '_vr_terms_accepted_at';
	public const PAYMENT_VERIFIED_AT     = '_vr_payment_verified_at';
	public const REPORT_STATUS           = '_vr_report_status';
	public const ASSIGNED_STAFF_ID       = '_vr_assigned_staff_id';
	public const REPORT_UPLOADED_AT      = '_vr_report_uploaded_at';
	public const DELIVERED_AT            = '_vr_delivered_at';
	public const SLA_DUE_AT              = '_vr_sla_due_at';
	public const DELIVERY_ATTEMPT_COUNT  = '_vr_delivery_attempt_count';
	public const LAST_DELIVERY_ERROR     = '_vr_last_delivery_error_code';
	public const STAFF_NOTES             = '_vr_staff_notes';
	public const CANNOT_FULFILL_REASON   = '_vr_cannot_fulfill_reason';
}
