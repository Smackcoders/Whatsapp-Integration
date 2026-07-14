<?php
/**
 * Tests for WN_PhoneValidator class.
 *
 * Covers: E.164 normalisation, country-code prefix, double-prefix prevention,
 * invalid input rejection, empty input, and international formats.
 */

use PHPUnit\Framework\TestCase;
use Smackcoders\WN\WN_PhoneValidator;

class WN_PhoneValidator_Test extends TestCase {

    // -----------------------------------------------------------------------
    // 1. 10-digit local number → prefixed with default country code (91)
    // -----------------------------------------------------------------------

    public function test_10_digit_number_gets_default_country_code(): void {
        $result = WN_PhoneValidator::validate_and_normalize( '9876543210' );

        $this->assertTrue( $result['valid'] );
        $this->assertSame( '919876543210', $result['normalized'] );
        $this->assertEmpty( $result['error'] );
    }

    // -----------------------------------------------------------------------
    // 2. Number already containing country code is not double-prefixed
    // -----------------------------------------------------------------------

    public function test_already_prefixed_number_is_not_double_prefixed(): void {
        $result = WN_PhoneValidator::validate_and_normalize( '919876543210' );

        $this->assertTrue( $result['valid'] );
        // Must remain 12 digits, not become 14
        $this->assertSame( '919876543210', $result['normalized'] );
        $this->assertStringStartsWith( '91', $result['normalized'] );
    }

    // -----------------------------------------------------------------------
    // 3. Number with leading + is normalised correctly
    // -----------------------------------------------------------------------

    public function test_plus_prefixed_e164_number_normalises_correctly(): void {
        $result = WN_PhoneValidator::validate_and_normalize( '+919876543210' );

        $this->assertTrue( $result['valid'] );
        $this->assertSame( '919876543210', $result['normalized'] );
    }

    // -----------------------------------------------------------------------
    // 4. Empty input is rejected
    // -----------------------------------------------------------------------

    public function test_empty_phone_number_is_rejected(): void {
        $result = WN_PhoneValidator::validate_and_normalize( '' );

        $this->assertFalse( $result['valid'] );
        $this->assertEmpty( $result['normalized'] );
        $this->assertNotEmpty( $result['error'] );
    }

    // -----------------------------------------------------------------------
    // 5. Whitespace-only input is rejected
    // -----------------------------------------------------------------------

    public function test_whitespace_only_input_is_rejected(): void {
        $result = WN_PhoneValidator::validate_and_normalize( '   ' );

        $this->assertFalse( $result['valid'] );
        $this->assertNotEmpty( $result['error'] );
    }

    // -----------------------------------------------------------------------
    // 6. Number that is too short is rejected
    // -----------------------------------------------------------------------

    public function test_number_too_short_is_rejected(): void {
        // 5 digits — below the E.164 minimum of 7
        $result = WN_PhoneValidator::validate_and_normalize( '12345' );

        $this->assertFalse( $result['valid'] );
        $this->assertNotEmpty( $result['error'] );
    }

    // -----------------------------------------------------------------------
    // 7. Number that is too long is rejected
    // -----------------------------------------------------------------------

    public function test_number_too_long_is_rejected(): void {
        // 16 digits — above the E.164 maximum of 15
        $result = WN_PhoneValidator::validate_and_normalize( '1234567890123456' );

        $this->assertFalse( $result['valid'] );
        $this->assertNotEmpty( $result['error'] );
    }

    // -----------------------------------------------------------------------
    // 8. International format (US number) accepted when 12 digits total
    // -----------------------------------------------------------------------

    public function test_us_number_with_country_code_accepted(): void {
        // US: +1 followed by 10 digits = 11 digits total
        $result = WN_PhoneValidator::validate_and_normalize( '+12125551234' );

        $this->assertTrue( $result['valid'] );
        $this->assertSame( '12125551234', $result['normalized'] );
    }

    // -----------------------------------------------------------------------
    // 9. Custom country code is used for 10-digit numbers
    // -----------------------------------------------------------------------

    public function test_custom_country_code_applied_to_10_digit_number(): void {
        $result = WN_PhoneValidator::validate_and_normalize( '2025551234', '1' );

        $this->assertTrue( $result['valid'] );
        $this->assertSame( '12025551234', $result['normalized'] );
    }

    // -----------------------------------------------------------------------
    // 10. Dashes and spaces are stripped before validation
    // -----------------------------------------------------------------------

    public function test_dashes_and_spaces_are_stripped(): void {
        $result = WN_PhoneValidator::validate_and_normalize( '98-765-43210' );

        $this->assertTrue( $result['valid'] );
        $this->assertSame( '919876543210', $result['normalized'] );
    }

    // -----------------------------------------------------------------------
    // 11. DEFAULT_COUNTRY_CODE constant is '91'
    // -----------------------------------------------------------------------

    public function test_default_country_code_constant_is_india(): void {
        $this->assertSame( '91', WN_PhoneValidator::DEFAULT_COUNTRY_CODE );
    }
}
