<?php

declare(strict_types=1);

namespace App\Http\Requests\Policy;

use App\DTO\RoleDTO;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->resolveRoleId();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('roles', 'slug')->ignore($roleId),
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Поле name обязательно.',
            'name.unique' => 'Роль с таким name уже существует.',
            'slug.required' => 'Поле slug обязательно.',
            'slug.unique' => 'Роль с таким slug уже существует.',
            'slug.regex' => 'Slug может содержать только латиницу в нижнем регистре, цифры, дефис и подчеркивание.',
        ];
    }

    public function toDTO(): RoleDTO
    {
        // При обновлении id берём из параметра маршрута.
        return new RoleDTO(
            id: $this->resolveRoleId(),
            name: $this->validated('name'),
            slug: $this->validated('slug'),
            description: $this->validated('description'),
            createdAt: Carbon::now(),
            createdBy: $this->resolveActorId(),
            deletedAt: null,
            deletedBy: null,
        );
    }

    private function resolveRoleId(): int
    {
        $role = $this->route('role');

        if (is_object($role) && isset($role->id)) {
            return (int) $role->id;
        }

        return (int) $role;
    }

    private function resolveActorId(): int
    {
        $actor = $this->attributes->get('__auth_user');

        return (int) ($actor->id ?? 0);
    }
}
