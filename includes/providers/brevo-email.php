<?php

namespace Smackcoders\WN\Providers;

if (!defined('ABSPATH')) {
    exit;
}

class BrevoEmailProvider {
    private $api_key;
    private $from_email;
    private $from_name;

    public function __construct($api_key, $from_email, $from_name = 'WP Notifier') {
        $this->api_key = $api_key;
        $this->from_email = $from_email;
        $this->from_name = $from_name;
    }

    public function sendEmail($to, $subject, $message) {
        $url = 'https://api.brevo.com/v3/smtp/email';
        
        $body = [
            'sender' => [
                'name' => $this->from_name,
                'email' => $this->from_email
            ],
            'to' => [
                [
                    'email' => $to
                ]
            ],
            'subject' => $subject,
            'textContent' => $message
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'api-key' => $this->api_key,
                'Content-Type' => 'application/json',
                'accept' => 'application/json'
            ],
            'body' => wp_json_encode($body),
            'timeout' => 30,
            'sslverify' => false,
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 200 && $code < 300) {
            return ['success' => true];
        }

        $response_body = wp_remote_retrieve_body($response);
        $decoded = json_decode($response_body, true);
        return ['success' => false, 'error' => $decoded['message'] ?? $response_body];
    }
}
