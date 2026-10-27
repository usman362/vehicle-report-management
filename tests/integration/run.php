<?php
/**
 * Integration tests that run inside a real WordPress and WooCommerce.
 *
 * Run with:  wp eval-file tests/integration/run.php
 *
 * The unit suite under tests/Unit covers the pure domain logic with no
 * WordPress at all. These cases cover what only the real runtime can answer:
 * whether a capability actually stops a staff member, whether a failed
 * delivery really schedules a retry, and whether two orders for the same
 * vehicle really stay apart. Each case cleans up the rows it created.
 *
 * @package VehicleReportOrders
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

if ( ! class_exists( 'VR_Integration_Results' ) ) {
	/**
	 * Collects pass and fail counts across the cases.
	 */
	class VR_Integration_Results {

		/**
		 * Passing assertions.
		 *
		 * @var int
		 */
		public static int $passed = 0;

		/**
		 * Failures, as human-readable lines.
		 *
		 * @var array<int, string>
		 */
		public static array $failures = array();

		/**
		 * The case being run.
		 *
		 * @var string
		 */
		public static string $case = '';
	}
}

if ( ! function_exists( 'vr_it_case' ) ) {
	/**
	 * Start a named case.
	 *
	 * @param string $name Case name.
	 *
	 * @return void
	 */
	function vr_it_case( string $name ): void {
		VR_Integration_Results::$case = $name;
		WP_CLI::log( "  {$name}" );
	}

	/**
	 * Assert a condition.
	 *
	 * @param string $what      What is being asserted, in words.
	 * @param bool   $condition The condition.
	 * @param string $detail    Extra context shown on failure.
	 *
	 * @return bool The condition, so a case can stop early.
	 */
	function vr_it_ok( string $what, bool $condition, string $detail = '' ): bool {
		if ( $condition ) {
			++VR_Integration_Results::$passed;
			WP_CLI::log( '    ✓ ' . $what );

			return true;
		}

		VR_Integration_Results::$failures[] = VR_Integration_Results::$case . ': ' . $what . ( '' !== $detail ? " ({$detail})" : '' );
		WP_CLI::log( '    ✗ ' . $what . ( '' !== $detail ? " — {$detail}" : '' ) );

		return false;
	}

	/**
	 * Assert two values match.
	 *
	 * @param string $what     What is being asserted.
	 * @param mixed  $expected Expected value.
	 * @param mixed  $actual   Actual value.
	 *
	 * @return bool
	 */
	function vr_it_is( string $what, $expected, $actual ): bool {
		return vr_it_ok(
			$what,
			$expected === $actual,
			sprintf( 'expected %s, got %s', wp_json_encode( $expected ), wp_json_encode( $actual ) )
		);
	}

	/**
	 * A throwaway paid order with a registration on it.
	 *
	 * @param string $registration Registration mark.
	 * @param string $email        Billing email address.
	 *
	 * @return WC_Order
	 */
	function vr_it_order( string $registration, string $email = 'integration@example.test' ): WC_Order {
		$order = wc_create_order();
		$order->set_billing_email( $email );
		$order->update_meta_data( VehicleReportOrders\Domain\OrderMeta::ORIGINAL_REGISTRATION, $registration );
		$order->update_meta_data( VehicleReportOrders\Domain\OrderMeta::NORMALIZED_REGISTRATION, strtoupper( str_replace( ' ', '', $registration ) ) );
		$order->update_meta_data( VehicleReportOrders\Domain\OrderMeta::REPORT_STATUS, VehicleReportOrders\Domain\FulfillmentStatus::AWAITING_STAFF->value );
		$order->update_meta_data( '_vr_integration_fixture', '1' );
		$order->save();

		return $order;
	}

	/**
	 * A stored report file for an order.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return string Storage key.
	 */
	function vr_it_report( WC_Order $order ): string {
		$key  = 'integration-' . bin2hex( random_bytes( 8 ) ) . '.pdf';
		$path = trailingslashit( VehicleReportOrders\Reports\PrivateStorage::base_path() ) . $key;

		file_put_contents( $path, "%PDF-1.4\nintegration fixture\n%%EOF\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		( new VehicleReportOrders\Reports\ReportVersionRepository() )->add(
			$order->get_id(),
			array(
				'storage_key'       => $key,
				'original_filename' => 'integration.pdf',
				'mime_type'         => 'application/pdf',
				'size_bytes'        => (int) filesize( $path ),
				'sha256'            => (string) hash_file( 'sha256', $path ),
				'prepared_by'       => get_current_user_id(),
				'source_summary'    => 'integration fixture',
				'coverage_notes'    => '',
			)
		);

		return $key;
	}

	/**
	 * Remove every fixture this suite created.
	 *
	 * @return int Orders removed.
	 */
	function vr_it_cleanup(): int {
		$removed = 0;

		foreach ( wc_get_orders(
			array(
				'limit'      => -1,
				'meta_key'   => '_vr_integration_fixture', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => '1',                       // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		) as $order ) {
			foreach ( ( new VehicleReportOrders\Reports\ReportVersionRepository() )->all_for( $order->get_id() ) as $version ) {
				$path = VehicleReportOrders\Reports\PrivateStorage::path_for( (string) $version->storage_key );

				if ( null !== $path && file_exists( $path ) ) {
					wp_delete_file( $path );
				}
			}

			$order->delete( true );
			++$removed;
		}

		foreach ( get_users( array( 'search' => 'vr-integration-*', 'search_columns' => array( 'user_login' ) ) ) as $user ) {
			wp_delete_user( $user->ID );
		}

		return $removed;
	}
}

WP_CLI::log( 'Integration tests' );

vr_it_cleanup();

foreach ( glob( __DIR__ . '/cases/*.php' ) as $vr_case_file ) {
	require $vr_case_file;
}

$vr_cleaned = vr_it_cleanup();
WP_CLI::log( "  cleanup: removed {$vr_cleaned} fixture orders" );

if ( array() !== VR_Integration_Results::$failures ) {
	foreach ( VR_Integration_Results::$failures as $vr_failure ) {
		WP_CLI::warning( $vr_failure );
	}

	WP_CLI::error(
		sprintf(
			'%d assertions passed, %d failed.',
			VR_Integration_Results::$passed,
			count( VR_Integration_Results::$failures )
		)
	);
}

WP_CLI::success( sprintf( '%d assertions passed.', VR_Integration_Results::$passed ) );
