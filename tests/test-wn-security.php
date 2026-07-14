<?php
/**
 * Tests for WN_Security class.
 *
 * Covers: encrypt/decrypt, sensitive-key detection, encrypt_if_sensitive,
 * decrypt_if_sensitive, and XSS prevention via esc_html.
 */

use PHPUnit\Framework\TestCase;
use Smackcoders\WN\WN_Security;

class WN_Security_Test extends TestCase {

    // -----------------------------------------------------------------------
    // 1. Encrypt / Decrypt round-trip
    // -----------------------------------------------------------------------

    public function test_encrypt_and_decrypt_round_trip(): void {
        $original  = 'super-secret-api-key-12345';
        $encrypted = WN_Security::encrypt( $original );

        $this->assertNotEmpty( $encrypted, 'Encrypted value must not be empty.' );
        $this->assertNotEquals( $original, $encrypted, 'Encrypted value must differ from original.' );
        $this->assertStringStartsWith( 'WN::', $encrypted, 'Encrypted value must start with WN:: prefix.' );

        $decrypted = WN_Security::decrypt( $encrypted );
        $this->assertSame( $original, $decrypted, 'Decrypted value must match the original.' );
    }

    // -----------------------------------------------------------------------
    // 2. Empty values are passed through unchanged
    // -----------------------------------------------------------------------

    public function test_encrypt_empty_string_returns_empty(): void {
        $this->assertSame( '', WN_Security::encrypt( '' ) );
    }

    public function test_decrypt_empty_string_returns_empty(): void {
        $this->assertSame( '', WN_Security::decrypt( '' ) );
    }

    // -----------------------------------------------------------------------
    // 3. is_sensitive identifies known sensitive keys
    // -----------------------------------------------------------------------

    public function test_is_sensitive_returns_true_for_known_keys(): void {
        $known_sensitive = [
            'twilio_sid',
            'twilio_token',
            'infobip_api_key',
            'gupshup_api_key',
            'cloud_access_token',
            'sendgrid_api_key',
        ];

        foreach ( $known_sensitive as $key ) {
            $this->assertTrue(
                WN_Security::is_sensitive( $key ),
                "Expected '$key' to be marked as sensitive."
            );
        }
    }

    public function test_is_sensitive_returns_false_for_non_sensitive_keys(): void {
        $non_sensitive = [ 'provider', 'admin_phone_number', 'email_provider', 'invoice_enabled' ];

        foreach ( $non_sensitive as $key ) {
            $this->assertFalse(
                WN_Security::is_sensitive( $key ),
                "Expected '$key' NOT to be marked as sensitive."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 4. encrypt_if_sensitive only encrypts sensitive keys
    // -----------------------------------------------------------------------

    public function test_encrypt_if_sensitive_encrypts_sensitive_key(): void {
        $value     = 'my-api-key';
        $encrypted = WN_Security::encrypt_if_sensitive( 'twilio_sid', $value );

        $this->assertStringStartsWith( 'WN::', $encrypted, 'Sensitive key value should be encrypted.' );
    }

    public function test_encrypt_if_sensitive_leaves_non_sensitive_key_unchanged(): void {
        $value  = 'some-plain-value';
        $result = WN_Security::encrypt_if_sensitive( 'provider', $value );

        $this->assertSame( $value, $result, 'Non-sensitive key value must not be modified.' );
    }

    // -----------------------------------------------------------------------
    // 5. decrypt_if_sensitive only decrypts WN::-prefixed sensitive values
    // -----------------------------------------------------------------------

    public function test_decrypt_if_sensitive_decrypts_only_when_prefixed(): void {
        $original  = 'gupshup-api-secret';
        $encrypted = WN_Security::encrypt( $original );

        $result = WN_Security::decrypt_if_sensitive( 'gupshup_api_key', $encrypted );
        $this->assertSame( $original, $result );
    }

    public function test_decrypt_if_sensitive_ignores_non_sensitive_key(): void {
        $value  = 'plain-text-setting';
        $result = WN_Security::decrypt_if_sensitive( 'admin_phone_number', $value );

        $this->assertSame( $value, $result, 'Non-sensitive key value must pass through untouched.' );
    }

    // -----------------------------------------------------------------------
    // 6. XSS prevention — esc_html stub strips dangerous content
    // -----------------------------------------------------------------------

    public function test_esc_html_prevents_xss(): void {
        $dangerous = '<script>alert("xss")</script>';
        $safe      = esc_html( $dangerous );

        $this->assertStringNotContainsString( '<script>', $safe, 'esc_html must escape < characters.' );
        $this->assertStringContainsString( '&lt;script&gt;', $safe );
    }

    // -----------------------------------------------------------------------
    // 7. Different plaintexts produce different ciphertexts
    // -----------------------------------------------------------------------

    public function test_different_plaintexts_produce_different_ciphertexts(): void {
        $enc1 = WN_Security::encrypt( 'value-one' );
        $enc2 = WN_Security::encrypt( 'value-two' );

        $this->assertNotSame( $enc1, $enc2 );
    }

    // -----------------------------------------------------------------------
    // 8. Decrypting without WN:: prefix on a sensitive key returns value as-is
    // -----------------------------------------------------------------------

    public function test_decrypt_if_sensitive_without_prefix_returns_raw_value(): void {
        // If the stored value was never encrypted (legacy), return it unchanged.
        $raw    = 'legacy-plain-api-key';
        $result = WN_Security::decrypt_if_sensitive( 'twilio_token', $raw );

        $this->assertSame( $raw, $result );
    }
}
