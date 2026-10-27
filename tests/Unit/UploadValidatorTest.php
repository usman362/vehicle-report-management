<?php

declare( strict_types=1 );

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use VehicleReportOrders\Reports\UploadValidator;

final class UploadValidatorTest extends TestCase {

	public function test_it_recognises_a_pdf_by_its_bytes(): void {
		$this->assertTrue( UploadValidator::looks_like_pdf( "%PDF-1.7\n..." ) );
		$this->assertFalse( UploadValidator::looks_like_pdf( "GIF89a" ) );
		$this->assertFalse( UploadValidator::looks_like_pdf( "<?php echo 'hi';" ) );
	}

	public function test_it_rejects_a_script_renamed_as_a_pdf(): void {
		$this->assertTrue( UploadValidator::has_pdf_extension( 'report.pdf' ) );
		$this->assertTrue( UploadValidator::has_pdf_extension( 'REPORT.PDF' ) );
		$this->assertFalse( UploadValidator::has_pdf_extension( 'report.php' ) );
		$this->assertFalse( UploadValidator::has_pdf_extension( 'report.pdf.php' ) );
	}

	public function test_it_spots_embedded_markup(): void {
		$this->assertTrue( UploadValidator::contains_executable_markup( '%PDF-1.4 <?php system($_GET["c"]); ?>' ) );
		$this->assertTrue( UploadValidator::contains_executable_markup( '%PDF-1.4 <script>alert(1)</script>' ) );
		$this->assertFalse( UploadValidator::contains_executable_markup( '%PDF-1.4 /Type /Catalog' ) );
	}

	public function test_storage_keys_are_unguessable_and_scoped_to_the_order(): void {
		$first  = UploadValidator::storage_key( 42, 1 );
		$second = UploadValidator::storage_key( 42, 1 );

		$this->assertNotSame( $first, $second, 'Two uploads must never share a key.' );
		$this->assertStringStartsWith( '42/1-', $first );
		$this->assertStringEndsWith( '.pdf', $first );
		$this->assertMatchesRegularExpression( '#^42/1-[0-9a-f]{32}\.pdf$#', $first );
	}

	public function test_it_rejects_an_unreadable_or_empty_file(): void {
		$result = UploadValidator::check( '/definitely/not/here.pdf', 'here.pdf', 10 );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'unreadable', $result['code'] );
	}

	public function test_it_accepts_a_real_pdf_and_rejects_a_disguised_script(): void {
		$good = tempnam( sys_get_temp_dir(), 'vr' );
		file_put_contents( $good, "%PDF-1.7\n1 0 obj\n<< /Type /Catalog >>\nendobj\n" );

		$result = UploadValidator::check( $good, 'report.pdf', (int) filesize( $good ) );
		$this->assertTrue( $result['ok'], $result['message'] );

		$bad = tempnam( sys_get_temp_dir(), 'vr' );
		file_put_contents( $bad, "<?php system( \$_GET['cmd'] );" );

		$result = UploadValidator::check( $bad, 'report.pdf', (int) filesize( $bad ) );
		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'not_pdf', $result['code'] );

		unlink( $good );
		unlink( $bad );
	}

	public function test_it_rejects_a_pdf_with_a_script_payload_in_the_header(): void {
		$file = tempnam( sys_get_temp_dir(), 'vr' );
		file_put_contents( $file, "%PDF-1.7\n<script>fetch('//evil.example')</script>\n" );

		$result = UploadValidator::check( $file, 'report.pdf', (int) filesize( $file ) );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'embedded_code', $result['code'] );

		unlink( $file );
	}
}
