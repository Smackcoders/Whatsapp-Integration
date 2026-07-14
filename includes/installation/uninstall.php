<?php

namespace Smackcoders\WN;

if (!defined('ABSPATH')) {
    exit;
}

class WhatsAppPluginUninstaller {

    public static function uninstall() {

        global $wpdb;

        $tables = [
            "{$wpdb->prefix}whatsapp_notifications",
            "{$wpdb->prefix}whatsapp_error_logs",
            "{$wpdb->prefix}whatsapp_scheduled_notifications",
            "{$wpdb->prefix}whatsapp_config",
            "{$wpdb->prefix}subscriptions",
            "{$wpdb->prefix}whatsapp_bulk_queue",
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table"); // phpcs:ignore
        }
        
    }

}
