<?php

declare(strict_types=1);

namespace App\Http\Requests\Policy;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachUserRoleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        //  формат, где user_id приходит в URL (/user/{user}/role).
        if ($this->route('user') !== null && !$this->has('user_id')) {
            $this->merge(['user_id' => (int) $this->route('user')]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role_id' => [
                'required',
                'integer',
                'exists:roles,id',
                // Не даём создать дубликат активной связи user-role.
                Rule::unique('role_user', 'role_id')
                    ->where(fn ($query) => $query
                        ->where('user_id', (int) $this->input('user_id'))
                        ->whereNull('deleted_at')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Поле user_id обязательно.',
            'user_id.exists' => 'Пользователь не найден.',
            'role_id.required' => 'Поле role_id обязательно.',
            'role_id.exists' => 'Роль не найдена.',
            'role_id.unique' => 'Эта роль уже назначена пользователю.',
        ];
    }

    public function toDTO(): array
    {
        return [
            'user_id' => (int) $this->validated('user_id'),
            'role_id' => (int) $this->validated('role_id'),
        ];
    }
}
