<?php
/**
 * Tests for WN_Logger class.
 *
 * Covers: log level constants, should_log filtering, level_to_code mapping,
 * DB persistence for ERROR/CRITICAL, and no DB write for lower levels.
 */

use PHPUnit\Framework\TestCase;
use Smackcoders\WN\WN_Logger;

class WN_Logger_Test extends TestCase {

    /**
     * Reset static min_level to INFO before each test so tests are isolated.
     */
    protected function setUp(): void {
        parent::setUp();
        WN_Logger::set_min_level( WN_Logger::LEVEL_INFO );
    }

    // -----------------------------------------------------------------------
    // 1. Level constants are defined and have expected string values
    // -----------------------------------------------------------------------

    public function test_level_constants_are_defined(): void {
        $this->assertSame( 'DEBUG',    WN_Logger::LEVEL_DEBUG );
        $this->assertSame( 'INFO',     WN_Logger::LEVEL_INFO );
        $this->assertSame( 'WARNING',  WN_Logger::LEVEL_WARNING );
        $this->assertSame( 'ERROR',    WN_Logger::LEVEL_ERROR );
        $this->assertSame( 'CRITICAL', WN_Logger::LEVEL_CRITICAL );
    }

    // -----------------------------------------------------------------------
    // 2. DEBUG messages are suppressed when min_level is INFO (default)
    // -----------------------------------------------------------------------

    public function test_debug_suppressed_below_min_level(): void {
        global $wpdb;
        $before = count( $wpdb->inserts );

        WN_Logger::debug( 'This should not be logged or persisted.' );

        // No new DB inserts should have been added
        $this->assertSame( $before, count( $wpdb->inserts ), 'DEBUG must not be persisted when below min level.' );
    }

    // -----------------------------------------------------------------------
    // 3. ERROR level IS persisted to DB via write_to_db (called from log())
    // -----------------------------------------------------------------------

    public function test_error_level_is_persisted_to_db(): void {
        global $wpdb;
        $before = count( $wpdb->inserts );

        // error_log() is used internally; suppress output during test
        WN_Logger::error( 'Test error message', [ 'order_id' => 42 ] );

        $after = count( $wpdb->inserts );
        $this->assertGreaterThan( $before, $after, 'ERROR should trigger a DB insert.' );

        $last_insert = end( $wpdb->inserts );
        $this->assertStringContainsString( 'whatsapp_error_logs', $last_insert['table'] );
        $this->assertArrayHasKey( 'error_message', $last_insert['data'] );
        $this->assertStringContainsString( 'Test error message', $last_insert['data']['error_message'] );
    }

    // -----------------------------------------------------------------------
    // 4. CRITICAL level IS persisted to DB
    // -----------------------------------------------------------------------

    public function test_critical_level_is_persisted_to_db(): void {
        global $wpdb;
        $before = count( $wpdb->inserts );

        WN_Logger::critical( 'Critical system failure detected.' );

        $this->assertGreaterThan( $before, count( $wpdb->inserts ), 'CRITICAL should trigger a DB insert.' );

        $last_insert = end( $wpdb->inserts );
        $this->assertSame( 503, $last_insert['data']['error_code'] );
    }

    // -----------------------------------------------------------------------
    // 5. INFO level is NOT automatically persisted to DB
    // -----------------------------------------------------------------------

    public function test_info_level_not_persisted_to_db(): void {
        global $wpdb;
        $before = count( $wpdb->inserts );

        WN_Logger::info( 'Informational message — should not hit DB.' );

        $this->assertSame( $before, count( $wpdb->inserts ), 'INFO must not be persisted to DB automatically.' );
    }

    // -----------------------------------------------------------------------
    // 6. set_min_level allows DEBUG to pass through when min is DEBUG
    // -----------------------------------------------------------------------

    public function test_set_min_level_allows_debug_when_min_is_debug(): void {
        WN_Logger::set_min_level( WN_Logger::LEVEL_DEBUG );

        global $wpdb;
        $before = count( $wpdb->inserts );

        // DEBUG itself does NOT persist to DB even when allowed through (to_db = false)
        // We just verify it doesn't throw and doesn't block the call
        // (Full output goes to error_log which we cannot easily assert here)
        $this->addToAssertionCount( 1 ); // marks test as not risky
        WN_Logger::debug( 'Debug message with min_level=DEBUG.' );

        // Still no DB write (debug doesn't force to_db=true)
        $this->assertSame( $before, count( $wpdb->inserts ) );
    }

    // -----------------------------------------------------------------------
    // 7. WARNING convenience method forces DB persistence (to_db=true)
    // -----------------------------------------------------------------------

    public function test_warning_convenience_method_persists_to_db(): void {
        global $wpdb;
        $before = count( $wpdb->inserts );

        WN_Logger::warning( 'Low disk space detected.' );

        $this->assertGreaterThan( $before, count( $wpdb->inserts ), 'WARNING convenience wrapper forces DB persist.' );
    }

    // -----------------------------------------------------------------------
    // 8. Context array is serialised into the stored error_message
    // -----------------------------------------------------------------------

    public function test_context_is_included_in_db_message(): void {
        global $wpdb;

        WN_Logger::error( 'Context test', [ 'key' => 'value', 'order' => 99 ] );

        $last_insert = end( $wpdb->inserts );
        $this->assertStringContainsString( 'key', $last_insert['data']['error_message'] );
        $this->assertStringContainsString( '99',  $last_insert['data']['error_message'] );
    }

    // -----------------------------------------------------------------------
    // 9. error_code for ERROR level is 500
    // -----------------------------------------------------------------------

    public function test_error_code_for_error_level_is_500(): void {
        global $wpdb;

        WN_Logger::error( 'Error code check.' );

        $last_insert = end( $wpdb->inserts );
        $this->assertSame( 500, $last_insert['data']['error_code'] );
    }
}
