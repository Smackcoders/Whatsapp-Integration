<?php

namespace Smackcoders\WN;

if (!defined('ABSPATH')) {
    exit;
}

class WhatsAppPluginInstaller {

    public static function install() {

        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        // Table: wp_whatsapp_notifications
        $table_notifications = "{$wpdb->prefix}whatsapp_notifications";

        $sql_notifications = "CREATE TABLE $table_notifications (

            id INT(11) NOT NULL AUTO_INCREMENT,

            event_type VARCHAR(50) NOT NULL COMMENT 'e.g. order_confirmation, shipping_update, subscription_reminder, etc.',

            recipient_phone VARCHAR(255) NOT NULL,

            message TEXT NOT NULL,

            sent_status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',

            response_message TEXT DEFAULT NULL,

            provider VARCHAR(50) DEFAULT NULL COMMENT 'WhatsApp API provider used (e.g., Twilio)',

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id)

        ) $charset_collate;";

        // Table: wp_whatsapp_error_logs
        $table_error_logs = "{$wpdb->prefix}whatsapp_error_logs";

        $sql_error_logs = "CREATE TABLE $table_error_logs (

            id INT(11) NOT NULL AUTO_INCREMENT,

            notification_id INT(11) DEFAULT NULL COMMENT 'Reference to wp_whatsapp_notifications.id',

            error_message TEXT NOT NULL,

            error_code INT DEFAULT NULL,

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id),

            INDEX (notification_id)

        ) $charset_collate;";

        // Table: wp_whatsapp_scheduled_notifications
        $table_scheduled = "{$wpdb->prefix}whatsapp_scheduled_notifications";

        $sql_scheduled = "CREATE TABLE $table_scheduled (

            id INT(11) NOT NULL AUTO_INCREMENT,

            user_id INT(11) NOT NULL COMMENT 'WordPress user ID',

            subscription_id INT(11) DEFAULT NULL COMMENT 'Optional subscription identifier',

            notification_type VARCHAR(50) NOT NULL COMMENT 'Either subscription_reminder or subscription_expiry',

            scheduled_time DATETIME NOT NULL,

            status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',

            error_message TEXT DEFAULT NULL,

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id)

        ) $charset_collate;";

        // Table: wp_whatsapp_config
        $table_config = "{$wpdb->prefix}whatsapp_config";

        $sql_config = "CREATE TABLE $table_config (

            id INT(11) NOT NULL AUTO_INCREMENT,

            setting_key VARCHAR(100) NOT NULL UNIQUE COMMENT 'Configuration key name',

            setting_value TEXT NOT NULL COMMENT 'Configuration value',

            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id)

        ) $charset_collate;";

        $table_subscription = "{$wpdb->prefix}subscriptions";

        $sql_subscription = "CREATE TABLE $table_subscription (

            id INT(11) NOT NULL AUTO_INCREMENT,

            user_id INT,

            phone_number VARCHAR(15),

            expiry_date DATETIME,

            status ENUM('active', 'expired'),

            last_notified DATETIME DEFAULT NULL,

            PRIMARY KEY (id)

        ) $charset_collate;";

        // Table: wp_whatsapp_bulk_queue (Issue #20 - Bulk Notification Queue)
        $table_bulk_queue = "{$wpdb->prefix}whatsapp_bulk_queue";

        $sql_bulk_queue = "CREATE TABLE $table_bulk_queue (

            id INT(11) NOT NULL AUTO_INCREMENT,

            recipient_phone VARCHAR(30) NOT NULL,

            notification_type VARCHAR(50) NOT NULL,

            replacements TEXT DEFAULT NULL,

            status ENUM('pending','processing','sent','failed') NOT NULL DEFAULT 'pending',

            retries TINYINT(3) NOT NULL DEFAULT 0,

            priority TINYINT(3) NOT NULL DEFAULT 10,

            error_message TEXT DEFAULT NULL,

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id),

            INDEX (status, priority, created_at)

        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Suppress unwanted output
        ob_start();

        dbDelta($sql_notifications);

        dbDelta($sql_error_logs);

        dbDelta($sql_scheduled);

        dbDelta($sql_config);

        dbDelta($sql_subscription);

        dbDelta($sql_bulk_queue);

        ob_end_clean();

        self::insert_whatsapp_config();

        self::insert_subscriptions();

    }

    public static function insert_whatsapp_config() {

        global $wpdb;
    
        $table_name = $wpdb->prefix . 'whatsapp_config';

        $existing_templates = $wpdb->get_var("SELECT COUNT(*) FROM $table_name"); // phpcs:ignore
    
        if ($existing_templates == 0) {

            $messages = [
                'failed_message'                => 'Payment failed for order #[Order ID] ❗ Your payment was unsuccessful. Please try again or use another payment method.',

                'new_user_registration_message' => 'Welcome! 🎉 Thank you for signing up. We are excited to have you on board!',

                'abandoned_cart_message'        => 'Hey, your cart is waiting! 🛒 You left some great items in your cart. Complete your purchase before they are gone!',

                'on_hold_order_message'         => 'Your order #[Order ID] is on hold ⏳ We\'ve received your order, but it\'s currently on hold. Please complete the payment to proceed.',

                'cancelled_order_message'       => 'Your order #[Order ID] has been cancelled ❌ If this was a mistake or you need assistance, please contact us.',

                'refunded_order_message'        => 'Refund processed for order #[Order ID] 💰 Your refund has been processed. It may take a few days to reflect in your account.',

                'completed_order_message'       => 'Great news! Your order #[Order ID] is on its way 🚚 Your order has been shipped.',

                'status_message'                =>'Your order has been changed from [old_status] to [new_status].',

                'post_update_message'           => 'A post or page has been updated! 📝 Check out the latest changes now!',

                'new_comment_message'           => 'Someone just commented on your post! 💬 Check it out and engage with them.',

                'new_post_message'              => 'A new post has been published! 🚀 Stay updated with our latest content.',

                'user_profile_update_message'   => 'Your profile has been updated successfully! 🎉 Keep your details up to date.',

                'new_review_submitted_message'  => 'A new review has been submitted! ⭐ Check it out and respond if needed.',

                'product_added_message'         => 'You just added [Product Name] (x[Quantity]) to your cart! 🛍️ Dont forget to complete your purchase.',

                'completed_order'               =>'Your Order #[Order ID] has been confirmed.Thank You for shopping with us🎉',

                'pending_payment_message'       =>'Your Payment process 💰 for order #[Order ID] is pending .Please complete the process as soon as possible.',

                'invoice_message'               => 'Your invoice for order #[Order ID] is ready. Total: [Total]. Thank you for shopping with us!'

            ];

            // Add _enabled flags
            foreach (array_keys($messages) as $mkey) {
                $type = str_replace('_message', '', $mkey);
                $messages[$type . '_enabled'] = '1';
            }
            
            // Explicitly add admin/invoice ones if not covered
            $messages['admin_new_order_enabled'] = '1';
            $messages['admin_new_customer_enabled'] = '1';
            $messages['invoice_enabled'] = '1';
    
            foreach ($messages as $key => $value) {

                $wpdb->insert( // phpcs:ignore

                    $table_name,

                    [

                        'setting_key'   => $key,

                        'setting_value' => $value,

                        'updated_at'    => current_time('mysql') 

                    ],

                    ['%s', '%s', '%s']

                );

            }

        }

    }

    public static function insert_subscriptions() {

        global $wpdb;

        $table_subscriptions = "{$wpdb->prefix}subscriptions";

        $existing_records = $wpdb->get_var("SELECT COUNT(*) FROM $table_subscriptions"); // phpcs:ignore

            if ($existing_records == 0) {
                // phpcs:disable 
                $wpdb->query("INSERT INTO $table_subscriptions 
                (`id`, `user_id`, `phone_number`, `expiry_date`, `status`, `last_notified`) 
                VALUES 
                ('1', '2', '+917708137070', DATE_ADD(NOW(), INTERVAL 2 DAY), 'active', NULL)"); // phpcs:enable
        }

    }

}