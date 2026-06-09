<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\Carbon;

final class UserDTO
{
    /**
     * @param RoleDTO[] $roles Массив ролей пользователя для RBAC.
     */
    public function __construct(
        public readonly int    $id,
        public readonly string $username,
        public readonly string $email,
        public readonly Carbon $birthday,
        public readonly array  $roles = [],
    ) {}

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'username' => $this->username,
            'email'    => $this->email,
            'birthday' => $this->birthday->format('Y-m-d'),
            // Для auth-ответа возвращаем одну основную роль пользователя.
            'roles'    => $this->roles[0]->name ?? null,
        ];
    }
}
