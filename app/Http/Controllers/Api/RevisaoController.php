<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRevisaoRequest;
use App\Models\Revisao;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RevisaoController extends Controller
{
    public function index(): JsonResponse
    {
        $revisoes = Revisao::with('veiculo.pessoa:id,nome')
            ->orderBy('data_revisao', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $revisoes]);
    }

    public function show(int $id): JsonResponse
    {
        $revisao = Revisao::with('veiculo.pessoa')->find($id);

        if (! $revisao) {
            return response()->json([
                'success' => false,
                'message' => 'Revisão não encontrada.',
            ], 404);
        }

        return response()->json(['success' => true, 'data' => $revisao]);
    }

    public function store(StoreRevisaoRequest $request): JsonResponse
    {
        $revisao = Revisao::create($request->validated());

        return response()->json(['success' => true, 'data' => $revisao], 201);
    }

    public function update(StoreRevisaoRequest $request, int $id): JsonResponse
    {
        $revisao = Revisao::find($id);

        if (! $revisao) {
            return response()->json([
                'success' => false,
                'message' => 'Revisão não encontrada.',
            ], 404);
        }

        $revisao->update($request->validated());

        return response()->json(['success' => true, 'data' => $revisao]);
    }

    public function destroy(int $id): JsonResponse
    {
        $revisao = Revisao::find($id);

        if (! $revisao) {
            return response()->json([
                'success' => false,
                'message' => 'Revisão não encontrada.',
            ], 404);
        }

        try {
            $revisao->delete();
        } catch (\Throwable $e) {
            Log::error('Erro ao excluir revisão: ' . $e->getMessage(), ['revisao_id' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível excluir a revisão. Tente novamente.',
            ], 500);
        }

        return response()->json(['success' => true, 'message' => 'Revisão excluída com sucesso.']);
    }
}
