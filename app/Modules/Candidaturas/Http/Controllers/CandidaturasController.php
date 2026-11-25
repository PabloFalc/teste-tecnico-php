<?php

namespace App\Modules\Candidaturas\Http\Controllers;

use App\Modules\Vagas\Domain\Models\Vaga;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CandidaturasController
{
    private const PAGE_SIZE = 20;

    private const MAX_PAGE_SIZE = 100;

    // GET /api/candidaturas  (vagas em que o candidato logado se candidatou)
    public function list(Request $request): JsonResponse
    {
        $pageSize = min($request->integer('page_size', self::PAGE_SIZE), self::MAX_PAGE_SIZE);

        return response()->json($request->user()->candidaturas()->paginate($pageSize));
    }

    // POST /api/vagas/{vaga}/candidaturas
    public function store(Request $request, Vaga $vaga): JsonResponse
    {
        if (! $vaga->ativa) {
            return response()->json(['message' => 'Vaga não está ativa.'], 422);
        }

        $candidaturas = $request->user()->candidaturas();

        try {
            if ($candidaturas->where('vaga_id', $vaga->id)->exists()) {
                return $this->jaCandidatado();
            }

            $candidaturas->attach($vaga->id);
        } catch (UniqueConstraintViolationException) {
            return $this->jaCandidatado();
        }

        return response()->json($candidaturas->where('vaga_id', $vaga->id)->first(), 201);
    }

    // DELETE /api/vagas/{vaga}/candidaturas
    public function destroy(Request $request, Vaga $vaga): JsonResponse
    {
        $removidas = $request->user()->candidaturas()->detach($vaga->id);

        if ($removidas === 0) {
            return response()->json(['message' => 'Você não se candidatou a esta vaga.'], 404);
        }

        return response()->json(null, 204);
    }

    private function jaCandidatado(): JsonResponse
    {
        return response()->json(['message' => 'Você já se candidatou a esta vaga.'], 409);
    }
}
