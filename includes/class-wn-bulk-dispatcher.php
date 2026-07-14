<?php

namespace Smackcoders\WN;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WN_BulkDispatcher - Handles optimized bulk API calls with retry logic.
 * Implements Issue #20: Optimize API Calls for Bulk Notifications
 */
class WN_BulkDispatcher {

    /**
     * Maximum retry attempts for failed messages
     */
    const MAX_RETRIES = 3;

    /**
     * Delay between retries in seconds
     */
    const RETRY_DELAY = 5;

    /**
     * Queue table name
     */
    private string $queue_table;

    private \wpdb $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->queue_table = $wpdb->prefix . 'whatsapp_bulk_queue';
    }

    /**
     * Queue a bulk notification for later processing
     *
     * @param string $recipient_phone Recipient phone number
     * @param string $notification_type Notification event type
     * @param array  $replacements     Template placeholder replacements
     * @param int    $priority         Queue priority (lower = higher priority)
     * @return bool
     */
    public function queue_notification( string $recipient_phone, string $notification_type, array $replacements = [], int $priority = 10 ): bool {
        $result = $this->wpdb->insert( // phpcs:ignore
            $this->queue_table,
            [
                'recipient_phone'   => sanitize_text_field( $recipient_phone ),
                'notification_type' => sanitize_text_field( $notification_type ),
                'replacements'      => wp_json_encode( $replacements ),
                'status'            => 'pending',
                'retries'           => 0,
                'priority'          => (int) $priority,
                'created_at'        => current_time( 'mysql' ),
                'updated_at'        => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s' ]
        );

        return (bool) $result;
    }

    /**
     * Process the bulk notification queue in batches
     * Hooked to WP Cron: wn_process_bulk_queue
     *
     * @param int $batch_size Number of items per batch
     * @return void
     */
    public function process_queue( int $batch_size = 50 ): void {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name set from plugin prefix, not user input
        $sql   = $this->wpdb->prepare( "SELECT * FROM {$this->queue_table} WHERE status = 'pending' AND retries < %d ORDER BY priority ASC, created_at ASC LIMIT %d", self::MAX_RETRIES, $batch_size ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $items = $this->wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $sql is the return value of $wpdb->prepare()

        if ( empty( $items ) ) {
            return;
        }

        $event_manager = new WN_EventManager();

        foreach ( $items as $item ) {
            // Mark as processing
            $this->wpdb->update( // phpcs:ignore
                $this->queue_table,
                [ 'status' => 'processing', 'updated_at' => current_time( 'mysql' ) ],
                [ 'id' => $item->id ],
                [ '%s', '%s' ],
                [ '%d' ]
            );

            $replacements = json_decode( $item->replacements, true ) ?: [];

            // Attempt send
            $result = $event_manager->dispatch_message_public(
                $item->recipient_phone,
                $item->notification_type,
                $replacements
            );

            if ( ! empty( $result['success'] ) ) {
                $this->wpdb->update( // phpcs:ignore
                    $this->queue_table,
                    [ 'status' => 'sent', 'updated_at' => current_time( 'mysql' ) ],
                    [ 'id' => $item->id ],
                    [ '%s', '%s' ],
                    [ '%d' ]
                );
            } else {
                $new_retries = (int) $item->retries + 1;
                $new_status  = ( $new_retries >= self::MAX_RETRIES ) ? 'failed' : 'pending';

                $this->wpdb->update( // phpcs:ignore
                    $this->queue_table,
                    [
                        'status'        => $new_status,
                        'retries'       => $new_retries,
                        'error_message' => $result['error'] ?? 'Unknown error',
                        'updated_at'    => current_time( 'mysql' ),
                    ],
                    [ 'id' => $item->id ],
                    [ '%s', '%d', '%s', '%s' ],
                    [ '%d' ]
                );
            }
        }
    }

    /**
     * Schedule the bulk queue cron if not already scheduled
     */
    public function schedule_cron(): void {
        if ( ! wp_next_scheduled( 'wn_process_bulk_queue' ) ) {
            wp_schedule_event( time(), 'every_five_minutes', 'wn_process_bulk_queue' );
        }
    }

    /**
     * Register the 5-minute cron interval
     *
     * @param array $schedules Existing cron schedules
     * @return array
     */
    public static function add_cron_interval( array $schedules ): array {
        if ( ! isset( $schedules['every_five_minutes'] ) ) {
            $schedules['every_five_minutes'] = [
                'interval' => 300,
                'display'  => __( 'Every 5 Minutes', 'wp-notifier' ),
            ];
        }
        return $schedules;
    }

    /**
     * Get queue statistics
     *
     * @return array
     */
    public function get_queue_stats(): array {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name set from plugin prefix, not user input
        $stats = $this->wpdb->get_results( "SELECT status, COUNT(*) as count FROM {$this->queue_table} GROUP BY status" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- table name from trusted plugin prefix

        $result = [ 'pending' => 0, 'processing' => 0, 'sent' => 0, 'failed' => 0 ];
        foreach ( $stats as $row ) {
            $result[ $row->status ] = (int) $row->count;
        }
        return $result;
    }

    /**
     * Clear sent/failed items older than specified days
     *
     * @param int $days
     * @return int Number of rows deleted
     */
    public function purge_old_items( int $days = 30 ): int {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name set from plugin prefix, not user input
        $sql = $this->wpdb->prepare( "DELETE FROM {$this->queue_table} WHERE status IN ('sent', 'failed') AND created_at < DATE_SUB(NOW(), INTERVAL %d DAY)", $days ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $this->wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $sql is the return value of $wpdb->prepare()
        return (int) $this->wpdb->rows_affected;
    }
}
