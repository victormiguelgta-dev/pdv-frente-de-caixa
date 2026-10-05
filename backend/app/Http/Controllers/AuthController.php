<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/*
| AuthController = login, "quem sou eu" e logout.
|
| Como funciona o login por token (Laravel Sanctum):
|   1. O frontend manda usuário e senha para POST /api/login.
|   2. Se estiverem certos, o backend cria um TOKEN (uma chave longa e
|      aleatória) e devolve para o frontend.
|   3. Em toda requisição seguinte, o frontend envia esse token no cabeçalho
|      "Authorization: Bearer <token>". É assim que o backend sabe quem é.
|   4. No logout, o token é apagado do banco e deixa de valer.
|
| No banco fica só um "hash" do token (como a senha), não o token em si.
*/
class AuthController extends Controller
{
    /*
    | POST /api/login
    */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('username', $request->validated('username'))->first();

        /*
        | Hash::check compara a senha digitada com a senha criptografada do
        | banco. A mensagem de erro é a MESMA para "usuário não existe" e "senha
        | errada": assim quem tenta invadir não descobre quais usuários existem.
        */
        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'username' => 'Usuário ou senha inválidos.',
            ]);
        }

        // O nome do token ajuda a identificar de onde veio (útil em auditoria).
        $token = $user->createToken('pdv')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userData($user),
        ]);
    }

    /*
    | GET /api/me
    | Devolve o usuário dono do token. O frontend usa ao abrir o sistema, para
    | saber se o login guardado ainda vale.
    */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userData($request->user())]);
    }

    /*
    | POST /api/logout
    | Apaga o token usado nesta requisição: ele deixa de funcionar na hora.
    */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sessão encerrada.']);
    }

    // Só os dados do usuário que o frontend precisa (nada de senha, e-mail etc.).
    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'role' => $user->role->value,
            'role_label' => $user->role->label(),
        ];
    }
}
