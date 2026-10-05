<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/*
| Validação do formulário de login: só confere se usuário e senha vieram.
| Se a senha está CERTA quem confere é o AuthController.
*/
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Informe o usuário.',
            'password.required' => 'Informe a senha.',
        ];
    }
}
