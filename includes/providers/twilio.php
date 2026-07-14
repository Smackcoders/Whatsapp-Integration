<?php

namespace Smackcoders\WN\Providers;

if (!defined('ABSPATH')) {
    exit;
}

class TwilioProvider {

    private $sid;
    private $token;
    private $from;

    public function __construct($sid, $token, $from) {
        $this->sid = $sid;
        $this->token = $token;
        $this->from = $from;
    }

    public function sendMessage(string $to, string $message, bool $is_whatsapp = true): array {
        try {
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json";
    
            // Normalize by stripping any pre-existing "whatsapp:" prefix to prevent double prefixing
            $to_clean = preg_replace('/^whatsapp:/i', '', trim($to));
            $from_clean = preg_replace('/^whatsapp:/i', '', trim($this->from));

            $clean_to = (strpos($to_clean, '+') === 0) ? $to_clean : '+' . $to_clean;
            $clean_from = (strpos($from_clean, '+') === 0) ? $from_clean : '+' . $from_clean;

            $data = [
                'To'   => $is_whatsapp ? "whatsapp:$clean_to" : $clean_to,
                'From' => $is_whatsapp ? "whatsapp:$clean_from" : $clean_from,
                'Body' => $message,
            ];

            // error_log("Twilio Sending Data: " . print_r($data, true));
    
            $response = wp_remote_post($url, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode("{$this->sid}:{$this->token}"),
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ],
                'body'      => http_build_query($data),
                'timeout'   => 30,
                'sslverify' => false,
            ]);
    
            if (is_wp_error($response)) {
                // error_log("Twilio WP Error: " . $response->get_error_message());
                return [
                    'success' => false,
                    'error'   => $response->get_error_message(),
                ];
            }
    
            $http_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            // error_log("Twilio API Response: " . $body);
            $json_body = json_decode($body, true);
    
            if ($http_code === 201) {
                return [
                    'success' => true,
                    'message' => 'Message sent successfully',
                ];
            } else {
                return [
                    'success' => false,
                    'error'   => isset($json_body['message']) ? $json_body['message'] : 'Twilio API error (' . $http_code . ')',
                ];
            }
    
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }
}    