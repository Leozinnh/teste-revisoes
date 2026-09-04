<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRevisaoRequest;
use App\Models\Revisao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RevisaoController extends Controller
{
    // ?veiculo_id= retorna só as revisões de um veículo
    // (link "Ver revisões" da tela de veículos)
    public function index(Request $request): JsonResponse
    {
        $revisoes = Revisao::with('veiculo.pessoa:id,nome')
            ->when($request->query('veiculo_id'), fn ($query) => $query->where('veiculo_id', $request->query('veiculo_id')))
            ->orderBy('data_revisao', 'desc')
            ->paginate($this->perPage($request));

        return response()->json($this->respostaPaginada($revisoes));
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
