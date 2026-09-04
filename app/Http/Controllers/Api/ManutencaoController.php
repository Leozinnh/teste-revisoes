<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pessoa;
use App\Models\Revisao;
use App\Models\Veiculo;
use Database\Seeders\DadosEmVolumeSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ManutencaoController extends Controller
{
    // Painel de manutenção: apaga o banco e popula de novo com dados
    // limpos. O projeto ainda não tem autenticação (pendência E3), então
    // o endpoint exige o MANUTENCAO_TOKEN do .env — sem isso, qualquer
    // um que conhecesse a URL apagaria os dados em produção.

    // GET /api/manutencao — o front usa para avisar se o painel está
    // liberado (token configurado) antes de oferecer o botão
    public function status(): JsonResponse
    {
        $token = config('manutencao.token');

        return response()->json([
            'success' => true,
            'token_configurado' => is_string($token) && $token !== '',
        ]);
    }

    // POST /api/manutencao/limpar — migrate:fresh (derruba e recria as
    // tabelas) + DatabaseSeeder, e opcionalmente o DadosEmVolumeSeeder
    // para os relatórios ficarem bem preenchidos
    public function limparEPopular(Request $request): JsonResponse
    {
        $esperado = config('manutencao.token');
        $recebido = (string) $request->input('token', '');

        if (! is_string($esperado) || $esperado === '' || ! hash_equals($esperado, $recebido)) {
            return response()->json([
                'success' => false,
                'message' => 'Token de manutenção ausente ou inválido. Confira o MANUTENCAO_TOKEN no .env.',
            ], 403);
        }

        // O volume (1.000+ revisões) passa do limite padrão do PHP; a
        // requisição pode demorar alguns segundos
        @set_time_limit(300);

        $etapas = ['migrate:fresh', 'db:seed (padrão)'];
        $comVolume = $request->boolean('com_volume');

        try {
            Artisan::call('migrate:fresh', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);

            if ($comVolume) {
                $etapas[] = 'db:seed DadosEmVolumeSeeder';
                Artisan::call('db:seed', [
                    '--class' => DadosEmVolumeSeeder::class,
                    '--force' => true,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Manutenção falhou: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'A limpeza falhou no meio do caminho. Veja os logs: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Banco apagado e populado de novo'
                . ($comVolume ? ' (com os dados em volume para os relatórios)' : '')
                . '.',
            'etapas' => $etapas,
            'contagem' => [
                'pessoas' => Pessoa::count(),
                'veiculos' => Veiculo::count(),
                'revisoes' => Revisao::count(),
            ],
        ]);
    }
}
