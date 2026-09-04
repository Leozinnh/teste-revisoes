<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pessoa;
use App\Models\Revisao;
use App\Models\Veiculo;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function resumo(): JsonResponse
    {
        // Indicadores
        $pessoas = Pessoa::count();
        $veiculos = Veiculo::count();
        $revisoes = Revisao::count();
        $totalGasto = Revisao::sum('valor');

        // Gráfico: veículos por marca
        $porMarca = Veiculo::selectRaw('marca, count(*) as total')
            ->groupBy('marca')
            ->orderByDesc('total')
            ->get();

        // Revisões por mês, agrupadas em PHP (funciona igual no Postgres
        // e no SQLite dos testes).
        $porMes = Revisao::pluck('data_revisao')
            ->map(fn ($data) => substr((string) $data, 0, 7))
            ->countBy()
            ->sortKeys();

        // Gráfico: pessoas por sexo
        $porSexo = Pessoa::selectRaw('sexo, count(*) as total')
            ->groupBy('sexo')
            ->get()
            ->map(fn ($linha) => [
                'rotulo' => $linha->sexo === 'M' ? 'Masculino' : 'Feminino',
                'total' => $linha->total,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'pessoas' => $pessoas,
                'veiculos' => $veiculos,
                'revisoes' => $revisoes,
                'total_gasto' => $totalGasto,
                'grafico_marcas' => [
                    'rotulos' => $porMarca->pluck('marca'),
                    'valores' => $porMarca->pluck('total'),
                ],
                'grafico_meses' => [
                    'rotulos' => $porMes->keys(),
                    'valores' => $porMes->values(),
                ],
                'grafico_sexo' => [
                    'rotulos' => $porSexo->pluck('rotulo'),
                    'valores' => $porSexo->pluck('total'),
                ],
            ],
        ]);
    }
}
