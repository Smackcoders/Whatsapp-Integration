<?php

namespace Smackcoders\WN;

if (!defined('ABSPATH')) {
    exit;
}

class WN_Scheduler {

    public function __construct() {
        add_action('wn_subscription_check_cron', [$this, 'check_subscriptions']);
        
        if (!wp_next_scheduled('wn_subscription_check_cron')) {
            wp_schedule_event(time(), 'daily', 'wn_subscription_check_cron');
        }
    }

    /**
     * Scan the subscriptions table and send reminders/expiry alerts
     */
    public function check_subscriptions() {
        global $wpdb;
        $table = $wpdb->prefix . 'subscriptions';
        
        // 1. Reminders (2 days before expiry)
        // $reminder_date = date('Y-m-d', strtotime('+2 days'));
        $timestamp = strtotime('+2 days', current_time('timestamp'));
        $reminder_date = wp_date('Y-m-d', $timestamp);
        $reminders = $wpdb->get_results($wpdb->prepare( // phpcs:ignore 
            "SELECT * FROM $table WHERE status = 'active' AND DATE(end_date) = %s AND (last_notified IS NULL OR DATE(last_notified) != CURDATE())", // phpcs:ignore
            $reminder_date
        ));

        foreach ($reminders as $sub) {
            $user = get_userdata($sub->user_id);
            if ($user && !empty($user->billing_phone)) {
                $this->trigger_notification($user->billing_phone, 'subscription_reminder', $sub);
                $wpdb->update($table, ['last_notified' => current_time('mysql')], ['id' => $sub->id]); // phpcs:ignore 
            }
        }

        // 2. Expiry (Today or passed)
        $expiry = $wpdb->get_results( // phpcs:ignore 
            "SELECT * FROM $table WHERE status = 'active' AND DATE(end_date) <= CURDATE()" // phpcs:ignore
        );

        foreach ($expiry as $sub) {
            $user = get_userdata($sub->user_id);
            if ($user && !empty($user->billing_phone)) {
                $this->trigger_notification($user->billing_phone, 'subscription_expiry', $sub);
                $wpdb->update($table, ['status' => 'expired', 'last_notified' => current_time('mysql')], ['id' => $sub->id]); // phpcs:ignore
            }
        }
    }

    private function trigger_notification($to, $type, $sub) {
        $event_manager = new WN_EventManager();
        $country = '';
        $user = get_userdata($sub->user_id);
        if ($user) {
            $country = get_user_meta($sub->user_id, 'billing_country', true);
        }
        $event_manager->dispatch_message($to, $type, [
            '[User ID]'   => $sub->user_id,
            '[Expiry Date]' => $sub->end_date
        ], (string)$country);
    }
}
