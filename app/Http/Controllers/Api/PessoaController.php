<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePessoaRequest;
use App\Models\Pessoa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PessoaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $pessoas = Pessoa::withCount('veiculos')
            ->orderBy('nome')
            ->paginate($this->perPage($request));

        return response()->json($this->respostaPaginada($pessoas));
    }

    public function show(int $id): JsonResponse
    {
        $pessoa = Pessoa::withCount('veiculos')->find($id);

        if (! $pessoa) {
            return response()->json([
                'success' => false,
                'message' => 'Pessoa não encontrada.',
            ], 404);
        }

        return response()->json(['success' => true, 'data' => $pessoa]);
    }

    public function store(StorePessoaRequest $request): JsonResponse
    {
        $pessoa = Pessoa::create($request->validated());

        return response()->json(['success' => true, 'data' => $pessoa], 201);
    }

    public function update(StorePessoaRequest $request, int $id): JsonResponse
    {
        $pessoa = Pessoa::find($id);

        if (! $pessoa) {
            return response()->json([
                'success' => false,
                'message' => 'Pessoa não encontrada.',
            ], 404);
        }

        $pessoa->update($request->validated());

        return response()->json(['success' => true, 'data' => $pessoa]);
    }

    // Pessoa com veículos não pode ser excluída. O banco também bloqueia
    // (restrictOnDelete); aqui a mensagem amigável vem antes.
    public function destroy(int $id): JsonResponse
    {
        $pessoa = Pessoa::withCount('veiculos')->find($id);

        if (! $pessoa) {
            return response()->json([
                'success' => false,
                'message' => 'Pessoa não encontrada.',
            ], 404);
        }

        if ($pessoa->veiculos_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Esta pessoa possui veículos cadastrados. Exclua os veículos primeiro.',
            ], 409);
        }

        try {
            $pessoa->delete();
        } catch (\Throwable $e) {
            Log::error('Erro ao excluir pessoa: ' . $e->getMessage(), ['pessoa_id' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível excluir a pessoa. Tente novamente.',
            ], 500);
        }

        return response()->json(['success' => true, 'message' => 'Pessoa excluída com sucesso.']);
    }
}
