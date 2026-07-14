<?php

namespace Smackcoders\WN;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WN_PhoneValidator - Phone number validation and formatting utilities.
 * Implements Issue #8: Implement Customer Data Sync
 */
class WN_PhoneValidator {

    /**
     * Default country code when none is found (India)
     */
    const DEFAULT_COUNTRY_CODE = '91';

    /**
     * Validate and normalize a phone number to E.164 format (digits only, with country code).
     *
     * @param string $phone Raw phone number input
     * @param string $country_code  ISO two-letter country code or numeric prefix (e.g. "IN" or "91")
     * @return array{valid: bool, normalized: string, error: string}
     */
    public static function validate_and_normalize( string $phone, string $country_code = '' ): array {
        // Strip all non-numeric characters except leading +
        $stripped = preg_replace( '/[^\d+]/', '', trim( $phone ) );

        if ( empty( $stripped ) ) {
            return [
                'valid'      => false,
                'normalized' => '',
                'error'      => __( 'Phone number is empty.', 'wp-notifier' ),
            ];
        }

        // Remove leading + and leading 0 for processing
        $digits = ltrim( $stripped, '+' );
        $digits = ltrim( $digits, '0' );

        // If number looks like a local 10-digit number, prepend country code
        if ( strlen( $digits ) === 10 ) {
            $prefix = '';
            if ( ! empty( $country_code ) ) {
                $prefix = ltrim( $country_code, '+' );
                if ( strlen( $prefix ) === 2 && class_exists( 'WooCommerce' ) && isset( WC()->countries ) ) {
                    $prefix = ltrim( (string)WC()->countries->get_country_calling_code( $prefix ), '+' );
                }
            }
            if ( empty( $prefix ) ) {
                $prefix = self::DEFAULT_COUNTRY_CODE;
            }
            $digits = $prefix . $digits;
        }

        // Basic length check: E.164 requires 7-15 digits
        if ( strlen( $digits ) < 7 || strlen( $digits ) > 15 ) {
            return [
                'valid'      => false,
                'normalized' => '',
                'error'      => sprintf(
                    /* translators: %s: the phone number entered */
                    __( 'Invalid phone number length for: %s', 'wp-notifier' ),
                    esc_html( $phone )
                ),
            ];
        }

        // Ensure all remaining characters are digits
        if ( ! ctype_digit( $digits ) ) {
            return [
                'valid'      => false,
                'normalized' => '',
                'error'      => sprintf(
                    /* translators: %s: the phone number entered */
                    __( 'Phone number contains invalid characters: %s', 'wp-notifier' ),
                    esc_html( $phone )
                ),
            ];
        }

        return [
            'valid'      => true,
            'normalized' => $digits,
            'error'      => '',
        ];
    }

    /**
     * Sync a user's billing phone to the plugin's preferred format,
     * storing validated number in user meta.
     *
     * @param int $user_id WordPress user ID
     * @return bool True if phone was updated/valid, false otherwise
     */
    public static function sync_user_phone( int $user_id ): bool {
        $raw_phone = get_user_meta( $user_id, 'billing_phone', true );
        $billing_country = get_user_meta( $user_id, 'billing_country', true );

        if ( empty( $raw_phone ) ) {
            return false;
        }

        $result = self::validate_and_normalize( $raw_phone, (string)$billing_country );

        if ( ! $result['valid'] ) {
            // Store validation error in user meta for admin review
            update_user_meta( $user_id, '_wn_phone_error', $result['error'] );
            return false;
        }

        // Store normalized phone
        update_user_meta( $user_id, '_wn_normalized_phone', $result['normalized'] );
        delete_user_meta( $user_id, '_wn_phone_error' );

        return true;
    }

    /**
     * Get the best available phone number for a user (normalized or fallback to billing_phone)
     *
     * @param int $user_id WordPress user ID
     * @return string
     */
    public static function get_user_phone( int $user_id ): string {
        $normalized = get_user_meta( $user_id, '_wn_normalized_phone', true );
        if ( ! empty( $normalized ) ) {
            return $normalized;
        }
        // Fallback: normalize on-the-fly
        $raw = get_user_meta( $user_id, 'billing_phone', true );
        $billing_country = get_user_meta( $user_id, 'billing_country', true );
        if ( empty( $raw ) ) {
            return '';
        }
        $result = self::validate_and_normalize( $raw, (string)$billing_country );
        return $result['valid'] ? $result['normalized'] : '';
    }

    /**
     * Validate phone number on user profile update (hook)
     *
     * @param int $user_id User ID
     */
    public static function on_profile_update( int $user_id ): void {
        self::sync_user_phone( $user_id );
    }

    /**
     * Register hooks
     */
    public static function init(): void {
        add_action( 'profile_update', [ __CLASS__, 'on_profile_update' ], 5, 1 );
        add_action( 'user_register', [ __CLASS__, 'on_profile_update' ], 5, 1 );
        add_action( 'woocommerce_checkout_update_order_meta', [ __CLASS__, 'sync_from_order' ], 10, 2 );
    }

    /**
     * Sync phone from WooCommerce order
     *
     * @param int   $order_id WooCommerce order ID
     * @param array $posted   Posted checkout data
     */
    public static function sync_from_order( int $order_id, array $posted ): void {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }
        $user_id = $order->get_user_id();
        if ( $user_id ) {
            self::sync_user_phone( $user_id );
        }
    }
}
