# WP Notifier – WhatsApp, SMS & Email Order Notifications for WordPress

Automated WhatsApp, SMS, and email order notifications for WordPress and WooCommerce stores.

## Overview

WP Notifier is a WooCommerce WhatsApp notification plugin that keeps customers and store admins informed automatically, without manual follow-up. It closes a common gap in WordPress stores: default WooCommerce order emails are easy to miss, so a WooCommerce order notification plugin that also reaches shoppers over WhatsApp and SMS improves delivery and engagement. Built for e-commerce and subscription-based WordPress sites, it handles transactional messaging for order confirmations, shipping and status updates, subscription reminders, and invoice delivery, while giving admins real-time alerts for new orders and new customers. WP Notifier is designed for store owners, agencies, and developers who want one WordPress bulk notification plugin covering WhatsApp, SMS, and email instead of stitching together separate services.

## Key Features

- **WhatsApp notification dispatch** through Twilio, Gupshup, Infobip, and any REST-API-based WhatsApp Business provider
- **SMS notifications** with multi-provider support and fallback handling — a genuine SMS alerts WooCommerce plugin, not an email-only tool
- **Email notification** via SendGrid, Brevo, and Mailtrap for SMTP and API-based delivery
- **Deep WooCommerce integration** — hooks into order placement, order status changes, subscription events, and invoice generation
- **Message template customization** with placeholder tokens for dynamic content per event type
- **Subscription expiry scheduler** — automated reminders two days before expiry and on-the-day expiry notices
- **Bulk notification dispatcher** with a queued database table and automatic retry queue for high-volume stores
- **Audit logs admin screen** with event type and date range filtering, sortable columns, and status tracking (sent/failed)
- **Credential encryption** for stored provider API keys, plus a GDPR data exporter and GDPR data eraser
- **Phone number normalization** and e164 format validation before every send
- **Multi language readiness** — translation-ready with a full POT file and WPML/Polylang compatibility
- **PHPUnit test suite** covering core classes (bulk dispatcher, phone validator, logger, security)

## Use Cases

- WooCommerce store owners who want WordPress WhatsApp order notifications instead of relying on email alone
- Subscription-based sites that need a subscription reminder plugin for WooCommerce to automate renewal and expiry alerts
- Stores that want to reduce support tickets with proactive, automated order status alerts sent across WhatsApp, SMS, and email
- Admin teams that need instant new order and new customer alerts — a WooCommerce admin alert plugin experience with no manual monitoring
- Agencies managing multiple WooCommerce sites that need encrypted credential storage for safely keeping several provider API keys
- Businesses that must retain a searchable, GDPR-ready WooCommerce audit log of every notification sent

## Requirements

- **WordPress:** 6.0 or higher
- **PHP:** 7.4 or higher
- **WooCommerce** (required for order, subscription, and invoice notifications — core WordPress notifications such as new user registration work without it)
- An account with at least one supported provider: Twilio, Gupshup, or Infobip for WhatsApp/SMS, and/or SendGrid, Brevo, or Mailtrap for email

## Installation

### Install from WordPress

1. Download the plugin ZIP file.
2. Go to WordPress Admin → Plugins → Add New.
3. Upload, install, and activate the plugin.

### Manual Installation

1. Download or clone this repository.
2. Upload the `wp-notifier` folder to `/wp-content/plugins/`.
3. Activate the plugin from WordPress Admin → Plugins.

## Configuration / Setup

1. After activation, open **WP Notifier** in the WordPress admin menu.
2. Enter and validate your WhatsApp/SMS provider credentials (Twilio, Gupshup, or Infobip) and/or email provider credentials (SendGrid, Brevo, or Mailtrap). Credentials are encrypted before they are stored.
3. Toggle which notification types are active for customers and for admins (order confirmation, status changes, subscription reminders, invoices, new order/customer alerts).
4. Customize message templates per event type using the available placeholder tokens.
5. Save your settings — the scheduler and bulk dispatcher pick up subscription reminders and queued sends automatically via WP-Cron.

## Usage

Once configured, WP Notifier works in the background: WooCommerce events (new order, status change, subscription renewal, invoice generation) automatically trigger the matching WhatsApp, SMS, or email notification using your saved templates. Admins can review delivery in real time from the Audit Logs screen, filter by event type, date range, or status, and manually resend an invoice notification when needed. High-volume stores benefit from the bulk notification dispatcher, which queues sends and automatically retries failed attempts — helping keep customer communication consistent and reducing support load.

## Supported Integrations

- Twilio (WhatsApp and SMS)
- Gupshup (WhatsApp)
- Infobip (WhatsApp and SMS)
- SendGrid (email)
- Brevo (email)
- Mailtrap (email, useful for testing notifications before going live)
- WooCommerce (orders, subscriptions, invoices)
- WPML / Polylang (multi-language message templates)

## Screenshots / Demo

![WhatsApp Integration Overview](assets/images/wiwp.PNG)
![Gupshup Provider Configuration](assets/images/gupshup.PNG)
![Twilio Setup](assets/images/twilio.PNG)
![Infobip Setup](assets/images/infoo.PNG)

## Documentation

Full setup and usage documentation is available on the official plugin page: https://www.smackcoders.com/wp-notifier.html

## Frequently Asked Questions

### Does it support WhatsApp notifications?
Yes. WP Notifier sends WhatsApp notifications for order confirmations, status changes, subscription reminders, and invoices through Twilio, Gupshup, Infobip, or any REST-API-based provider.

### Which SMS providers are supported?
Twilio and Infobip are supported out of the box for SMS notifications, with multi-provider fallback handling to improve deliverability.

### Does it work with WooCommerce subscriptions?
Yes. The built-in scheduler sends a reminder two days before subscription expiry and an expiry notice on the day it lapses, so subscription expiry reminders go out automatically without manual tracking.

### Are API keys stored securely?
Yes. All provider API credentials are encrypted (AUTH_KEY-based encryption) before storage, and the plugin ships GDPR-ready data export and erasure tools.

### Can I customize the message templates?
Yes. Every notification type has an editable template with placeholder tokens, so you can match your brand's tone for order confirmations, shipping updates, and reminders.

### Does it support multiple languages?
Yes. WP Notifier is translation-ready with a full POT file included and is compatible with WPML and Polylang for multi-language message templates.

### Does this plugin require WooCommerce?
WooCommerce is required for order, subscription, and invoice notifications. Basic WordPress notifications (such as new user registration) work without it.

## Roadmap

Improvements under consideration include additional messaging provider integrations, expanded WooCommerce Subscriptions event coverage, and enhanced audit-log export options. These are potential directions, not commitments for a specific release.

## Changelog

### 1.0
- Initial release with WhatsApp, SMS, and email notification support.
- WooCommerce order, subscription, and invoice notifications.
- Bulk dispatcher with retry logic.
- Multi-language support (POT file included).
- Audit log page with filtering.
- GDPR-compliant data handling and encryption.
- PHPUnit test suite for core classes.

## Security

If you discover a security vulnerability in WP Notifier, please do not report it through a public GitHub issue. Instead, email info@smackcoders.com with details so it can be investigated and patched before any public disclosure.

## Contributing

Bug reports, feature suggestions, and pull requests are welcome. Please open a GitHub issue describing the bug or proposed change before submitting a pull request, and include steps to reproduce where relevant. Run the PHPUnit test suite (`composer test`) before submitting changes to core classes.

## Support

For help with configuration, provider setup, or troubleshooting, open an issue on this repository or contact Smackcoders through the official plugin page: https://www.smackcoders.com/wp-notifier.html

## License

Licensed under the GPL v2 (or later). See https://www.gnu.org/licenses/gpl-2.0.html for details.

Copyright (C) Smackcoders. All rights reserved under the Smackcoders Proprietary License as noted in the plugin source header. Unauthorized copying of the plugin files, via any medium, is strictly prohibited.

## Disclaimer

WhatsApp, Twilio, Gupshup, Infobip, SendGrid, Brevo, Mailtrap, and WooCommerce are trademarks of their respective owners. WP Notifier is an independent integration and is not officially affiliated with, endorsed by, or sponsored by any of these companies.

## Author / Maintainer

Developed and maintained by [Smackcoders](https://smackcoders.com/). Contact: info@smackcoders.com
