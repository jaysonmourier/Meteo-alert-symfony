<?php

namespace App\Message;

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