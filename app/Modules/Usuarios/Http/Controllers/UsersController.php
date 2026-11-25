<?php

namespace App\Modules\Usuarios\Http\Controllers;

use App\Modules\Usuarios\Domain\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsersController 
{
    private const PAGE_SIZE = 20;
    private const MAX_PAGE_SIZE = 100;

    // GET /api/users
    public function list(Request $request): JsonResponse
    {
        $pageSize = min($request->integer('page_size', self::PAGE_SIZE), self::MAX_PAGE_SIZE);

        return response()->json(User::paginate($pageSize));
    }

    // GET /api/users/{user}
    public function show(User $user): JsonResponse
    {
        return response()->json($user);
    }

    // PUT /api/users/{user}
    public function update(Request $request, User $user): JsonResponse
    {
        $this->somenteDono($request, $user);

        $dados = $request->validate([
            'nome'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'tipo'  => ['required', Rule::in(['recrutador', 'candidato'])],
        ]);

        $user->update($dados);

        return response()->json($user);
    }

    // DELETE /api/users/{user}
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->somenteDono($request, $user);

        $user->delete();

        return response()->json(null, 204);
    }

    // Só o próprio usuário altera ou remove sua conta.
    private function somenteDono(Request $request, User $user): void
    {
        abort_if($request->user()->id !== $user->id, 403, 'Sem permissão.');
    }
}
