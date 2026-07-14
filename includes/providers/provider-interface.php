<?php

namespace Smackcoders\WN;

if (!defined('ABSPATH')) {
    exit;
}

interface ProviderInterface {

    public function sendMessage(string $to, string $message): array;
    
}