=== WP Notifier ===
Contributors: smackcoders
Tags: whatsapp, notifications, woocommerce, sms, email
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send automated WhatsApp, SMS, and email notifications to customers and admins for WooCommerce orders, subscriptions, and more.

== Description ==

WP Notifier integrates WhatsApp, SMS, and email notifications into WordPress and WooCommerce, allowing you to automatically notify customers and administrators for key events such as order confirmations, shipping updates, subscription reminders, and invoice delivery.

= Key Features =

* **WhatsApp Notifications** — Send messages via Gupshup, Twilio, Infobip, and other providers.
* **SMS Notifications** — Multi-provider SMS support with fallback handling.
* **Email Notifications** — SMTP and API-based email delivery via SendGrid, Brevo, Mailtrap.
* **WooCommerce Integration** — Hooks into order placement, status changes, subscription events, and invoice generation.
* **Customizable Templates** — Configure message templates per event type with dynamic placeholders.
* **Notification Scheduler** — Automated reminders 2 days before subscription expiry and on expiry.
* **Bulk Dispatcher** — Queued batch processing with retry logic for high-volume stores.
* **Audit Logs** — Detailed log of all sent notifications with filtering by event type, date, and status.
* **GDPR Compliance** — Data encryption for API credentials; privacy-ready data handling.
* **Multi-Language Ready** — Translation-ready with a full POT file included.

== Installation ==

1. Upload the `wp-notifier` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **WP Notifier** in the WordPress admin menu.
4. Configure your WhatsApp, SMS, or email provider credentials.
5. Enable the notification types you want and customize message templates.

== Frequently Asked Questions ==

= Which WhatsApp providers are supported? =

Gupshup, Twilio, Infobip, and any provider with a REST API can be configured.

= Does this plugin require WooCommerce? =

WooCommerce is required for order and subscription notifications. Basic WordPress notifications (user registration, etc.) work without WooCommerce.

= Is customer data secure? =

Yes. All API credentials are encrypted using `AUTH_KEY`-based encryption before storage. The plugin is GDPR-compliant.

== Changelog ==

= 1.0 =
* Initial release with WhatsApp, SMS, and email notification support.
* WooCommerce order, subscription, and invoice notifications.
* Bulk dispatcher with retry logic.
* Multi-language support (POT file included).
* Audit log page with filtering.
* GDPR-compliant data handling and encryption.
* PHPUnit test suite for core classes.

== Upgrade Notice ==

= 1.0 =
Initial release.
