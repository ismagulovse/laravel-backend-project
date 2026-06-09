<?php

declare(strict_types=1);

namespace App\Http\Requests\Policy;

use App\DTO\PermissionDTO;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_-]+$/', 'unique:permissions,slug'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Поле name обязательно.',
            'name.unique' => 'Разрешение с таким name уже существует.',
            'slug.required' => 'Поле slug обязательно.',
            'slug.unique' => 'Разрешение с таким slug уже существует.',
            'slug.regex' => 'Slug может содержать только латиницу в нижнем регистре, цифры, дефис и подчеркивание.',
        ];
    }

    public function toDTO(): PermissionDTO
    {
        return new PermissionDTO(
            id: 0,
            name: $this->validated('name'),
            slug: $this->validated('slug'),
            description: $this->validated('description'),
            createdAt: Carbon::now(),
            createdBy: $this->resolveActorId(),
            deletedAt: null,
            deletedBy: null,
        );
    }

    private function resolveActorId(): int
    {
        /** @var mixed $actor */
        $actor = $this->attributes->get('__auth_user');

        return (int) ($actor->id ?? 0);
    }
}
