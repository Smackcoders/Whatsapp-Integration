<?php

namespace Smackcoders\WN;

if (!defined('ABSPATH')) {
    exit;
}

class WN_Security
{

    /**
     * Secret key for encryption
     */
    private static function get_key()
    {
        if (defined('SECURE_AUTH_KEY')) {
            return SECURE_AUTH_KEY;
        }
        return 'WN_DEFAULT_SECRET_KEY_CHANGE_ME';
    }

    /**
     * Encrypt a string
     */
    public static function encrypt($data)
    {
        if (empty($data))
            return $data;

        $key = self::get_key();
        $method = 'aes-256-cbc';
        $iv_length = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($iv_length);

        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);

        // Return Prefix + IV + Encrypted data
        return 'WN::' . base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a string
     */
    public static function decrypt($data)
    {
        if (empty($data))
            return $data;

        // Remove Prefix
        if (strpos($data, 'WN::') === 0) {
            $data = substr($data, 4);
        }

        $data = base64_decode($data);
        $key = self::get_key();
        $method = 'aes-256-cbc';
        $iv_length = openssl_cipher_iv_length($method);

        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);

        return openssl_decrypt($encrypted, $method, $key, 0, $iv);
    }

    /**
     * Encrypt data only if it is a sensitive key
     */
    public static function encrypt_if_sensitive($key, $value)
    {
        if (empty($value))
            return $value;
        if (self::is_sensitive($key)) {
            return self::encrypt($value);
        }
        return $value;
    }

    /**
     * Decrypt data only if it is a sensitive key and appears to be encrypted
     */
    public static function decrypt_if_sensitive($key, $value)
    {
        if (empty($value))
            return $value;
        if (self::is_sensitive($key)) {
            if (strpos($value, 'WN::') === 0) {
                return self::decrypt($value);
            }
        }
        return $value;
    }

    /**
     * Check if a setting key should be encrypted
     */
    public static function is_sensitive($key)
    {
        $sensitive_keys = [
            'twilio_sid',
            'twilio_token',
            'infobip_api_key',
            'gupshup_api_key',
            'cloud_access_token',
            'sendgrid_api_key',
            'infobip_email_api_key',
            'gupshup_email_api_key'
        ];
        return in_array($key, $sensitive_keys);
    }
}
