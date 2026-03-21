<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DTO\RegisterDTO;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;


class RegisterRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => [ 'required','string', 'min:7', 'regex:/^[A-Z][a-zA-Z]*$/'],
            'email' => ['required','email','unique:users,email',],
            'password' => [ 'required', 'string', 'min:8', 'regex:/[0-9]/', 'regex:/[^a-zA-Z0-9]/', 'regex:/[A-Z]/', 'regex:/[a-z]/',],
            'c_password' => ['required','string','same:password',],
            'birthday' => ['required', 'date_format:Y-m-d',],
        ];
    }

   
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {

            $birthday = $this->input('birthday');

            if ($birthday !== null) {

               
                $date = Carbon::parse($birthday);

                $datePlus14 = $date->copy()->addYears(14);

                if ($datePlus14->isAfter(now())) {

                    $v->errors()->add(
                        'birthday',
                        'Вам должно быть не менее 14 лет для регистрации.'
                    );
                }
            }

            $username = strtolower($this->input('username', ''));

            $userExists = \App\Models\User::whereRaw(
                'LOWER(username) = ?',
                [$username]
            )->exists();

            if ($userExists) {
                
                $v->errors()->add(
                    'username',
                    'Это имя пользователя уже занято.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'username.min'         => 'Имя пользователя минимум 7 символов.',
            'username.regex'       => 'Имя пользователя только латиница, начинается с заглавной.',
            'username.unique'      => 'Это имя пользователя уже занято.',
            'email.unique'         => 'Этот email уже используется.',
            'password.min'         => 'Пароль минимум 8 символов.',
            'password.regex'       => 'Пароль должен содержать цифру, спецсимвол, заглавную и строчную букву.',
            'c_password.same'      => 'Пароли не совпадают.',
            'birthday.date_format' => 'Дата рождения должна быть в формате YYYY-MM-DD.',
        ];
    }

    public function toDTO(): RegisterDTO
    {
        return new RegisterDTO(
            username: $this->validated('username'),
            email:    $this->validated('email'),
            password: $this->validated('password'),
            birthday: Carbon::parse($this->validated('birthday')),
        );
    }
}