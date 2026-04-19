<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DTO\LoginDTO;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [ 'username' => [ 'required','string','min:7','regex:/^[A-Z][a-zA-Z]*$/',], 
        'password' => ['required','string','min:8','regex:/[0-9]/','regex:/[^a-zA-Z0-9]/','regex:/[A-Z]/','regex:/[a-z]/',],];
    }

    public function messages(): array
    {
        return [
            'username.min'   => 'Имя пользователя, минимум 7 символов.',
            'username.regex' => 'Имя пользователя, только латиница, начинается с заглавной.',
            'password.min'   => 'Пароль, минимум 8 символов.',
            'password.regex' => 'Пароль должен содержать цифру, спецсимвол, заглавную и строчную букву.',
        ];
    }

    public function toDTO(): LoginDTO
    {
        return new LoginDTO(
            username: $this->validated('username'),
            password: $this->validated('password'),
        );
    }
}