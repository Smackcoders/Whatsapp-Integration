<?php

namespace Smackcoders\WN;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * User Guide / Help Documentation Page
 * Implements Issue #27: Develop User Guide Documentation
 */

if (!current_user_can('manage_options')) {
    wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'wp-notifier'));
}
?>

<div class="smack-modern-wrapper">
    <div class="smack-modern-header">
        <div class="header-content">
            <div class="header-icon-wrapper">
                <span class="dashicons dashicons-book-alt"></span>
            </div>
            <div class="title-group">
                <h1><?php esc_html_e('WP Notifier - User Guide', 'wp-notifier'); ?></h1>
                <p><?php esc_html_e('Complete configuration reference, troubleshooting guide, and best practices', 'wp-notifier'); ?></p>
            </div>
        </div>
    </div>

    <div class="smack-tabs-nav">
        <button type="button" class="tab-btn active" data-tab="guide-overview">
            <span class="dashicons dashicons-info"></span> <?php esc_html_e('Overview', 'wp-notifier'); ?>
        </button>
        <button type="button" class="tab-btn" data-tab="guide-whatsapp">
            <span class="dashicons dashicons-whatsapp"></span> <?php esc_html_e('WhatsApp Setup', 'wp-notifier'); ?>
        </button>
        <button type="button" class="tab-btn" data-tab="guide-sms">
            <span class="dashicons dashicons-phone"></span> <?php esc_html_e('SMS Setup', 'wp-notifier'); ?>
        </button>
        <button type="button" class="tab-btn" data-tab="guide-email">
            <span class="dashicons dashicons-email-alt"></span> <?php esc_html_e('Email Setup', 'wp-notifier'); ?>
        </button>
        <button type="button" class="tab-btn" data-tab="guide-templates">
            <span class="dashicons dashicons-edit"></span> <?php esc_html_e('Message Templates', 'wp-notifier'); ?>
        </button>
        <button type="button" class="tab-btn" data-tab="guide-troubleshoot">
            <span class="dashicons dashicons-warning"></span> <?php esc_html_e('Troubleshooting', 'wp-notifier'); ?>
        </button>
    </div>

    <div class="smack-tabs-content">

        <!-- Overview Tab -->
        <div id="guide-overview" class="tab-pane active">
            <div class="smack-card">
                <div class="card-header">
                    <div class="header-left">
                        <span class="dashicons dashicons-info"></span>
                        <h2><?php esc_html_e('Plugin Overview', 'wp-notifier'); ?></h2>
                    </div>
                </div>
                <div class="card-content">
                    <p><?php esc_html_e('WP Notifier sends automated WhatsApp, SMS, and Email notifications for WooCommerce events. It supports multiple API providers and lets you customize message templates for each event type.', 'wp-notifier'); ?></p>

                    <h3><?php esc_html_e('Supported Notification Events', 'wp-notifier'); ?></h3>
                    <ul style="list-style:disc;padding-left:20px;">
                        <li><?php esc_html_e('Order Confirmation – Sent when a customer places an order.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Order Status Change – Fired when order status transitions (e.g. Processing → Shipped).', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Failed Order – Sent when payment fails.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Cancelled Order – Sent when an order is cancelled.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Refunded Order – Sent when a refund is processed.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('On-Hold Order – Sent when order is placed on hold.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Pending Payment – Sent when payment is pending.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Subscription Reminder – Sent 2 days before subscription expiry.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Admin New Order Alert – Notifies admin when a new order is placed.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Admin New Customer Alert – Notifies admin when a new user registers.', 'wp-notifier'); ?></li>
                    </ul>

                    <h3><?php esc_html_e('Quick Start', 'wp-notifier'); ?></h3>
                    <ol style="list-style:decimal;padding-left:20px;">
                        <li><?php esc_html_e('Go to WP Notifier → WhatsApp API Configuration and select your provider.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Enter your API credentials and save.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Set your Admin WhatsApp number under Admin Settings.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Customize message templates under Notification Templates.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Enable or disable channels per event type under the Toggles tab.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Place a test WooCommerce order to verify delivery.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Review logs under WP Notifier → Audit Logs.', 'wp-notifier'); ?></li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- WhatsApp Setup Tab -->
        <div id="guide-whatsapp" class="tab-pane">
            <div class="smack-card">
                <div class="card-header">
                    <div class="header-left">
                        <span class="dashicons dashicons-whatsapp"></span>
                        <h2><?php esc_html_e('WhatsApp Provider Setup', 'wp-notifier'); ?></h2>
                    </div>
                </div>
                <div class="card-content">

                    <h3>Twilio</h3>
                    <ol style="list-style:decimal;padding-left:20px;">
                        <li><?php esc_html_e('Log in to your Twilio account at console.twilio.com.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('From the Dashboard, copy your Account SID and Auth Token.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Enable the WhatsApp Sandbox or get a WhatsApp-approved number.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Enter your WhatsApp-enabled Twilio number in the "Twilio Phone Number" field (include + and country code, e.g. +14155552671).', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Ensure your customers\' numbers have joined your WhatsApp Sandbox if using trial mode.', 'wp-notifier'); ?></li>
                    </ol>

                    <h3>Infobip</h3>
                    <ol style="list-style:decimal;padding-left:20px;">
                        <li><?php esc_html_e('Log in to portal.infobip.com.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Go to Settings → API Keys and generate a key.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Note your unique Base URL (shown in the portal, e.g. https://abc123.api.infobip.com).', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Enter your WhatsApp Sender ID (registered in Infobip).', 'wp-notifier'); ?></li>
                    </ol>

                    <h3>Gupshup</h3>
                    <ol style="list-style:decimal;padding-left:20px;">
                        <li><?php esc_html_e('Log in to app.gupshup.io.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Create or open your WhatsApp app.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Copy the API Key from the App Settings panel.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Enter your Bot Name and Sender Number (registered WhatsApp number).', 'wp-notifier'); ?></li>
                    </ol>

                    <h3><?php esc_html_e('Phone Number Format', 'wp-notifier'); ?></h3>
                    <p><?php esc_html_e('All recipient numbers are automatically normalized to include the country code. For Indian numbers (10 digits), the prefix 91 is added automatically. Ensure WooCommerce customer billing phone fields contain valid numbers.', 'wp-notifier'); ?></p>
                </div>
            </div>
        </div>

        <!-- SMS Setup Tab -->
        <div id="guide-sms" class="tab-pane">
            <div class="smack-card">
                <div class="card-header">
                    <div class="header-left">
                        <span class="dashicons dashicons-phone"></span>
                        <h2><?php esc_html_e('SMS Provider Setup', 'wp-notifier'); ?></h2>
                    </div>
                </div>
                <div class="card-content">
                    <h3><?php esc_html_e('Twilio SMS', 'wp-notifier'); ?></h3>
                    <ol style="list-style:decimal;padding-left:20px;">
                        <li><?php esc_html_e('Log in to Twilio Console.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Go to Phone Numbers → Manage → Active Numbers.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Find an SMS-enabled number and copy it.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Enter this number in the SMS Configuration → Twilio SMS Number field.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Note: Trial accounts can only send to verified numbers and have a 160-character limit.', 'wp-notifier'); ?></li>
                    </ol>
                    <p><strong><?php esc_html_e('Note:', 'wp-notifier'); ?></strong> <?php esc_html_e('SMS credentials are separate from WhatsApp credentials so you can use different Twilio numbers or accounts for each channel.', 'wp-notifier'); ?></p>
                </div>
            </div>
        </div>

        <!-- Email Setup Tab -->
        <div id="guide-email" class="tab-pane">
            <div class="smack-card">
                <div class="card-header">
                    <div class="header-left">
                        <span class="dashicons dashicons-email-alt"></span>
                        <h2><?php esc_html_e('Email Provider Setup', 'wp-notifier'); ?></h2>
                    </div>
                </div>
                <div class="card-content">

                    <h3>SendGrid</h3>
                    <ol style="list-style:decimal;padding-left:20px;">
                        <li><?php esc_html_e('Log in to app.sendgrid.com.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Go to Settings → API Keys → Create API Key.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Grant "Mail Send" permission (Full Access or Restricted).', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Verify your sender email address under Sender Authentication.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Enter the API Key and verified From Email in the plugin.', 'wp-notifier'); ?></li>
                    </ol>

                    <h3>Mailtrap</h3>
                    <ol style="list-style:decimal;padding-left:20px;">
                        <li><?php esc_html_e('Log in to mailtrap.io.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Go to Sending Domains and verify your domain.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Go to API Tokens and generate a token.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Enter the token and your sender email in the plugin.', 'wp-notifier'); ?></li>
                    </ol>

                    <h3>Brevo (Sendinblue)</h3>
                    <ol style="list-style:decimal;padding-left:20px;">
                        <li><?php esc_html_e('Log in to app.brevo.com.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Go to SMTP & API → API Keys → Generate a New API Key.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Verify your sender email under Senders & IPs.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Enter the v3 API Key and verified From Email in the plugin.', 'wp-notifier'); ?></li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Message Templates Tab -->
        <div id="guide-templates" class="tab-pane">
            <div class="smack-card">
                <div class="card-header">
                    <div class="header-left">
                        <span class="dashicons dashicons-edit"></span>
                        <h2><?php esc_html_e('Message Templates & Placeholders', 'wp-notifier'); ?></h2>
                    </div>
                </div>
                <div class="card-content">
                    <p><?php esc_html_e('Use the following placeholders in your templates. They will be replaced with actual values when the notification is sent:', 'wp-notifier'); ?></p>

                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f8f9fa;">
                                <th style="padding:8px;border:1px solid #ddd;text-align:left;"><?php esc_html_e('Placeholder', 'wp-notifier'); ?></th>
                                <th style="padding:8px;border:1px solid #ddd;text-align:left;"><?php esc_html_e('Description', 'wp-notifier'); ?></th>
                                <th style="padding:8px;border:1px solid #ddd;text-align:left;"><?php esc_html_e('Applicable Events', 'wp-notifier'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding:8px;border:1px solid #ddd;"><code>[Order ID]</code></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('WooCommerce order number', 'wp-notifier'); ?></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('All order events', 'wp-notifier'); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:8px;border:1px solid #ddd;"><code>[Customer Name]</code></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Customer full name from billing info', 'wp-notifier'); ?></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Order events', 'wp-notifier'); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:8px;border:1px solid #ddd;"><code>[Total]</code></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Order total with currency', 'wp-notifier'); ?></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Order confirmation, admin new order', 'wp-notifier'); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:8px;border:1px solid #ddd;"><code>[Status]</code></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Current order status', 'wp-notifier'); ?></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Status change events', 'wp-notifier'); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:8px;border:1px solid #ddd;"><code>[old_status]</code></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Previous order status', 'wp-notifier'); ?></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Order status change', 'wp-notifier'); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:8px;border:1px solid #ddd;"><code>[new_status]</code></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('New order status', 'wp-notifier'); ?></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Order status change', 'wp-notifier'); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:8px;border:1px solid #ddd;"><code>[Items]</code></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('List of ordered items with quantities', 'wp-notifier'); ?></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Admin new order', 'wp-notifier'); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:8px;border:1px solid #ddd;"><code>[Email]</code></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Customer email address', 'wp-notifier'); ?></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Admin notifications', 'wp-notifier'); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:8px;border:1px solid #ddd;"><code>[Name]</code></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('User full name', 'wp-notifier'); ?></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('New customer alert', 'wp-notifier'); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:8px;border:1px solid #ddd;"><code>[username]</code></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('WordPress username (login)', 'wp-notifier'); ?></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Customer registration', 'wp-notifier'); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:8px;border:1px solid #ddd;"><code>[Expiry Date]</code></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Subscription expiry date', 'wp-notifier'); ?></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Subscription reminder/expiry', 'wp-notifier'); ?></td>
                            </tr>
                            <tr>
                                <td style="padding:8px;border:1px solid #ddd;"><code>[Admin URL]</code></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Link to order in WP admin', 'wp-notifier'); ?></td>
                                <td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Admin new order', 'wp-notifier'); ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <h3 style="margin-top:20px;"><?php esc_html_e('Best Practices', 'wp-notifier'); ?></h3>
                    <ul style="list-style:disc;padding-left:20px;">
                        <li><?php esc_html_e('Keep WhatsApp messages under 1,024 characters for best delivery.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Avoid excessive special characters or URLs in trial account messages.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Test templates by placing a sample WooCommerce order before going live.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Use emoji sparingly—some carriers may strip or block emoji-heavy messages.', 'wp-notifier'); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Troubleshooting Tab -->
        <div id="guide-troubleshoot" class="tab-pane">
            <div class="smack-card">
                <div class="card-header">
                    <div class="header-left">
                        <span class="dashicons dashicons-warning"></span>
                        <h2><?php esc_html_e('Troubleshooting Guide', 'wp-notifier'); ?></h2>
                    </div>
                </div>
                <div class="card-content">

                    <h3><?php esc_html_e('Notifications not being sent', 'wp-notifier'); ?></h3>
                    <ol style="list-style:decimal;padding-left:20px;">
                        <li><?php esc_html_e('Check that the correct provider is selected and credentials are saved.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Verify the relevant notification toggle is enabled under the Toggles tab.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Review the Audit Logs page for "failed" entries and check the Reasons column.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Enable WP_DEBUG_LOG in wp-config.php and check wp-content/debug.log for [WP Notifier] entries.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('Ensure the customer\'s billing phone number is set and correctly formatted.', 'wp-notifier'); ?></li>
                    </ol>

                    <h3><?php esc_html_e('Authentication / credential errors', 'wp-notifier'); ?></h3>
                    <ul style="list-style:disc;padding-left:20px;">
                        <li><?php esc_html_e('Double-check that API keys were copied without extra spaces.', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('For Twilio: ensure the From number is in E.164 format (e.g. +14155552671).', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('For Infobip: paste the exact Base URL shown in your portal (unique per account).', 'wp-notifier'); ?></li>
                        <li><?php esc_html_e('For Gupshup: the Bot Name must match exactly as registered in the dashboard.', 'wp-notifier'); ?></li>
                    </ul>

                    <h3><?php esc_html_e('Duplicate notifications', 'wp-notifier'); ?></h3>
                    <p><?php esc_html_e('The plugin uses post meta flags (_wn_customer_notified and _wn_admin_notified) to prevent duplicates. If you see duplicates, check if another plugin is triggering WooCommerce hooks multiple times. Check the Audit Logs to confirm.', 'wp-notifier'); ?></p>

                    <h3><?php esc_html_e('GDPR / Data Export-Erase', 'wp-notifier'); ?></h3>
                    <p><?php esc_html_e('The plugin integrates with WordPress\'s built-in Personal Data tools. To export or erase a customer\'s notification data, go to Tools → Erase Personal Data and enter the customer\'s email address.', 'wp-notifier'); ?></p>

                    <h3><?php esc_html_e('Cron / Scheduler not running', 'wp-notifier'); ?></h3>
                    <p><?php esc_html_e('WordPress cron relies on site visits. On low-traffic sites, scheduled notifications may be delayed. Use a real cron job (server-level) that calls:', 'wp-notifier'); ?></p>
                    <code>wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron &gt;/dev/null 2&gt;&amp;1</code>
                    <p style="margin-top:8px;"><?php esc_html_e('Set this to run every 5 minutes via crontab for reliable delivery.', 'wp-notifier'); ?></p>

                    <h3><?php esc_html_e('Common Audit Log Status Codes', 'wp-notifier'); ?></h3>
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f8f9fa;">
                                <th style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Status', 'wp-notifier'); ?></th>
                                <th style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Meaning', 'wp-notifier'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td style="padding:8px;border:1px solid #ddd;">sent</td><td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Successfully delivered to provider.', 'wp-notifier'); ?></td></tr>
                            <tr><td style="padding:8px;border:1px solid #ddd;">failed</td><td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Provider returned an error. Check Reasons column.', 'wp-notifier'); ?></td></tr>
                            <tr><td style="padding:8px;border:1px solid #ddd;">pending</td><td style="padding:8px;border:1px solid #ddd;"><?php esc_html_e('Queued and not yet processed.', 'wp-notifier'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- end .smack-tabs-content -->
</div><!-- end .smack-modern-wrapper -->
