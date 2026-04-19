<?php

declare(strict_types=1);

namespace App\DTO;

final class TokenListDTO
{

    public function __construct(public readonly array $tokens,) 
    {

    }
 
    public function toArray(): array
    {
        return ['tokens' => $this->tokens];
    }
}