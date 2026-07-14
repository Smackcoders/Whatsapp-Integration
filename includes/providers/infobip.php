<?php

namespace Smackcoders\WN\Providers;

if (!defined('ABSPATH')) {
    exit;
}

class InfobipProvider  {

    private $baseUrl;
    private $apiKey;
    private $infobipSender;

    public function __construct($baseUrl, $apiKey, $infobipSender) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->infobipSender = $infobipSender;
    }

    public function sendMessage(string $to, string $message): array {
        if (!$this->apiKey || !$this->baseUrl || !$this->infobipSender) {
            return [
                'success' => false,
                'error'   => 'Missing Infobip credentials'
            ];
        }

        $url = $this->baseUrl . '/whatsapp/1/message/text';

        $payload = [
            'from'    => $this->infobipSender,
            'to'      => $to,
            'content' => ['text' => $message]
        ];

        $headers = [
            'Authorization' => 'App ' . $this->apiKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json'
        ];

        $response = wp_remote_post($url, [
            'method'    => 'POST',
            'body'      => json_encode($payload),
            'headers'   => $headers,
            'timeout'   => 15
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error'   => $response->get_error_message()
            ];
        }

        $http_code = wp_remote_retrieve_response_code($response);
        if (in_array($http_code, [200, 201])) {
            return [
                'success' => true,
                'message' => 'Message sent successfully'
            ];
        } else {
            return [
                'success' => false,
                'error'   => 'HTTP ' . $http_code
            ];
        }
    }
}
