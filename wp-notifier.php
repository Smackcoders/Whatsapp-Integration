<?php

/**
 * Plugin Name:       WP Notifier
 * Plugin URI:        https://www.smackcoders.com/wp-notifier.html
 * Description:       Easily send automated WhatsApp notifications to customers and admins 
 *                    for events like orders, signups, and status updates—boosting engagement
 *                    and communication in your WordPress site.
 * Version:           1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Smackcoders
 * Author URI:        https://smackcoders.com/
 * Text Domain:       wp-notifier
 * License:           GPL v2 or later
 * 
 * Copyright (C) Smackcoders. - All Rights Reserved under Smackcoders Proprietary License
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * You can contact Smackcoders at email address info@smackcoders.com.
 */

namespace Smackcoders\WN;

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants

define('WP_NOTIFIER_PLUGIN_VERSION', '1.0.0');

define('WP_NOTIFIER_PLUGIN_NAME', 'WP Notifier'); 

define('WP_NOTIFIER_PLUGIN_SLUG', 'wp-notifier');    

define('WP_NOTIFIER_PLUGIN_FILE', __FILE__);

define('WP_NOTIFIER_PLUGIN_DIR', plugin_dir_path(__FILE__));

define('WP_NOTIFIER_PLUGIN_URL', plugin_dir_url(__FILE__));

define('WP_NOTIFIER_PLUGIN_ASSETS_URL', WP_NOTIFIER_PLUGIN_URL . 'assets/');

///////////////////////////////////////////////////////////////////////////////////

require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/bootstrap.php';

// Plugin Activation, Deactivation, Uninstall Modules
require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/installation/install.php';

require_once WP_NOTIFIER_PLUGIN_DIR . 'includes/installation/uninstall.php';

///////////////////////////////////////////////////////////////////////////////////

register_activation_hook(WP_NOTIFIER_PLUGIN_FILE, ['Smackcoders\WN\WhatsAppPluginInstaller', 'install']);

register_uninstall_hook(WP_NOTIFIER_PLUGIN_FILE, ['SmackCoders\WN\WhatsAppPluginUninstaller', 'uninstall']);