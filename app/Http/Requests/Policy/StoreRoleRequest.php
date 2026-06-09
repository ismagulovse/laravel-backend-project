<?php

declare(strict_types=1);

namespace App\Http\Requests\Policy;

use App\DTO\RoleDTO;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // На этом этапе пускаем всех авторизованных; проверка прав будет через middleware/Gate.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_-]+$/', 'unique:roles,slug'],
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
        // DTO нужен как единый формат данных из Request в контроллер/сервис.
        return new RoleDTO(
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
