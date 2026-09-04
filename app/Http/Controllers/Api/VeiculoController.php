<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVeiculoRequest;
use App\Models\Veiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VeiculoController extends Controller
{
    // ?pessoa_id= retorna só os veículos de uma pessoa (link "Ver veículos" da tela de pessoas)
    public function index(Request $request): JsonResponse
    {
        $veiculos = Veiculo::with('pessoa:id,nome')
            ->when($request->query('pessoa_id'), fn ($query) => $query->where('pessoa_id', $request->query('pessoa_id')))
            ->orderBy('marca')
            ->orderBy('modelo')
            ->paginate($this->perPage($request));

        return response()->json($this->respostaPaginada($veiculos));
    }

    public function show(int $id): JsonResponse
    {
        $veiculo = Veiculo::with('pessoa', 'revisoes')->find($id);

        if (! $veiculo) {
            return response()->json([
                'success' => false,
                'message' => 'Veículo não encontrado.',
            ], 404);
        }

        return response()->json(['success' => true, 'data' => $veiculo]);
    }

    public function store(StoreVeiculoRequest $request): JsonResponse
    {
        $veiculo = Veiculo::create($request->validated());

        return response()->json(['success' => true, 'data' => $veiculo], 201);
    }

    public function update(StoreVeiculoRequest $request, int $id): JsonResponse
    {
        $veiculo = Veiculo::find($id);

        if (! $veiculo) {
            return response()->json([
                'success' => false,
                'message' => 'Veículo não encontrado.',
            ], 404);
        }

        $veiculo->update($request->validated());

        return response()->json(['success' => true, 'data' => $veiculo]);
    }

    // Veículo com revisões não pode ser excluído (mesma regra da pessoa)
    public function destroy(int $id): JsonResponse
    {
        $veiculo = Veiculo::withCount('revisoes')->find($id);

        if (! $veiculo) {
            return response()->json([
                'success' => false,
                'message' => 'Veículo não encontrado.',
            ], 404);
        }

        if ($veiculo->revisoes_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Este veículo possui revisões cadastradas. Exclua as revisões primeiro.',
            ], 409);
        }

        try {
            $veiculo->delete();
        } catch (\Throwable $e) {
            Log::error('Erro ao excluir veículo: ' . $e->getMessage(), ['veiculo_id' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível excluir o veículo. Tente novamente.',
            ], 500);
        }

        return response()->json(['success' => true, 'message' => 'Veículo excluído com sucesso.']);
    }
}
