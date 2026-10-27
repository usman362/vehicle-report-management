<?php
/**
 * Plugin Name: Vehicle Report — development mail catcher
 * Description: Writes outgoing email to storage/mail instead of sending it. Local development only; it refuses to run in any other environment.
 *
 * @package VehicleReportOrders
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

if ( 'local' !== wp_get_environment_type() ) {
	return;
}

add_filter(
	'pre_wp_mail',
	static function ( $short_circuit, array $atts ) {
		$directory = dirname( ABSPATH ) . '/vr-dev-mail';

		if ( ! wp_mkdir_p( $directory ) ) {
			return $short_circuit;
		}

		$to      = is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : (string) $atts['to'];
		$headers = is_array( $atts['headers'] ) ? implode( "\n", $atts['headers'] ) : (string) $atts['headers'];

		$contents = sprintf(
			"Date: %s\nTo: %s\nSubject: %s\n%s\n\n%s\n",
			gmdate( 'c' ),
			$to,
			(string) $atts['subject'],
			$headers,
			(string) $atts['message']
		);

		$file = sprintf( '%s/%s-%s.eml', $directory, gmdate( 'Ymd-His' ), substr( md5( $to . $atts['subject'] . microtime() ), 0, 8 ) );

		file_put_contents( $file, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Development-only mail capture.

		// Tell WordPress the message was handled, so nothing leaves the machine.
		return true;
	},
	10,
	2
);
