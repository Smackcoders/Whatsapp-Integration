<?php

namespace Smackcoders\WN\Providers;

if (!defined('ABSPATH')) {
    exit;
}

class GupshupProvider {

    private static $instance = null;

    private $apiKey;
    private $sender;
    private $botName;

    public function __construct(string $apiKey, string $sender, string $botName) {
        $this->apiKey = $apiKey;
        $this->sender = $sender;
        $this->botName = $botName;
    }

    public static function getInstance(string $apiKey, string $sender, string $botName): self {
        if (self::$instance === null) {
            self::$instance = new self($apiKey, $sender, $botName);
        }
        return self::$instance;
    }

    public function sendMessage(string $to, string $message): array {
        try {
            if (!$this->apiKey || !$this->sender || !$this->botName) {
                return [
                    'success' => false,
                    'error'   => 'Missing credentials or bot name',
                ];
            }
            $to = ltrim($to, '+');
            $sender = ltrim($this->sender, '+');
            
            // error_log("WN Debug: Gupshup Sending from [$sender] to [$to] using app [{$this->botName}]");

            $url = "https://api.gupshup.io/wa/api/v1/msg";

            $data = [
                'channel'     => 'whatsapp',
                'source'      => $sender, // Gupshup modern API uses 'source'
                'sender'      => $sender, // Fallback for some app types
                'destination' => $to,
                'message'     => wp_json_encode(['type' => 'text', 'text' => $message]),
                'src.name'    => $this->botName
            ];

            $args = [
                'body'    => http_build_query($data),
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'apikey'       => $this->apiKey,
                ],
                'timeout'   => 30,
                'sslverify' => false,
            ];

            $response = wp_remote_post($url, $args);

            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'error'   => $response->get_error_message(),
                ];
            }

            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $json = json_decode($body, true);

            if ($status_code >= 200 && $status_code < 300) {
                return [
                    'success'  => true,
                    'message'  => 'Message sent successfully',
                    'response' => $json,
                ];
            }

            return [
                'success' => false,
                'error'   => $json['message'] ?? 'Unexpected Gupshup API error',
                'status'  => $status_code,
                'body'    => $body,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }
}
