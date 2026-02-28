<?php

namespace App\DTO;

class ClientInfoDTO
{
    public readonly string $ip;
    public readonly string $user_agent;

    public function __construct(string $ip, string $user_agent)
    {
        $this->ip         = $ip;
        $this->user_agent = $user_agent;
    }

    public function toArray(): array
    {
        return [
            'ip'         => $this->ip,
            'user_agent' => $this->user_agent,
        ];
    }
}