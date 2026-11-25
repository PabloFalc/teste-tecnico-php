<?php

namespace App\Modules\Vagas\Http\Controllers;

use App\Modules\Vagas\Domain\Models\Vaga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VagasController
{
    private const PAGE_SIZE = 20;
    private const MAX_PAGE_SIZE = 100;

    // GET /api/vagas  (somente vagas ativas)
    public function list(Request $request): JsonResponse
    {
        $pageSize = min($request->integer('page_size', self::PAGE_SIZE), self::MAX_PAGE_SIZE);

        return response()->json(Vaga::where('ativa', true)->latest()->paginate($pageSize));
    }

    // POST /api/vagas
    public function store(Request $request): JsonResponse
    {
        $vaga = $request->user()->vagasCriadas()->create($this->validar($request));

        return response()->json($vaga->fresh(), 201);
    }

    // PUT /api/vagas/{vaga}
    public function update(Request $request, Vaga $vaga): JsonResponse
    {
        $this->somenteDono($request, $vaga);

        $dados = $request->validate(['ativa' => ['sometimes', 'boolean']]) + $this->validar($request);

        $vaga->update($dados);

        return response()->json($vaga);
    }

    // DELETE /api/vagas/{vaga}
    public function destroy(Request $request, Vaga $vaga): JsonResponse
    {
        $this->somenteDono($request, $vaga);

        $vaga->delete();

        return response()->json(null, 204);
    }

    // GET /api/vagas/{vaga}/candidaturas  (candidatos da vaga, só o dono)
    public function candidatos(Request $request, Vaga $vaga): JsonResponse
    {
        $this->somenteDono($request, $vaga);

        $pageSize = min($request->integer('page_size', self::PAGE_SIZE), self::MAX_PAGE_SIZE);

        return response()->json($vaga->candidatos()->paginate($pageSize));
    }

    // PATCH /api/vagas/{vaga}/congelar  (alterna ativa: congela/descongela)
    public function alternarCongelamento(Request $request, Vaga $vaga): JsonResponse
    {
        $this->somenteDono($request, $vaga);

        $vaga->update(['ativa' => ! $vaga->ativa]);

        return response()->json($vaga);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'titulo'    => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string'],
            'tipo'      => ['required', Rule::in(['CLT', 'PJ', 'Freelancer'])],
        ]);
    }

    private function somenteDono(Request $request, Vaga $vaga): void
    {
        abort_if($request->user()->id !== $vaga->user_id, 403, 'Sem permissão.');
    }
}
