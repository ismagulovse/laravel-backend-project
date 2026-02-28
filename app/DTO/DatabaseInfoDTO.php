<?php

namespace App\DTO;

class DatabaseInfoDTO
{
    public readonly string $driver;
    public readonly string $database;
    public readonly string $version;

    public function __construct(string $driver, string $database, string $version)
    {
        $this->driver   = $driver;
        $this->database = $database;
        $this->version  = $version;
    }

    public function toArray(): array
    {
        return [
            'driver'   => $this->driver,
            'database' => $this->database,
            'version'  => $this->version,
        ];
    }
}