<?php

namespace Smackcoders\WN;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WN_I18n - Handles internationalization (multi-language support).
 * Implements Issue #21: Implement Multi-Language Support
 */
class WN_I18n {

    /**
     * Register hooks. WordPress 4.6+ auto-loads translations for plugins
     * hosted under the correct slug, so no manual load_plugin_textdomain() call is needed.
     */
    public static function init(): void {
        // WordPress automatically loads translations for plugins after 4.6.
        // Manual load_plugin_textdomain() calls are no longer required.
    }
}
