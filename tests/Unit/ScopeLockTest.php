<?php

declare( strict_types=1 );

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * The owner's scope lock, enforced as a test.
 *
 * The specification forbids this website from ever containing a CRM, seller
 * leads, buyer matchmaking, phone-number harvesting or outbound marketing.
 * Documentation can be ignored; a failing test cannot. Comments are stripped
 * first, so a sentence explaining what the project does not do is fine while
 * code that does it is not.
 */
final class ScopeLockTest extends TestCase {

	/**
	 * Identifiers that must never appear in executable code.
	 *
	 * @var list<string>
	 */
	private const FORBIDDEN = array(
		'crm',
		'scrape',
		'scraper',
		'scraping',
		'twilio',
		'vonage',
		'nexmo',
		'send_sms',
		'sms_campaign',
		'bulk_message',
		'lead_list',
		'seller_lead',
		'buyer_match',
		'matchmaking',
		'mailchimp',
		'sendgrid_marketing',
		'opt_in_marketing',
		'harvest',
	);

	/**
	 * @dataProvider sourceDirectories
	 */
	public function test_no_out_of_scope_code_exists( string $directory ): void {
		$offences = array();

		foreach ( $this->php_files( $directory ) as $file ) {
			$code = $this->code_without_comments( (string) file_get_contents( $file ) );

			foreach ( self::FORBIDDEN as $needle ) {
				if ( str_contains( strtolower( $code ), $needle ) ) {
					$offences[] = sprintf( '%s contains "%s"', basename( $file ), $needle );
				}
			}
		}

		$this->assertSame(
			array(),
			$offences,
			"This website must never gain CRM, lead-harvesting or outbound marketing code:\n" . implode( "\n", $offences )
		);
	}

	public static function sourceDirectories(): array {
		$root = dirname( __DIR__, 2 );

		return array(
			'plugin' => array( $root . '/wp-content/plugins/vehicle-report-orders' ),
			'theme'  => array( $root . '/wp-content/themes/vehicle-report-theme' ),
		);
	}

	public function test_the_plugin_declares_no_third_party_dependencies(): void {
		$composer = dirname( __DIR__, 2 ) . '/composer.json';
		$manifest = json_decode( (string) file_get_contents( $composer ), true );

		$this->assertSame(
			array( 'php' ),
			array_keys( (array) ( $manifest['require'] ?? array() ) ),
			'Runtime dependencies would ship with the plugin; keep it to PHP itself.'
		);
	}

	/**
	 * Every PHP file under a directory.
	 *
	 * @param string $directory Directory to walk.
	 *
	 * @return list<string>
	 */
	private function php_files( string $directory ): array {
		if ( ! is_dir( $directory ) ) {
			return array();
		}

		$files    = array();
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory ) );

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && 'php' === $file->getExtension() && ! str_contains( $file->getPathname(), '/vendor/' ) ) {
				$files[] = $file->getPathname();
			}
		}

		return $files;
	}

	/**
	 * Source with comments and strings of prose removed.
	 *
	 * @param string $source PHP source.
	 *
	 * @return string
	 */
	private function code_without_comments( string $source ): string {
		$code = '';

		foreach ( token_get_all( $source ) as $token ) {
			if ( is_array( $token ) ) {
				if ( in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT, T_INLINE_HTML, T_CONSTANT_ENCAPSED_STRING ), true ) ) {
					continue;
				}

				$code .= $token[1];

				continue;
			}

			$code .= $token;
		}

		return $code;
	}
}
