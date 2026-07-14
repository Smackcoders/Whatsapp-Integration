<?php

namespace Smackcoders\WN;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WN_Logger - Production issue logging mechanism using WP_Filesystem / wp_debug_log.
 * Implements Issue #28: Create Production Issue Logging Mechanism
 * Extends Issue #16: Develop Error Logging
 */
class WN_Logger {

    /**
     * Log levels
     */
    const LEVEL_DEBUG   = 'DEBUG';
    const LEVEL_INFO    = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR   = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';

    /**
     * Minimum log level to actually write (configurable via DB setting)
     */
    private static string $min_level = self::LEVEL_INFO;

    /**
     * Level priority map for filtering
     */
    private static array $level_priority = [
        self::LEVEL_DEBUG    => 0,
        self::LEVEL_INFO     => 1,
        self::LEVEL_WARNING  => 2,
        self::LEVEL_ERROR    => 3,
        self::LEVEL_CRITICAL => 4,
    ];

    /**
     * Write a log entry to the WP debug log and optionally to DB.
     *
     * @param string $message   Human-readable log message
     * @param string $level     Log level constant (DEBUG, INFO, WARNING, ERROR, CRITICAL)
     * @param array  $context   Additional contextual data
     * @param bool   $to_db     Whether to also persist to DB error_logs table
     */
    public static function log( string $message, string $level = self::LEVEL_INFO, array $context = [], bool $to_db = false ): void {
        // Check if logging is enabled and level meets minimum
        if ( ! self::should_log( $level ) ) {
            return;
        }

        $prefix    = '[WP Notifier]';
        $timestamp = current_time( 'Y-m-d H:i:s' );
        $context_str = ! empty( $context ) ? ' | Context: ' . wp_json_encode( $context ) : '';
        $log_line  = sprintf( '%s [%s] %s %s%s', $timestamp, $level, $prefix, $message, $context_str );

        // Log to PHP/WordPress error_log
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log( $log_line );

        // Persist to DB if requested or level is ERROR/CRITICAL
        if ( $to_db || in_array( $level, [ self::LEVEL_ERROR, self::LEVEL_CRITICAL ], true ) ) {
            self::write_to_db( $message, $level, $context );
        }
    }

    /**
     * Convenience methods
     */
    public static function debug( string $message, array $context = [] ): void {
        self::log( $message, self::LEVEL_DEBUG, $context );
    }

    public static function info( string $message, array $context = [] ): void {
        self::log( $message, self::LEVEL_INFO, $context );
    }

    public static function warning( string $message, array $context = [] ): void {
        self::log( $message, self::LEVEL_WARNING, $context, true );
    }

    public static function error( string $message, array $context = [] ): void {
        self::log( $message, self::LEVEL_ERROR, $context, true );
    }

    public static function critical( string $message, array $context = [] ): void {
        self::log( $message, self::LEVEL_CRITICAL, $context, true );
    }

    /**
     * Write log entry to whatsapp_error_logs table
     *
     * @param string $message Log message
     * @param string $level   Log level
     * @param array  $context Context data
     */
    private static function write_to_db( string $message, string $level, array $context = [] ): void {
        global $wpdb;

        $context_json = ! empty( $context ) ? wp_json_encode( $context ) : '';
        $full_message = $message . ( $context_json ? ' | ' . $context_json : '' );

        $wpdb->insert( // phpcs:ignore
            $wpdb->prefix . 'whatsapp_error_logs',
            [
                'notification_id' => 0,
                'error_message'   => substr( $full_message, 0, 65535 ),
                'error_code'      => self::level_to_code( $level ),
                'created_at'      => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%d', '%s' ]
        );
    }

    /**
     * Map log level to HTTP-style status code for storage
     *
     * @param string $level
     * @return int
     */
    private static function level_to_code( string $level ): int {
        $map = [
            self::LEVEL_DEBUG    => 100,
            self::LEVEL_INFO     => 200,
            self::LEVEL_WARNING  => 300,
            self::LEVEL_ERROR    => 500,
            self::LEVEL_CRITICAL => 503,
        ];
        return $map[ $level ] ?? 500;
    }

    /**
     * Determine if the given level should be logged given the current minimum level
     *
     * @param string $level
     * @return bool
     */
    private static function should_log( string $level ): bool {
        $current_priority = self::$level_priority[ $level ] ?? 0;
        $min_priority     = self::$level_priority[ self::$min_level ] ?? 1;
        return $current_priority >= $min_priority;
    }

    /**
     * Set minimum log level (can be changed via plugin settings)
     *
     * @param string $level
     */
    public static function set_min_level( string $level ): void {
        if ( isset( self::$level_priority[ $level ] ) ) {
            self::$min_level = $level;
        }
    }

    /**
     * Get recent errors from DB for admin display
     *
     * @param int $limit Number of recent errors to fetch
     * @return array
     */
    public static function get_recent_errors( int $limit = 50 ): array {
        global $wpdb;
        return $wpdb->get_results( // phpcs:ignore
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}whatsapp_error_logs ORDER BY created_at DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }
}
