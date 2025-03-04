<?php

namespace App\Message;

/**
 * Représente une notification par SMS 
 */
class SmsNotification {
    public function __construct(
        private string $to,
        private string $content
    ) {}

    public function getTo(): string
    {
        return $this->to;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}