<?php

namespace Smackcoders\WN;

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$smackcoders_wn_table_config = $wpdb->prefix . 'whatsapp_config';

// Retrieve current values
$smackcoders_wn_credentials = [];
$smackcoders_wn_results = $wpdb->get_results("SELECT setting_key, setting_value FROM $smackcoders_wn_table_config"); // phpcs:ignore
foreach ($smackcoders_wn_results as $smackcoders_wn_row) {
    $smackcoders_wn_credentials[$smackcoders_wn_row->setting_key] = WN_Security::decrypt_if_sensitive($smackcoders_wn_row->setting_key, $smackcoders_wn_row->setting_value);
}
$smackcoders_wn_credentials['admin_phone_number'] = get_option('admin_phone_number', '');

// Handle Form Submission
if (
$_SERVER['REQUEST_METHOD'] === 'POST' && // phpcs:ignore
isset($_POST['whatsapp_nonce']) &&
wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['whatsapp_nonce'])), 'whatsapp_integration_settings_action')
) {
    // 1. General Settings
    $smackcoders_wn_settings = [
        'provider' => sanitize_text_field(wp_unslash($_POST['provider'] ?? '')),
        'admin_phone_number' => sanitize_text_field(wp_unslash($_POST['admin_phone_number'] ?? '')),

        // Twilio
        'twilio_sid' => sanitize_text_field(wp_unslash($_POST['twilio_sid'] ?? '')),
        'twilio_token' => sanitize_text_field(wp_unslash($_POST['twilio_token'] ?? '')),
        'twilio_from' => sanitize_text_field(wp_unslash($_POST['twilio_from'] ?? '')),

        // Twilio SMS Settings
        'sms_provider' => sanitize_text_field(wp_unslash($_POST['sms_provider'] ?? '')),
        'sms_twilio_sid' => sanitize_text_field(wp_unslash($_POST['sms_twilio_sid'] ?? '')),
        'sms_twilio_token' => sanitize_text_field(wp_unslash($_POST['sms_twilio_token'] ?? '')),
        'sms_twilio_from' => sanitize_text_field(wp_unslash($_POST['sms_twilio_from'] ?? '')),

        // Infobip
        'infobip_api_key' => sanitize_text_field(wp_unslash($_POST['infobip_api_key'] ?? '')),
        'infobip_base_url' => sanitize_text_field(wp_unslash($_POST['infobip_base_url'] ?? '')),
        'infobip_sender' => sanitize_text_field(wp_unslash($_POST['infobip_sender'] ?? '')),

        // Gupshup
        'gupshup_api_key' => sanitize_text_field(wp_unslash($_POST['gupshup_api_key'] ?? '')),
        'gupshup_botname' => sanitize_text_field(wp_unslash($_POST['gupshup_botname'] ?? '')),
        'gupshup_sender' => sanitize_text_field(wp_unslash($_POST['gupshup_sender'] ?? '')),

        // Email Settings
        'email_provider' => sanitize_text_field(wp_unslash($_POST['email_provider'] ?? '')),
        'email_from_name' => sanitize_text_field(wp_unslash($_POST['email_from_name'] ?? '')),
        'sendgrid_from_email' => sanitize_email(wp_unslash($_POST['sendgrid_from_email'] ?? '')),
        'mailtrap_from_email' => sanitize_email(wp_unslash($_POST['mailtrap_from_email'] ?? '')),
        'brevo_from_email' => sanitize_email(wp_unslash($_POST['brevo_from_email'] ?? '')),
        'sendgrid_api_key' => sanitize_text_field(wp_unslash($_POST['sendgrid_api_key'] ?? '')),
        'mailtrap_api_key' => sanitize_text_field(wp_unslash($_POST['mailtrap_api_key'] ?? '')),
        'brevo_api_key' => sanitize_text_field(wp_unslash($_POST['brevo_api_key'] ?? '')),
    ];

    // 2. Templates & Toggles processing
    $smackcoders_wn_notification_types = [
        'order_confirmation', 'order_status_change', 'failed_order', 'on_hold_order',
        'cancelled_order', 'refunded_order', 'completed_order', 'abandoned_cart',
        'subscription_reminder', 'subscription_expiry', 'pending_payment',
        'new_user_registration', 'profile_update', 'admin_new_order', 'admin_new_customer',
        'invoice'
    ];

    foreach ($smackcoders_wn_notification_types as $smackcoders_wn_type) {
        $smackcoders_wn_msg_key = $smackcoders_wn_type . '_message';
        $smackcoders_wn_toggle_key = $smackcoders_wn_type . '_enabled';
        $smackcoders_wn_email_toggle_key = $smackcoders_wn_type . '_email_enabled';

        if (isset($_POST[$smackcoders_wn_msg_key])) {
            $smackcoders_wn_settings[$smackcoders_wn_msg_key] = sanitize_textarea_field(wp_unslash($_POST[$smackcoders_wn_msg_key]));
        }
        $smackcoders_wn_settings[$smackcoders_wn_toggle_key] = isset($_POST[$smackcoders_wn_toggle_key]) ? '1' : '0';
        $smackcoders_wn_settings[$smackcoders_wn_email_toggle_key] = isset($_POST[$smackcoders_wn_email_toggle_key]) ? '1' : '0';
        $smackcoders_wn_settings[$smackcoders_wn_type . '_sms_enabled'] = isset($_POST[$smackcoders_wn_type . '_sms_enabled']) ? '1' : '0';
    }

    foreach ($smackcoders_wn_settings as $smackcoders_wn_key => $smackcoders_wn_value) {
        $smackcoders_wn_value = WN_Security::encrypt_if_sensitive($smackcoders_wn_key, $smackcoders_wn_value);
        $smackcoders_wn_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $smackcoders_wn_table_config WHERE setting_key = %s", $smackcoders_wn_key)); // phpcs:ignore
        if ($smackcoders_wn_exists) {
            $wpdb->update($smackcoders_wn_table_config, ['setting_value' => $smackcoders_wn_value], ['setting_key' => $smackcoders_wn_key]); // phpcs:ignore
        }
        else {
            $wpdb->insert($smackcoders_wn_table_config, ['setting_key' => $smackcoders_wn_key, 'setting_value' => $smackcoders_wn_value]); // phpcs:ignore
        }
    }
    update_option('admin_phone_number', $smackcoders_wn_settings['admin_phone_number']);

    // Refresh $credentials for the view
    $smackcoders_wn_credentials = $smackcoders_wn_settings;
    echo '<div class="notice notice-success smack-modern-notice"><p>Settings Saved Successfully! 🚀</p></div>';
}
?>


<div class="smack-modern-wrapper">
    <div class="smack-modern-header">
        <div class="header-content">
            <div class="header-icon-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            </div>
            <div class="title-group">
                <h1>WhatsApp Integration</h1>
                <p>Configure your gateway and notification templates</p>
            </div>
        </div>
    </div>

    <form method="post" class="smack-modern-form">
        <?php wp_nonce_field('whatsapp_integration_settings_action', 'whatsapp_nonce'); ?>

        <div class="smack-settings-tabs">
            <div class="smack-tabs-nav">
                <div class="smack-sidebar-brand">
                    <div class="smack-sidebar-logo">
                        <span class="dashicons dashicons-whatsapp"></span>
                    </div>
                    <div class="smack-sidebar-title-wrap">
                        <div class="smack-sidebar-title">WhatsApp</div>
                        <div class="smack-sidebar-subtitle">Integration</div>
                    </div>
                </div>

                <button type="button" class="tab-btn active" data-tab="api-config">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="smack-tab-icon"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg> Whatsapp API Configuration
                </button>
            <button type="button" class="tab-btn" data-tab="sms-config">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="smack-tab-icon"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg> SMS Configuration
            </button>
            <button type="button" class="tab-btn" data-tab="email-config">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="smack-tab-icon"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg> Email Configuration
            </button>
            <button type="button" class="tab-btn" data-tab="admin-settings">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="smack-tab-icon"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg> Admin Settings
            </button>
            <button type="button" class="tab-btn" data-tab="notif-templates">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="smack-tab-icon"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Notification Templates
            </button>
            <button type="button" class="tab-btn" data-tab="notif-toggles">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="smack-tab-icon"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg> Toggles
            </button>
        </div>

        <div class="smack-main-content">
            <div class="smack-tabs-content">
                <!-- API Configuration Tab -->
            <div id="api-config" class="tab-pane active">
                <div class="smack-tab-page-header">
                    <div class="smack-tab-title-group">
                        <h2>Whatsapp API Configuration</h2>
                        <p>Configure your WhatsApp gateway and authentication credentials.</p>
                    </div>
                    <button type="button" class="help-trigger" data-help="api">
                        <span class="dashicons dashicons-editor-help"></span> Help
                    </button>
                </div>
                <div class="smack-card">
                    <div class="card-content">
                        <div class="modern-field">
                            <label>Select Your Provider</label>
                            <select name="provider" id="provider-selector">
                                <option value="twilio" <?php selected($smackcoders_wn_credentials['provider'] ?? '', 'twilio'); ?>>Twilio</option>
                                <option value="infobip" <?php selected($smackcoders_wn_credentials['provider'] ?? '', 'infobip'); ?>>Infobip</option>
                                <option value="gupshup" <?php selected($smackcoders_wn_credentials['provider'] ?? '', 'gupshup'); ?>>Gupshup</option>
                            </select>
                        </div>

                        <!-- Twilio -->
                        <div id="twilio-fields" class="provider-fields">
                            <div class="modern-field"><label>Twilio SID</label><input type="text" name="twilio_sid" value="<?php echo esc_attr($smackcoders_wn_credentials['twilio_sid'] ?? ''); ?>" placeholder="ACxxxx..."></div>
                            <div class="modern-field"><label>Auth Token</label><input type="password" name="twilio_token" value="<?php echo esc_attr($smackcoders_wn_credentials['twilio_token'] ?? ''); ?>" placeholder="Enter Token"></div>
                            <div class="modern-field"><label>Twilio Phone Number</label><input type="text" name="twilio_from" value="<?php echo esc_attr($smackcoders_wn_credentials['twilio_from'] ?? ''); ?>" placeholder="+1..."></div>
                        </div>

                        <!-- Infobip -->
                        <div id="infobip-fields" class="provider-fields" style="display:none;">
                            <div class="modern-field"><label>Infobip API Key</label><input type="text" name="infobip_api_key" value="<?php echo esc_attr($smackcoders_wn_credentials['infobip_api_key'] ?? ''); ?>" placeholder="Enter API Key"></div>
                            <div class="modern-field"><label>Base URL</label><input type="text" name="infobip_base_url" value="<?php echo esc_attr($smackcoders_wn_credentials['infobip_base_url'] ?? ''); ?>" placeholder="https://xxx.api.infobip.com"></div>
                            <div class="modern-field"><label>Sender ID</label><input type="text" name="infobip_sender" value="<?php echo esc_attr($smackcoders_wn_credentials['infobip_sender'] ?? ''); ?>" placeholder="Enter Sender Number"></div>
                        </div>

                        <!-- Gupshup -->
                        <div id="gupshup-fields" class="provider-fields" style="display:none;">
                            <div class="modern-field"><label>Gupshup API Key</label><input type="text" name="gupshup_api_key" value="<?php echo esc_attr($smackcoders_wn_credentials['gupshup_api_key'] ?? ''); ?>" placeholder="Enter API Key"></div>
                            <div class="modern-field"><label>Bot Name</label><input type="text" name="gupshup_botname" value="<?php echo esc_attr($smackcoders_wn_credentials['gupshup_botname'] ?? ''); ?>" placeholder="e.g. MyWhatsAppBot"></div>
                            <div class="modern-field"><label>Sender Number</label><input type="text" name="gupshup_sender" value="<?php echo esc_attr($smackcoders_wn_credentials['gupshup_sender'] ?? ''); ?>" placeholder="Enter Sender Number"></div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- SMS Configuration Tab -->
            <div id="sms-config" class="tab-pane">
                <div class="smack-tab-page-header">
                    <div class="smack-tab-title-group">
                        <h2>SMS Configuration</h2>
                        <p>Set up your SMS provider for fallback notifications.</p>
                    </div>
                    <button type="button" class="help-trigger" data-help="sms">
                        <span class="dashicons dashicons-editor-help"></span> Help
                    </button>
                </div>
                <div class="smack-card">
                    <div class="card-content">
                        <div class="modern-field">
                            <label>Select SMS Provider</label>
                            <select name="sms_provider" id="sms-provider-selector">
                                <option value="twilio" <?php selected($smackcoders_wn_credentials['sms_provider'] ?? '', 'twilio'); ?>>Twilio</option>
                            </select>
                        </div>

                        <!-- Twilio SMS -->
                        <div id="sms-twilio-fields" class="sms-provider-fields" style="display:none;">
                            <div class="modern-field"><label>Twilio SID</label><input type="text" name="sms_twilio_sid" value="<?php echo esc_attr($smackcoders_wn_credentials['sms_twilio_sid'] ?? ''); ?>" placeholder="AC..."></div>
                            <div class="modern-field"><label>Auth Token</label><input type="password" name="sms_twilio_token" value="<?php echo esc_attr($smackcoders_wn_credentials['sms_twilio_token'] ?? ''); ?>" placeholder="Token..."></div>
                            <div class="modern-field"><label>Twilio SMS Number</label><input type="text" name="sms_twilio_from" value="<?php echo esc_attr($smackcoders_wn_credentials['sms_twilio_from'] ?? ''); ?>" placeholder="+1234567890"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Configuration Tab -->
            <div id="email-config" class="tab-pane">
                <div class="smack-tab-page-header">
                    <div class="smack-tab-title-group">
                        <h2>Email Configuration</h2>
                        <p>Configure your email provider for notification delivery.</p>
                    </div>
                    <button type="button" class="help-trigger" data-help="email">
                        <span class="dashicons dashicons-editor-help"></span> Help
                    </button>
                </div>
                <div class="smack-card">
                    <div class="card-content">
                        <div class="modern-field">
                            <label>Select Email Provider</label>
                            <select name="email_provider" id="email-provider-selector">
                                <option value="sendgrid" <?php selected($smackcoders_wn_credentials['email_provider'] ?? '', 'sendgrid'); ?>>SendGrid (Twilio)</option>
                                <option value="mailtrap" <?php selected($smackcoders_wn_credentials['email_provider'] ?? '', 'mailtrap'); ?>>Mailtrap</option>
                                <option value="brevo" <?php selected($smackcoders_wn_credentials['email_provider'] ?? '', 'brevo'); ?>>Brevo</option>
                            </select>
                        </div>

                        <!-- SendGrid -->
                        <div id="sendgrid-fields" class="email-provider-fields" style="display:none;">
                            <div class="modern-field"><label>SendGrid From Email</label><input type="email" name="sendgrid_from_email" value="<?php echo esc_attr($smackcoders_wn_credentials['sendgrid_from_email'] ?? ''); ?>" placeholder="auth@domain.com"></div>
                            <div class="modern-field"><label>SendGrid API Key</label><input type="password" name="sendgrid_api_key" value="<?php echo esc_attr($smackcoders_wn_credentials['sendgrid_api_key'] ?? ''); ?>" placeholder="SG.xxx..."></div>
                        </div>

                        <!-- Mailtrap -->
                        <div id="mailtrap-fields" class="email-provider-fields" style="display:none;">
                            <div class="modern-field"><label>Mailtrap From Email</label><input type="email" name="mailtrap_from_email" value="<?php echo esc_attr($smackcoders_wn_credentials['mailtrap_from_email'] ?? ''); ?>" placeholder="mailtrap@demomailtrap.co"></div>
                            <div class="modern-field"><label>Mailtrap API Key</label><input type="password" name="mailtrap_api_key" value="<?php echo esc_attr($smackcoders_wn_credentials['mailtrap_api_key'] ?? ''); ?>" placeholder="MT.xxx..."></div>
                        </div>

                        <!-- Brevo -->
                        <div id="brevo-fields" class="email-provider-fields" style="display:none;">
                            <div class="modern-field"><label>Brevo From Email</label><input type="email" name="brevo_from_email" value="<?php echo esc_attr($smackcoders_wn_credentials['brevo_from_email'] ?? ''); ?>" placeholder="auth@domain.com"></div>
                            <div class="modern-field"><label>Brevo API Key</label><input type="password" name="brevo_api_key" value="<?php echo esc_attr($smackcoders_wn_credentials['brevo_api_key'] ?? ''); ?>" placeholder="xkeysib-xxx..."></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin Settings Tab -->
            <div id="admin-settings" class="tab-pane">
                <div class="smack-tab-page-header">
                    <div class="smack-tab-title-group">
                        <h2>Admin Settings</h2>
                        <p>Configure administrative notifications and preferences.</p>
                    </div>
                    <button type="button" class="help-trigger" data-help="admin">
                        <span class="dashicons dashicons-editor-help"></span> Help
                    </button>
                </div>
                <div class="smack-card">
                    <div class="card-content">
                        <div class="modern-field">
                            <label>Admin WhatsApp Number</label>
                            <span class="field-desc">Receive real-time alerts for new orders and customers</span>
                            <input type="text" name="admin_phone_number" value="<?php echo esc_attr($smackcoders_wn_credentials['admin_phone_number']); ?>" placeholder="+91 999 999 9999">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Templates Tab -->
            <div id="notif-templates" class="tab-pane">
                <div class="smack-tab-page-header">
                    <div class="smack-tab-title-group">
                        <h2>Notification Templates</h2>
                        <p>Customize the message templates for different events.</p>
                    </div>
                    <button type="button" class="help-trigger" data-help="templates">
                        <span class="dashicons dashicons-editor-help"></span> Help
                    </button>
                </div>
                <div class="smack-card">
                    <div class="card-content">
                        <div class="horizontal-list">
                            <?php
$smackcoders_wn_notifs = [
    'order_confirmation' => 'Order Confirmation',
    'order_status_change' => 'Status Change Alert',
    'subscription_reminder' => 'Subscription Reminder',
    'admin_new_order' => 'New Order (Admin)',
    'admin_new_customer' => 'New Customer (Admin)',
    'invoice' => 'Invoice Notification',
];
foreach ($smackcoders_wn_notifs as $smackcoders_wn_key => $smackcoders_wn_label):
    $smackcoders_wn_msg = $smackcoders_wn_credentials[$smackcoders_wn_key . '_message'] ?? '';
?>
                            <div class="horizontal-row">
                                <div class="row-label">
                                    <label><?php echo esc_html($smackcoders_wn_label); ?></label>
                                </div>
                                <div class="row-input">
                                    <textarea name="<?php echo esc_attr($smackcoders_wn_key); ?>_message" placeholder="Enter message template..."><?php echo esc_textarea($smackcoders_wn_msg); ?></textarea>
                                    <div class="placeholders">Available: [Order ID]</div>
                                </div>
                            </div>
                            <?php
endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toggles Tab -->
            <div id="notif-toggles" class="tab-pane">
                <div class="smack-tab-page-header">
                    <div class="smack-tab-title-group">
                        <h2>Notification Toggles</h2>
                        <p>Enable or disable specific notifications across channels.</p>
                    </div>
                    <button type="button" class="help-trigger" data-help="toggles">
                        <span class="dashicons dashicons-editor-help"></span> Help
                    </button>
                </div>
                <div class="smack-card">
                    <div class="card-content">
                        <div class="horizontal-list">
                            <?php
foreach ($smackcoders_wn_notifs as $smackcoders_wn_key => $smackcoders_wn_label):
    $smackcoders_wn_enabled = ($smackcoders_wn_credentials[$smackcoders_wn_key . '_enabled'] ?? '1') === '1';
    $smackcoders_wn_email_enabled = ($smackcoders_wn_credentials[$smackcoders_wn_key . '_email_enabled'] ?? '0') === '1';
    $smackcoders_wn_sms_enabled = ($smackcoders_wn_credentials[$smackcoders_wn_key . '_sms_enabled'] ?? '0') === '1';
?>
                            <div class="horizontal-row">
                                <div class="row-label">
                                    <label><?php echo esc_html($smackcoders_wn_label); ?></label>
                                </div>
                                <div class="row-input">
                                    <div class="channel-toggles">
                                        <label class="switch-label">WhatsApp: 
                                            <label class="switch small">
                                                <input type="checkbox" name="<?php echo esc_attr($smackcoders_wn_key); ?>_enabled" value="1" <?php checked($smackcoders_wn_enabled); ?>>
                                                <span class="slider round"></span>
                                            </label>
                                        </label>
                                        <label class="switch-label">Email: 
                                            <label class="switch small">
                                                <input type="checkbox" name="<?php echo esc_attr($smackcoders_wn_key); ?>_email_enabled" value="1" <?php checked($smackcoders_wn_email_enabled); ?>>
                                                <span class="slider round"></span>
                                            </label>
                                        </label>
                                        <label class="switch-label">SMS: 
                                            <label class="switch small">
                                                <input type="checkbox" name="<?php echo esc_attr($smackcoders_wn_key); ?>_sms_enabled" value="1" <?php checked($smackcoders_wn_sms_enabled); ?>>
                                                <span class="slider round"></span>
                                            </label>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php
endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <div class="smack-form-footer">
                <button type="submit" class="smack-submit-btn">Save Configuration</button>
            </div>
        </div>
        </div>
    </form>
</div>

<!-- Help Modal Container -->
<div id="help-modal" class="smack-modal-overlay" style="display:none;">
    <div class="smack-modal">
        <div class="smack-modal-header">
            <button class="close-modal">&times;</button>
            <h3 id="help-title">Help Instructions</h3>
        </div>
        <div class="smack-modal-body" id="help-content">
            <!-- Content dynamic via JS -->
        </div>
    </div>
</div>




