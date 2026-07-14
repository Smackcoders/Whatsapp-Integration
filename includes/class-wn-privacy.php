<?php

namespace Smackcoders\WN;

if (!defined('ABSPATH')) {
    exit;
}

class WN_Privacy {

    public function __construct() {
        add_filter('wp_privacy_personal_data_exporters', [$this, 'register_exporters']);
        add_filter('wp_privacy_personal_data_erasers', [$this, 'register_erasers']);
    }

    public function register_exporters($exporters) {
        $exporters['wp-notifier'] = array(
            'exporter_friendly_name' => __('WhatsApp Integration', 'wp-notifier'),
            'callback'               => [$this, 'exporter'],
        );
        return $exporters;
    }

    public function register_erasers($erasers) {
        $erasers['wp-notifier'] = array(
            'eraser_friendly_name' => __('WhatsApp Integration', 'wp-notifier'),
            'callback'             => [$this, 'eraser'],
        );
        return $erasers;
    }

    /**
     * Exporter for WhatsApp data
     */
    public function exporter($email_address, $page = 1) {
        global $wpdb;

        $user = get_user_by('email', $email_address);
        if (!$user) {
            return array('data' => array(), 'done' => true);
        }

        $user_id = $user->ID;
        $phone = get_user_meta($user_id, 'billing_phone', true);
        
        $data_to_export = array();

        // 1. WhatsApp Notifications (Logs)
        if (!empty($phone)) {
            $logs = $wpdb->get_results($wpdb->prepare( // phpcs:ignore
                "SELECT * FROM {$wpdb->prefix}whatsapp_notifications WHERE recipient_phone LIKE %s",
                '%' . $wpdb->esc_like(ltrim($phone, '+')) . '%'
            ));

            foreach ($logs as $log) {
                $data_to_export[] = array(
                    'group_id'    => 'whatsapp-notifications',
                    'group_label' => __('WhatsApp Notifications', 'wp-notifier'),
                    'item_id'     => "log-{$log->id}",
                    'data'        => array(
                        array('name' => __('Date', 'wp-notifier'), 'value' => $log->created_at),
                        array('name' => __('Event Type', 'wp-notifier'), 'value' => $log->event_type),
                        array('name' => __('Phone', 'wp-notifier'), 'value' => $log->recipient_phone),
                        array('name' => __('Message', 'wp-notifier'), 'value' => $log->message),
                        array('name' => __('Status', 'wp-notifier'), 'value' => $log->sent_status),
                    ),
                );
            }
        }

        // 2. Scheduled Notifications
        $scheduled = $wpdb->get_results($wpdb->prepare( // phpcs:ignore
            "SELECT * FROM {$wpdb->prefix}whatsapp_scheduled_notifications WHERE user_id = %d",
            $user_id
        ));

        foreach ($scheduled as $item) {
            $data_to_export[] = array(
                'group_id'    => 'whatsapp-scheduled',
                'group_label' => __('Scheduled WhatsApp Notifications', 'wp-notifier'),
                'item_id'     => "scheduled-{$item->id}",
                'data'        => array(
                    array('name' => __('Scheduled Time', 'wp-notifier'), 'value' => $item->scheduled_time),
                    array('name' => __('Type', 'wp-notifier'), 'value' => $item->notification_type),
                    array('name' => __('Status', 'wp-notifier'), 'value' => $item->status),
                ),
            );
        }

        return array(
            'data' => $data_to_export,
            'done' => true,
        );
    }

    /**
     * Eraser for WhatsApp data
     */
    public function eraser($email_address, $page = 1) {
        global $wpdb;

        $user = get_user_by('email', $email_address);
        if (!$user) {
            return array('items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true);
        }

        $user_id = $user->ID;
        $phone = get_user_meta($user_id, 'billing_phone', true);
        
        $items_removed = false;

        // 1. Delete Scheduled Notifications
        $deleted_scheduled = $wpdb->delete( // phpcs:ignore
            "{$wpdb->prefix}whatsapp_scheduled_notifications",
            array('user_id' => $user_id),
            array('%d')
        );
        if ($deleted_scheduled) $items_removed = true;

        // 2. Anonymize Notifications Logs
        if (!empty($phone)) {
            $anonymized = $wpdb->query($wpdb->prepare( // phpcs:ignore
                "UPDATE {$wpdb->prefix}whatsapp_notifications 
                 SET recipient_phone = 'ANONYMIZED', message = 'REDACTED FOR PRIVACY' 
                 WHERE recipient_phone LIKE %s",
                '%' . $wpdb->esc_like(ltrim($phone, '+')) . '%'
            ));
            if ($anonymized) $items_removed = true;
        }

        // 3. Delete Subscriptions records
        $deleted_sub = $wpdb->delete( // phpcs:ignore
            "{$wpdb->prefix}subscriptions",
            array('user_id' => $user_id),
            array('%d')
        );
        if ($deleted_sub) $items_removed = true;

        return array(
            'items_removed'  => $items_removed,
            'items_retained' => false,
            'messages'       => array(__('WhatsApp personal data removed or anonymized.', 'wp-notifier')),
            'done'           => true,
        );
    }
}
