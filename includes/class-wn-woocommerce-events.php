<?php

namespace Smackcoders\WN;

if (!defined('ABSPATH')) {
    exit;
}

require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/providers/provider-interface.php';
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/providers/gupshub.php';
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/providers/infobip.php';
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/providers/twilio.php';
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/providers/sendgrid.php';
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/providers/mailtrap-email.php';
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/providers/brevo-email.php';

use Smackcoders\WN\Providers\TwilioProvider;
use Smackcoders\WN\Providers\InfobipProvider;
use Smackcoders\WN\Providers\GupshupProvider;
use Smackcoders\WN\Providers\SendGridProvider;
use Smackcoders\WN\Providers\MailtrapEmailProvider;
use Smackcoders\WN\Providers\BrevoEmailProvider;

class WN_EventManager {

    public \wpdb $wpdb;
    public string $table_name;
    private static array $processed_status_changes = [];
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'whatsapp_scheduled_notifications';

        // Customer Status Hooks
        add_action('woocommerce_thankyou', [$this, 'send_order_confirmation'], 20, 1); // Thank you page trigger
        add_action('woocommerce_order_status_changed', [$this, 'send_status_change_notification'], 20, 3);
        
        // Manual Status Hooks (Fallback)
        add_action('woocommerce_order_status_failed', [$this, 'failed_order_notification'], 20, 1);
        add_action('woocommerce_order_status_refunded', [$this, 'refunded_order_notification'], 20, 1);
        add_action('woocommerce_order_status_cancelled', [$this, 'cancelled_order_notification'], 20, 1);
        add_action('woocommerce_order_status_on-hold', [$this, 'on_hold_order_notification'], 20, 1);
        // add_action('woocommerce_order_status_completed', [$this, 'completed_order_notification'], 20, 1);
        add_action('woocommerce_order_status_pending', [$this, 'pending_payment_notification'], 20, 1);
        add_action('woocommerce_order_status_processing', [$this, 'processing_order_notification'], 20, 1);
        
        // Admin Notifications
        add_action('woocommerce_checkout_order_processed', [$this, 'admin_new_order_alert'], 20, 1);
        add_action('woocommerce_new_order', [$this, 'admin_new_order_alert'], 20, 1);
        add_action('user_register', [$this, 'admin_new_customer_alert'], 20, 1);
        add_action('profile_update', [$this, 'admin_customer_update_alert'], 20, 2);

        // Subscriptions & Cron
        add_action('woocommerce_order_status_completed', [$this, 'handle_subscription_order'], 30);
        add_action('init', [$this, 'schedule_notifications_cron']);
        add_action('whatsapp_process_scheduled_notifications', [$this, 'send_scheduled_notifications']);

        // Invoices
        add_action('woocommerce_invoice_generated', [$this, 'send_invoice_notification'], 20, 1);
        add_action('woocommerce_before_resend_order_emails', [$this, 'handle_resend_invoice_email'], 20, 2);
    }

    /**
     * Centralized message dispatcher
     */
    public function dispatch_message($to, $type, $replacements = [], $country = '') {
        // error_log("WN Debug: dispatch_message called for type [$type] to [$to]");

        // 1. Check if enabled
        if (!$this->is_notification_enabled($type)) {
            //  error_log("WN Debug: Notification type [$type] is disabled.");
             return;
        }

        // 2. Format Message
        $message = $this->get_template_message($type, $replacements);
        
        // Safety Fallback for empty messages (if database template is missing)
        if (empty($message)) {
            if ($type === 'admin_new_order' || $type === 'order_confirmation') {
                $message = "✨ Order Notification: Order #[Order ID] has been processed. Current Status: [Status].";
            } elseif ($type === 'admin_new_customer') {
                $message = "👤 Admin Alert: A new customer ([Name]) has registered with email [Email].";
            } else {
                $message = "🔔 Update Alert: Notification for $type.";
            }
            // Final placeholder sweep
            foreach ($replacements as $k => $v) $message = str_replace($k, (string)($v ?? ''), $message);
        }

        if (empty($to)) {
            // error_log("WN Debug: Recipient number is missing for $type");
            $this->log_activity([
                'event_type' => $type,
                'recipient_phone' => 'None',
                'message' => $message,
                'sent_status' => 'failed',
                'provider' => 'none',
                'response_message' => 'Recipient phone number is missing'
            ]);
            return;
        }

        // Standardize phone number (Strip all non-numeric characters and ensure country prefix)
        $to = preg_replace('/[^0-9]/', '', (string)$to);
        $to = ltrim($to, '0');
        if (strlen($to) === 10) {
            $calling_code = $this->get_calling_code($country);
            $to = $calling_code . $to;
        }

        $provider_name = $this->get_setting('provider');

        // error_log("WN Debug: Selected Provider [$provider_name]");

        $result = ['success' => false, 'error' => 'No provider configured'];
        
        if ($provider_name === 'twilio') {
            $result = $this->send_via_twilio($to, $message);
        } elseif ($provider_name === 'infobip') {
            $result = $this->send_via_infobip($to, $message);
        } elseif ($provider_name === 'gupshup') {
            $result = $this->send_via_gupshup($to, $message);
        }
        
        // error_log("WN Debug: Sending Result: " . print_r($result, true));

        // 3. Log Activity
        $this->log_activity([
            'event_type'       => $type,
            'recipient_phone'  => $to,
            'message'          => $message,
            'sent_status'      => ($result['success'] ?? false) ? 'sent' : 'failed',
            'provider'         => $provider_name ?? 'none',
            'response_message' => ($result['success'] ?? false) ? 'Message sent' : ($result['error'] ?? 'Provider Error')
        ]);

        return $result;
    }

    private function dispatch_email($to, $type, $replacements = []) {
        // error_log("WN Debug: dispatch_email called for type [$type] to recipient [$to]");
        if (!$this->is_email_enabled($type)) {
            // error_log("WN Debug: Email for [$type] is DISABLED.");
            return;
        }
        $message = $this->get_template_message($type, $replacements);
        $subject = str_replace('_', ' ', ucfirst($type)) . ' Notification';

        $provider_name = $this->get_setting('email_provider');
        // error_log("WN Debug: Found Email Provider: [" . ($provider_name ? $provider_name : 'none') . "]");

        $result = ['success' => false, 'error' => 'No email provider configured'];

        if ($provider_name === 'sendgrid') {
            $result = $this->send_email_via_sendgrid($to, $subject, $message);
        } elseif ($provider_name === 'mailtrap') {
            $result = $this->send_email_via_mailtrap($to, $subject, $message);
        } elseif ($provider_name === 'brevo') {
            $result = $this->send_email_via_brevo($to, $subject, $message);
        }

        // error_log("WN Debug: Email dispatch result: " . print_r($result, true));

        // Log the activity
        $this->log_activity([
            'event_type'       => $type . ' (Email)',
            'recipient_phone'  => $to,
            'message'          => $message,
            'sent_status'      => ($result['success'] ?? false) ? 'sent' : 'failed',
            'provider'         => $provider_name ?? 'none',
            'response_message' => ($result['success'] ?? false) ? 'Email sent' : ($result['error'] ?? 'Provider Error')
        ]);

        return $result;
    }

    public function dispatch_sms($to, $type, $replacements = [], $country = '') {
        // error_log("WN Debug: dispatch_sms called for type [$type] to recipient [$to]");
        if (!$this->is_sms_enabled($type)) {
            // error_log("WN Debug: SMS for [$type] is DISABLED.");
            return;
        }

        $message = $this->get_template_message($type, $replacements);
        $provider_name = $this->get_setting('sms_provider');
        // error_log("WN Debug: SMS Provider Name: [$provider_name]");

        // Twilio specific implementation for SMS
        if ($provider_name === 'twilio') {
            $sid = $this->get_setting('sms_twilio_sid');
            $token = $this->get_setting('sms_twilio_token');
            $from = $this->get_setting('sms_twilio_from');

            if (empty($sid) || empty($token)) {
                // error_log("WN Debug: SMS Twilio Credentials Missing");
                return ['success' => false, 'error' => 'Twilio SMS Credentials Missing'];
            }
            
            // Standardize phone number for SMS (Remove non-digits)
            $to = preg_replace('/[^0-9]/', '', (string)$to);
            $to = ltrim($to, '0');
            // Prefix calling code if its missed
            if (strlen($to) === 10) {
               $calling_code = $this->get_calling_code($country);
               $to = $calling_code . $to;
            }

            $provider = new TwilioProvider($sid, $token, $from);
            $result = $provider->sendMessage($to, $message, false); // false for $is_whatsapp

            // Log the activity
            $this->log_activity([
                'event_type'       => $type . ' (SMS)',
                'recipient_phone'  => $to,
                'message'          => $message,
                'sent_status'      => ($result['success'] ?? false) ? 'sent' : 'failed',
                'provider'         => 'twilio (SMS)',
                'response_message' => ($result['success'] ?? false) ? 'SMS sent via Twilio' : ($result['error'] ?? 'Provider Error')
            ]);

            return $result;
        }

        return ['success' => false, 'error' => 'SMS is only supported via Twilio for now'];
    }

    public function is_email_enabled($type) {
        $enabled = $this->get_setting($type . '_email_enabled');
        // error_log("WN Debug: is_email_enabled for [$type] returned: [$enabled]");
        return ($enabled === '1');
    }

    public function is_sms_enabled($type) {
        $enabled = $this->get_setting($type . '_sms_enabled');
        return ($enabled === '1');
    }

    public function is_notification_enabled($type) {
        $enabled = $this->get_setting($type . '_enabled');
        // Default to enabled (1) unless explicitly disabled (0)
        return ($enabled !== '0');
    }

    public function get_setting($key) {
        $value = $this->wpdb->get_var($this->wpdb->prepare( // phpcs:ignore
            "SELECT setting_value FROM {$this->wpdb->prefix}whatsapp_config WHERE setting_key = %s", // phpcs:ignore
            $key // phpcs:ignore
        ));
        return WN_Security::decrypt_if_sensitive($key, $value);
    }

    private function get_template_message($type, $replacements) {
        // Mapping UI keys to installation (default) keys
        $key_map = [
            'order_confirmation'    => 'completed_order',
            'order_status_change'   => 'status',
            'failed_order'          => 'failed',
            'refunded_order'        => 'refunded',
            'cancelled_order'       => 'cancelled',
            'on_hold_order'         => 'on_hold',
            'abandoned_cart'        => 'abandoned_cart',
            'pending_payment'       => 'pending_payment',
            'subscription_reminder' => 'subscription_reminder',
            'subscription_expiry'   => 'subscription_expiry',
            'new_user_registration' => 'new_user',
            'profile_update'        => 'new_user', // Fallback
            'admin_new_order'      => 'completed_order', // Fallback for admin if not defined
            'admin_new_customer'   => 'new_user',    // Fallback for admin if not defined
            'invoice'              => 'invoice',     // Invoice notification template
        ];
        
        // 1. Try custom key first (e.g. order_confirmation_message)
        $message = $this->wpdb->get_var($this->wpdb->prepare( // phpcs:ignore
            "SELECT setting_value FROM {$this->wpdb->prefix}whatsapp_config WHERE setting_key = %s", // phpcs:ignore
            $type . '_message' // phpcs:ignore  
        ));

        // 2. If empty, try the mapped installation key (e.g. completed_order_message)
        if (empty($message) && isset($key_map[$type])) {
            $message = $this->wpdb->get_var($this->wpdb->prepare( // phpcs:ignore
                "SELECT setting_value FROM {$this->wpdb->prefix}whatsapp_config WHERE setting_key = %s", // phpcs:ignore
                $key_map[$type] . '_message' // phpcs:ignore  
            ));
        }

        if (empty($message)) return '';

        foreach ($replacements as $k => $v) {
            if (is_object($v) || is_array($v)) continue;
            // Handle potentially missing values safely
            $v = (string)($v ?? '');
            $message = str_replace($k, $v, (string)$message);
        }
        return $message;
    }

    // Individual Handlers
    public function send_order_confirmation($order) {
        if (is_object($order)) {
            $order_id = $order->get_id();
        } else {
            $order_id = $order;
            $order = wc_get_order($order_id);
        }
        if ($order) {
            // --- PREVENT DUPLICATES (Database Level) ---
            if (get_post_meta($order_id, '_wn_customer_notified', true)) {
                // error_log("WN Debug: Customer already notified for order $order_id. Skipping.");
                $this->admin_new_order_alert($order);
                return;
            }

            $replacements = [
                '[Order ID]'      => $order_id,
                '[Customer Name]' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                '[Total]'         => $order->get_total() . ' ' . $order->get_currency(),
                '[Status]'        => $order->get_status(),
            ];
            $res = $this->dispatch_message($order->get_billing_phone(), 'order_confirmation', $replacements, $order->get_billing_country());
            
            if ($res && !empty($res['success'])) {
                update_post_meta($order_id, '_wn_customer_notified', '1');
            }

            // --- EMAIL DISPATCH ---
            $this->dispatch_email($order->get_billing_email(), 'order_confirmation', $replacements);

            // --- SMS DISPATCH ---
            $this->dispatch_sms($order->get_billing_phone(), 'order_confirmation', $replacements, $order->get_billing_country());

            $this->admin_new_order_alert($order); // Ensure admin is also notified
        }
    }

    public function send_status_change_notification($order_id, $old, $new) {
        if (is_object($order_id)) {
            $order = $order_id;
            $order_id = $order->get_id();
        } else {
            $order = wc_get_order($order_id);
        }
        
        if ($order) {
            $cache_key = $order_id . '_' . $new;
            if (isset(self::$processed_status_changes[$cache_key])) {
                return;
            }
            self::$processed_status_changes[$cache_key] = true;
            $old_label = empty($old) ? 'New Order' : ucfirst((string)$old);
            $replacements = [
                '[Order ID]'      => $order_id,
                '[Customer Name]' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                '[old_status]'    => $old_label,
                '[new_status]'    => ucfirst((string)$new),
                '[Status]'        => ucfirst((string)$new)
            ];
            $this->dispatch_message($order->get_billing_phone(), 'order_status_change', $replacements, $order->get_billing_country());
            
            // --- EMAIL ---
            $this->dispatch_email($order->get_billing_email(), 'order_status_change', $replacements);

            // --- SMS ---
            $this->dispatch_sms($order->get_billing_phone(), 'order_status_change', $replacements, $order->get_billing_country());
        }
    }

    public function completed_order_notification($order_id) { $this->send_order_confirmation($order_id); }
    public function processing_order_notification($order_id) { $this->send_order_confirmation($order_id); }
    public function failed_order_notification($order_id) { $this->send_status_change_notification($order_id, '', 'failed'); }
    public function refunded_order_notification($order_id) { $this->send_status_change_notification($order_id, '', 'refunded'); }
    public function cancelled_order_notification($order_id) { $this->send_status_change_notification($order_id, '', 'cancelled'); }
    public function on_hold_order_notification($order_id) { $this->send_status_change_notification($order_id, '', 'on-hold'); }
    public function pending_payment_notification($order_id) { $this->send_status_change_notification($order_id, '', 'pending'); }

    public function admin_new_order_alert($order_id) {
        // error_log("WN Debug: Entering admin_new_order_alert for Order #$order_id");
        if (is_object($order_id)) {
            $order = $order_id;
            $order_id = $order->get_id();
        } else {
            $order = wc_get_order($order_id);
        }

        if (!$order) {
            // error_log("WN Debug: Order #$order_id not found in admin_new_order_alert.");
            return;
        }

        // --- PREVENT DUPLICATES (Database Level) ---
        if (get_post_meta($order_id, '_wn_admin_notified', true)) {
            // error_log("WN Debug: Admin already notified for order $order_id. Skipping.");
            return;
        }

        $admin_phone = $this->get_setting('admin_phone_number');
        // error_log("WN Debug: Admin phone from setting: $admin_phone");

        // Build replacements array unconditionally so it is always in scope
        $items_list = [];
        foreach ($order->get_items() as $item) {
            $items_list[] = $item->get_name() . ' (x' . $item->get_quantity() . ')';
        }

        $replacements = [
            '[Order ID]'      => $order_id,
            '[Customer Name]' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            '[Email]'         => $order->get_billing_email(),
            '[Total]'         => $order->get_total() . ' ' . $order->get_currency(),
            '[Items]'         => implode(', ', $items_list),
            '[Address]'       => $order->get_billing_address_1() . ', ' . $order->get_billing_city(),
            '[Admin URL]'     => admin_url('post.php?post=' . $order_id . '&action=edit'),
            '[Status]'        => $order->get_status(),
        ];

        if (!empty($admin_phone)) {
            $res = $this->dispatch_message($admin_phone, 'admin_new_order', $replacements);
            if ($res && !empty($res['success'])) {
                update_post_meta($order_id, '_wn_admin_notified', '1');
            }
        }

        // --- ADMIN EMAIL ---
        $admin_email = get_option('admin_email');
        $this->dispatch_email($admin_email, 'admin_new_order', $replacements);

        // --- ADMIN SMS ---
        if (!empty($admin_phone)) {
            $this->dispatch_sms($admin_phone, 'admin_new_order', $replacements);
        }
    }

    public function admin_new_customer_alert($user_id) {
        $user = get_userdata($user_id);
        if ($user) {
            $admin_phone = $this->get_setting('admin_phone_number');
            $replacements = [
                '[username]' => $user->user_login,
                '[Email]'    => $user->user_email,
                '[Name]'     => $user->first_name . ' ' . $user->last_name,
            ];
            $this->dispatch_message($admin_phone, 'admin_new_customer', $replacements);
            $this->dispatch_sms($admin_phone, 'admin_new_customer', $replacements);
        }
    }

    public function admin_customer_update_alert($user_id) {
        $user = get_userdata($user_id);
        if ($user) {
            $admin_phone = $this->get_setting('admin_phone_number');
            $this->dispatch_message($admin_phone, 'admin_new_customer', ['[username]' => $user->user_login]); 
        }
    }

    public function send_invoice_notification($order_id) {
        // Check if invoice notifications are enabled
        if (!$this->is_notification_enabled('invoice')) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Deduplication guard — prevent sending the same invoice notification twice per order
        $meta_key = '_wn_invoice_notified';
        if (get_post_meta($order_id, $meta_key, true)) {
            // error_log("WN Debug: Invoice notification already sent for order $order_id. Skipping.");
            return;
        }

        $replacements = [
            '[Order ID]'      => $order_id,
            '[Customer Name]' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            '[Total]'         => $order->get_total() . ' ' . $order->get_currency(),
            '[Status]'        => $order->get_status(),
        ];

        $res = $this->dispatch_message($order->get_billing_phone(), 'invoice', $replacements, $order->get_billing_country());

        if ($res && !empty($res['success'])) {
            update_post_meta($order_id, $meta_key, '1');
        }

        // Email & SMS dispatch
        $this->dispatch_email($order->get_billing_email(), 'invoice', $replacements);
        $this->dispatch_sms($order->get_billing_phone(), 'invoice', $replacements, $order->get_billing_country());
    }

    /**
     * Handle native WooCommerce resend-email hook; fires only for invoice-type emails.
     *
     * @param \WC_Order $order      The order object.
     * @param string    $email_type The email type being resent (e.g. 'customer_invoice').
     */
    public function handle_resend_invoice_email($order, $email_type) {
        if (!is_a($order, 'WC_Abstract_Order')) {
            return;
        }
        // Only trigger for invoice-related email types
        $invoice_types = ['customer_invoice', 'woocommerce_customer_invoice'];
        if (!in_array($email_type, $invoice_types, true)) {
            return;
        }
        $this->send_invoice_notification($order->get_id());
    }

    // Provider Communication
    private function send_via_twilio($to, $message) {
        $sid = $this->get_setting('twilio_sid');
        $token = $this->get_setting('twilio_token');
        $from = $this->get_setting('twilio_from');
        if (empty($sid) || empty($token)) return ['success' => false, 'error' => 'Twilio Credentials Missing'];
        $provider = new TwilioProvider($sid, $token, $from);
        return $provider->sendMessage($to, $message);
    }

    private function send_via_infobip($to, $message) {
        $key = trim((string)$this->get_setting('infobip_api_key'));
        $url = trim((string)$this->get_setting('infobip_base_url'));
        $sender = trim((string)$this->get_setting('infobip_sender'));
        
        if (empty($key) || empty($url)) return ['success' => false, 'error' => 'Infobip Credentials Missing'];
        
        $provider = new InfobipProvider($url, $key, $sender);
        return $provider->sendMessage($to, $message);
    }

    private function send_via_gupshup($to, $message) {
        $key = $this->get_setting('gupshup_api_key');
        $sender = $this->get_setting('gupshup_sender');
        $bot = $this->get_setting('gupshup_botname');
        if (empty($key) || empty($bot)) return ['success' => false, 'error' => 'Gupshup API Key or Bot Name Missing'];
        $provider = new GupshupProvider($key, (string)$sender, (string)$bot);
        return $provider->sendMessage($to, $message);
    }

    private function send_email_via_sendgrid($to, $subject, $message) {
        $key = $this->get_setting('sendgrid_api_key');
        $from = $this->get_setting('sendgrid_from_email');
        $name = $this->get_setting('email_from_name') ?: get_bloginfo('name');
        if (empty($key) || empty($from)) return ['success' => false, 'error' => 'SendGrid Credentials Missing'];
        $provider = new SendGridProvider($key, $from, $name);
        return $provider->sendEmail($to, $subject, $message);
    }

    private function send_email_via_mailtrap($to, $subject, $message) {
        $key = $this->get_setting('mailtrap_api_key');
        $from = $this->get_setting('mailtrap_from_email');
        $name = $this->get_setting('email_from_name') ?: get_bloginfo('name');
        if (empty($key) || empty($from)) return ['success' => false, 'error' => 'Mailtrap Credentials Missing'];
        $provider = new MailtrapEmailProvider($key, $from, $name);
        return $provider->sendEmail($to, $subject, $message);
    }

    private function send_email_via_brevo($to, $subject, $message) {
        $key = $this->get_setting('brevo_api_key');
        $from = $this->get_setting('brevo_from_email');
        $name = $this->get_setting('email_from_name') ?: get_bloginfo('name');
        if (empty($key) || empty($from)) return ['success' => false, 'error' => 'Brevo Credentials Missing'];
        $provider = new BrevoEmailProvider($key, $from, $name);
        return $provider->sendEmail($to, $subject, $message);
    }

    /**
     * Public wrapper for dispatch_message - used by WN_BulkDispatcher.
     * Issue #20: Optimize API Calls for Bulk Notifications
     *
     * @param string $to           Recipient phone number
     * @param string $type         Notification event type
     * @param array  $replacements Template replacements
     * @return array
     */
    public function dispatch_message_public( string $to, string $type, array $replacements = [], string $country = '' ): array {
        return $this->dispatch_message( $to, $type, $replacements, $country ) ?? [ 'success' => false, 'error' => 'Dispatch returned null' ];
    }

    /**
     * Get calling code based on country
     */
    private function get_calling_code($country = '') {
        if (empty($country)) {
            $country = function_exists('wc_get_base_location') ? wc_get_base_location()['country'] : '';
            if (empty($country) && class_exists('WooCommerce') && isset(WC()->countries)) {
                $country = WC()->countries->get_base_country();
            }
        }
        $calling_code = '';
        if (!empty($country) && class_exists('WooCommerce') && isset(WC()->countries)) {
            $calling_code = WC()->countries->get_country_calling_code($country);
            $calling_code = ltrim((string)$calling_code, '+');
        }
        return empty($calling_code) ? '91' : $calling_code;
    }

    public function log_activity($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'whatsapp_notifications';
        

        $wpdb->insert($table, [ // phpcs:ignore 
            'event_type' => $data['event_type'],
            'recipient_phone' => $data['recipient_phone'] ?? 'None',
            'message' => $data['message'],
            'sent_status' => $data['sent_status'],
            'provider' => $data['provider'],
            'response_message' => $data['response_message'] ?? '',
            'created_at' => current_time('mysql')
        ]);
        if ($data['sent_status'] === 'failed') {
            $wpdb->insert($wpdb->prefix . 'whatsapp_error_logs', [ // phpcs:ignore 
                'notification_id' => $wpdb->insert_id,
                'error_message' => $data['response_message'],
                'error_code' => 500,
                'created_at' => current_time('mysql')
            ]);
        }
    }

    // Cron & Subscriptions
    public function handle_subscription_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product && $product->is_type('subscription')) {
                $expiry = gmdate('Y-m-d H:i:s', strtotime("+30 days"));
                $this->wpdb->insert($this->table_name, [
                    'user_id' => $order->get_user_id(),
                    'subscription_id' => $order_id,
                    'notification_type' => 'subscription_reminder',
                    'scheduled_time' => gmdate('Y-m-d H:i:s', strtotime("$expiry -2 days")),
                    'status' => 'pending',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ]);
            }
        }
    }

    public function schedule_notifications_cron() {
        if (!wp_next_scheduled('whatsapp_process_scheduled_notifications')) {
            wp_schedule_event(time(), 'hourly', 'whatsapp_process_scheduled_notifications');
        }
    }

    public function send_scheduled_notifications() {
        $tasks = $this->wpdb->get_results($this->wpdb->prepare( // phpcs:ignore
            "SELECT * FROM {$this->table_name} WHERE status = 'pending' AND scheduled_time <= %s", // phpcs:ignore
            gmdate('Y-m-d H:i:s') // phpcs:ignore
        ));
        foreach ($tasks as $task) {
            $phone = get_user_meta($task->user_id, 'billing_phone', true);
            $res = $this->dispatch_message($phone, $task->notification_type, ['[User ID]' => $task->user_id]);
            $this->wpdb->update($this->table_name, ['status' => $res['success'] ? 'sent' : 'failed'], ['id' => $task->id]);
        }
    }
}