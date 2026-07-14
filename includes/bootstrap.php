<?php

namespace Smackcoders\WN;

if (!defined('ABSPATH')) {
    exit;
}

// Fix for cURL resolving timeouts on local Windows/XAMPP environments
add_action('http_api_curl', function($handle, $args, $url) {
    if (strpos($url, 'sendgrid.com') !== false || strpos($url, 'gupshup.io') !== false || strpos($url, 'infobip') !== false || strpos($url, 'twilio.com') !== false || strpos($url, 'mailtrap.io') !== false || strpos($url, 'brevo.com') !== false) {
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // phpcs:ignore 
        }
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30); // phpcs:ignore 
        curl_setopt($handle, CURLOPT_TIMEOUT, 30); // phpcs:ignore 
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false); // phpcs:ignore     
    }
}, 10, 3);

/////////////////////////////////////////////////////////////////////////////////
   
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/class-wn-woocommerce-events.php';
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/class-wn-scheduler.php';

require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/class-wn-security.php';

require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/class-wn-privacy.php';

// Issue #16/#28: Logging
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/class-wn-logger.php';

// Issue #20: Bulk Dispatcher
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/class-wn-bulk-dispatcher.php';

// Issue #21: i18n / Multi-Language Support
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/class-wn-i18n.php';
WN_I18n::init();

// Issue #8: Phone Validator / Customer Data Sync
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/class-wn-phone-validator.php';
WN_PhoneValidator::init();

// Register custom cron interval for bulk dispatcher (Issue #20)
add_filter( 'cron_schedules', [ 'Smackcoders\WN\WN_BulkDispatcher', 'add_cron_interval' ] );

// Schedule bulk dispatcher cron
add_action( 'wp_loaded', function () {
    $dispatcher = new WN_BulkDispatcher();
    $dispatcher->schedule_cron();
} );

// Process bulk queue on cron fire
add_action( 'wn_process_bulk_queue', function () {
    $dispatcher = new WN_BulkDispatcher();
    $dispatcher->process_queue();
} );

if (!class_exists('SmackWpNotifier')) {

    class SmackWpNotifier {

        private static $instance = null;
    
        public function __construct() {
       
            add_action('admin_menu', [$this, 'smack_wn_register_menus']);

            add_action('admin_enqueue_scripts', [$this, 'smack_wn_enqueue_assets']);

            add_action('admin_footer', [$this, 'smack_wn_render_admin_footer']);

            // Removes the default "Thank you for creating with WordPress" text
            add_filter('admin_footer_text', [$this, 'smack_wn_remove_default_footer']);

            // Removes the default WP version string from the footer
            add_filter('update_footer', [$this, 'smack_wn_remove_default_version'], PHP_INT_MAX);

            add_filter('set-screen-option', [$this, 'smack_wn_set_screen_option'], 10, 3);
            add_filter('manage_wp-notifier_page_whatsapp-audit-logs_columns', [$this, 'get_audit_logs_columns']);

            new WN_Privacy();
            
            // Ensure classes are instantiated after all plugins are loaded (especially WooCommerce)
            add_action('plugins_loaded', function() {
                new WN_EventManager();
            });
        }

        public static function get_instance() {

            if (self::$instance === null) {
    
                self::$instance = new self();
    
            }
            
            return self::$instance;
    
        }
        public function smack_wn_register_menus() {
          
            add_menu_page(

                'WP Notifier',

                'WP Notifier',

                'manage_options',

                'wp-notifier',

                [$this, 'render_provider_configuration_page'],

                'dashicons-whatsapp',

                6
            );

        
            $audit_logs_hook = add_submenu_page(

                'wp-notifier',

                'Audit Logs',

                'Audit Logs',

                'manage_options',

                'whatsapp-audit-logs',

                [$this, 'render_audit_logs_list']

            );

            add_action("load-{$audit_logs_hook}", [$this, 'onload_whatsapp_audit_logs_page']);

            /*
            add_submenu_page(

                'wp-notifier',

                esc_html__('User Guide', 'wp-notifier'),

                esc_html__('User Guide', 'wp-notifier'),

                'manage_options',

                'wp-notifier-user-guide',

                [$this, 'render_user_guide_page']

            );
            */

        }

        public function smack_wn_enqueue_assets($top_level_page_hook) {

            $allowed_hooks = [
                'toplevel_page_wp-notifier',
                'wp-notifier_page_whatsapp-audit-logs',
                'wp-notifier_page_wp-notifier-user-guide',
            ];
            if (!in_array($top_level_page_hook, $allowed_hooks, true)) {
                return;
            }
            
            wp_enqueue_style(
                'provider-configuration-css',
                WP_NOTIFIER_PLUGIN_ASSETS_URL . 'css/provider-configuration.css',
                [],
                '1.0.0',
                'all'
            );

            // Google Fonts for Modern UI
            wp_enqueue_style(
                'wn-google-fonts',
                'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap',
                [],
                '1.0.0',
            );

            // Modern Configuration Assets
            wp_enqueue_style(
                'smack-modern-config-css',
                WP_NOTIFIER_PLUGIN_ASSETS_URL . 'css/smack-modern-config.css',
                [],
                '1.0.0',
                'all'
            );
        
        
            wp_enqueue_script(
                'provider-configuration-js',
                WP_NOTIFIER_PLUGIN_ASSETS_URL . 'js/provider-configuration.js',
                ['jquery'],
                '1.0.0',
                true
            );

            wp_enqueue_script(
                'smack-modern-config-js',
                WP_NOTIFIER_PLUGIN_ASSETS_URL . 'js/smack-modern-config.js',
                ['jquery'],
                '1.0.0',
                true
            );
        }
        
        public function onload_whatsapp_audit_logs_page() {
            $args = [
                'label'   => esc_html__('Logs per page', 'wp-notifier'),
                'default' => 20,
                'option'  => 'whatsapp_logs_per_page'
            ];
            add_screen_option('per_page', $args);
        }

        public function smack_wn_set_screen_option($status, $option, $value) {
            if ('whatsapp_logs_per_page' === $option) {
                return $value;
            }
            return $status;
        }

        public function get_audit_logs_columns() {
            return [
                'cb' => '<input type="checkbox" />',
                'event_type' => esc_html__('Event Type', 'wp-notifier'),
                'recipient_phone' => esc_html__('Recipient Phone', 'wp-notifier'),
                'message' => esc_html__('Message', 'wp-notifier'),
                'sent_status' => esc_html__('Status', 'wp-notifier'),
                'response_message' => esc_html__('Reasons', 'wp-notifier'),
                'provider' => esc_html__('Provider', 'wp-notifier'),
                'created_at' => esc_html__('Date', 'wp-notifier')
            ];
        }

        public function render_audit_logs_list() {

            require_once WP_NOTIFIER_PLUGIN_DIR . 'admin/views/auditlogs.php';

            render_whatsapp_audit_log_page();

        }

        public function render_provider_configuration_page() {

            include WP_NOTIFIER_PLUGIN_DIR . 'admin/views/provider-configuration.php';

        }

        public function render_user_guide_page() {

            include WP_NOTIFIER_PLUGIN_DIR . 'admin/views/user-guide.php';

        }

        private function is_plugin_page(): bool {
            $screen = get_current_screen();
            return in_array($screen->id, [
                'toplevel_page_wp-notifier',
                'wp-notifier_page_whatsapp-audit-logs',
                'wp-notifier_page_wp-notifier-user-guide',
            ], true);
        }

        public function smack_wn_remove_default_footer($footer_text) {
            if ($this->is_plugin_page()) {
                return '';
            }
            return $footer_text;
        }

        public function smack_wn_remove_default_version($footer_version) {
            if ($this->is_plugin_page()) {
                return '';
            }
            return $footer_version;
        }

        public function smack_wn_render_admin_footer() {
            if (!$this->is_plugin_page()) {
                return;
            }
        
            echo '<div style="text-align: center; padding: 15px; color: #888; font-size: 13px;">';
            echo 'Powered by Smackcoders | Version 1.0';
            echo '</div>';
        }
        
    }

    new SmackWpNotifier();
}