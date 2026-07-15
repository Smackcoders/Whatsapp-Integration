# WP Notifier – WhatsApp, SMS & Email Order Notifications for WordPress

> Automated WhatsApp, SMS, and email order notifications for WordPress and WooCommerce stores.

![License](https://img.shields.io/badge/license-GPLv2%2B-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Use Cases](#use-cases)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Supported Integrations](#supported-integrations)
- [Screenshots](#screenshots)
- [Documentation](#documentation)
- [FAQ](#faq)
- [Roadmap](#roadmap)
- [Changelog](#changelog)
- [Security](#security)
- [Contributing](#contributing)
- [Support](#support)
- [License](#license)
- [Disclaimer](#disclaimer)
- [Author](#author)

## Overview

WP Notifier turns WooCommerce order events into WhatsApp, SMS, and email notifications without any manual follow-up from the store. It closes a common gap in WordPress stores: default WooCommerce order emails get lost in crowded inboxes, so a WooCommerce order notification plugin that also reaches shoppers on WhatsApp and SMS improves delivery and engagement. The plugin is built for e-commerce and subscription-based WordPress sites. It handles order confirmations, shipping and status updates, subscription renewal reminders, and invoice delivery, and it gives store admins real-time alerts for new orders and new customer signups. For store owners, agencies, and developers who don't want to stitch together three separate services, WP Notifier is one WordPress bulk notification plugin that covers WhatsApp, SMS, and email from a single settings screen.

## Key Features

- **WhatsApp notification dispatch** through Twilio, Gupshup, and Infobip
- **SMS notifications** through Twilio, sent alongside WhatsApp and email for the same order and subscription events — a real SMS alerts WooCommerce plugin, not an email-only add-on
- **Email notification** via SendGrid, Brevo, and Mailtrap for SMTP and API-based delivery
- **Deep WooCommerce integration** — hooks into order placement, order status changes, subscription events, and invoice generation
- **Message template customization** with placeholder tokens for dynamic content per event type
- **Subscription expiry scheduler** — automated reminders two days before expiry and on-the-day expiry notices
- **Bulk notification dispatcher** with a queued database table and automatic retry queue for high-volume stores
- **Audit logs admin screen** with event type and date range filtering, sortable columns, and status tracking (sent/failed)
- **Credential encryption** for WhatsApp and email provider API keys, plus built-in GDPR data export and erasure tools
- **Phone number normalization** to E.164-style digits, with the country code detected automatically from WooCommerce billing data
- **Translation-ready** with a full POT file, so the interface and message templates can be localized through standard WordPress translation workflows
- **PHPUnit test suite** covering core classes (bulk dispatcher, phone validator, logger, security)

## Use Cases

- WooCommerce store owners who want WordPress WhatsApp order notifications instead of relying on email alone
- Subscription-based sites that need a subscription reminder plugin for WooCommerce to automate renewal and expiry alerts
- Stores that want to reduce support tickets with proactive, automated order status alerts sent across WhatsApp, SMS, and email
- Admin teams that want instant new order and new customer alerts — a WooCommerce admin alert plugin that doesn't require watching the dashboard
- Agencies managing multiple WooCommerce sites that need to keep several providers' credentials safely stored per client
- Businesses that must retain a searchable, GDPR-ready WooCommerce audit log of every notification sent

## Requirements

| Requirement | Version |
| --- | --- |
| WordPress | 6.0 or higher |
| PHP | 7.4 or higher |
| WooCommerce | Required for order, subscription, and invoice notifications (core WordPress notifications such as new user registration work without it) |

You'll also need an account with at least one supported provider: Twilio, Gupshup, or Infobip for WhatsApp; Twilio for SMS; and/or SendGrid, Brevo, or Mailtrap for email.

## Installation

### Install from WordPress

1. Download the plugin ZIP file.
2. Go to WordPress Admin → Plugins → Add New.
3. Upload, install, and activate the plugin.

### Manual Installation

1. Download or clone this repository.
2. Upload the `wp-notifier` folder to `/wp-content/plugins/`.
3. Activate the plugin from WordPress Admin → Plugins.

## Configuration

1. After activation, open **WP Notifier** in the WordPress admin menu.
2. Enter and validate your WhatsApp/SMS provider credentials (Twilio, Gupshup, or Infobip for WhatsApp; Twilio for SMS) and/or email provider credentials (SendGrid, Brevo, or Mailtrap). Sensitive credentials are encrypted before they're stored, using a key derived from your site's `SECURE_AUTH_KEY`.
3. Toggle which notification types are active for customers and for admins (order confirmation, status changes, subscription reminders, invoices, new order/customer alerts).
4. Customize message templates per event type using the available placeholder tokens.
5. Save your settings — the scheduler and bulk dispatcher pick up subscription reminders and queued sends automatically via WP-Cron.

## Usage

Once configured, WP Notifier works in the background. WooCommerce events — new order, status change, subscription renewal, invoice generation — trigger the matching WhatsApp, SMS, or email notification using your saved templates. Admins can review delivery in real time from the Audit Logs screen, filtering by event type, date range, or status. Invoice notifications also refire automatically whenever an admin uses WooCommerce's native "resend order details" action, so a shopper who lost an invoice email gets it again on WhatsApp or SMS too. High-volume stores benefit from the bulk notification dispatcher, which queues sends and retries failed attempts automatically.

## Supported Integrations

- Twilio (WhatsApp and SMS)
- Gupshup (WhatsApp)
- Infobip (WhatsApp)
- SendGrid (email)
- Brevo (email)
- Mailtrap (email, useful for testing notifications before going live)
- WooCommerce (orders, subscriptions, invoices)

## Screenshots

![WhatsApp Integration Overview](assets/images/wiwp.PNG)
![Gupshup Provider Configuration](assets/images/gupshup.PNG)
![Twilio Setup](assets/images/twilio.PNG)
![Infobip Setup](assets/images/infoo.PNG)

## Documentation

Full setup and usage documentation is available on the official plugin page: https://www.smackcoders.com/wp-notifier.html

## FAQ

### Does it support WhatsApp notifications?

Yes. WP Notifier sends WhatsApp notifications for order confirmations, status changes, subscription reminders, and invoices through Twilio, Gupshup, or Infobip.

### Which SMS providers are supported?

Twilio is supported for SMS notifications today, sent alongside or instead of WhatsApp for the same events.

### Does it work with WooCommerce subscriptions?

Yes. The built-in scheduler sends a reminder two days before subscription expiry and an expiry notice on the day it lapses, so renewal reminders go out automatically without manual tracking.

### Are API keys stored securely?

Sensitive provider credentials are encrypted before they're stored, using a key derived from your site's `SECURE_AUTH_KEY` constant, and the plugin ships built-in GDPR data export and erasure tools.

### Can I customize the message templates?

Yes. Every notification type has an editable template with placeholder tokens, so you can match your brand's tone for order confirmations, shipping updates, and reminders.

### Does it support multiple languages?

WP Notifier is translation-ready with a full POT file, so it can be localized through standard WordPress translation workflows.

### Does this plugin require WooCommerce?

WooCommerce is required for order, subscription, and invoice notifications. Basic WordPress notifications, such as new user registration, work without it.

## Roadmap

Improvements under consideration include additional messaging provider integrations, expanded WooCommerce Subscriptions event coverage, and enhanced audit-log export options. These are potential directions, not commitments for a specific release.

## Changelog

### 1.0.0

- Initial release with WhatsApp, SMS, and email notification support.
- WooCommerce order, subscription, and invoice notifications.
- Bulk dispatcher with retry logic.
- Multi-language readiness (POT file included).
- Audit log page with filtering.
- GDPR-ready data export, erasure, and credential encryption.
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

## Author

Developed and maintained by [Smackcoders](https://smackcoders.com/). Contact: info@smackcoders.com
