<?php

namespace App\DTO;

class ServerInfoDTO
{
    public readonly string $php_version;
    public readonly string $server_software;
    public readonly string $memory_limit;
    public readonly string $max_execution_time;
    public readonly string $upload_max_filesize;
    public readonly string $post_max_size;
    public readonly array $loaded_extensions;

    public function __construct(
        string $php_version,
        string $server_software,
        string $memory_limit,
        string $max_execution_time,
        string $upload_max_filesize,
        string $post_max_size,
        array $loaded_extensions
    ) {
        $this->php_version         = $php_version;
        $this->server_software     = $server_software;
        $this->memory_limit        = $memory_limit;
        $this->max_execution_time  = $max_execution_time;
        $this->upload_max_filesize = $upload_max_filesize;
        $this->post_max_size       = $post_max_size;
        $this->loaded_extensions   = $loaded_extensions;
    }

    public function toArray(): array
    {
        return [
            'php_version'         => $this->php_version,
            'server_software'     => $this->server_software,
            'memory_limit'        => $this->memory_limit,
            'max_execution_time'  => $this->max_execution_time,
            'upload_max_filesize' => $this->upload_max_filesize,
            'post_max_size'       => $this->post_max_size,
            'loaded_extensions'   => $this->loaded_extensions,
        ];
    }
}