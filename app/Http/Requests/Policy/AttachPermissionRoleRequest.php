<?php

declare(strict_types=1);

namespace App\Http\Requests\Policy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachPermissionRoleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // формат, где role_id приходит в URL (/role/{role}/permission).
        if ($this->route('role') !== null && !$this->has('role_id')) {
            $this->merge(['role_id' => (int) $this->route('role')]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'permission_id' => [
                'required',
                'integer',
                'exists:permissions,id',
                // Не даём создать дубликат активной связи role-permission.
                Rule::unique('permission_role', 'permission_id')
                    ->where(fn ($query) => $query
                        ->where('role_id', (int) $this->input('role_id'))
                        ->whereNull('deleted_at')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role_id.required' => 'Поле role_id обязательно.',
            'role_id.exists' => 'Роль не найдена.',
            'permission_id.required' => 'Поле permission_id обязательно.',
            'permission_id.exists' => 'Разрешение не найдено.',
            'permission_id.unique' => 'Это разрешение уже связано с ролью.',
        ];
    }

    public function toDTO(): array
    {
        return [
            'role_id' => (int) $this->validated('role_id'),
            'permission_id' => (int) $this->validated('permission_id'),
        ];
    }
}
