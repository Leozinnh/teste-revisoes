<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ManutencaoController;
use App\Http\Controllers\Api\PessoaController;
use App\Http\Controllers\Api\RelatorioController;
use App\Http\Controllers\Api\RevisaoController;
use App\Http\Controllers\Api\VeiculoController;
use Illuminate\Support\Facades\Route;

Route::apiResource('pessoas', PessoaController::class);

Route::apiResource('veiculos', VeiculoController::class);

// Sem isso o Laravel pluraliza o parâmetro como {reviso} (inglês) e o
// FormRequest não acha a revisão na edição.
Route::apiResource('revisoes', RevisaoController::class)->parameters(['revisoes' => 'revisao']);

// Dashboard
Route::get('dashboard', [DashboardController::class, 'resumo']);

// Relatórios
Route::get('relatorios/veiculos', [RelatorioController::class, 'veiculos']);
Route::get('relatorios/veiculos-por-pessoa', [RelatorioController::class, 'veiculosPorPessoa']);
Route::get('relatorios/sexo-com-mais-veiculos', [RelatorioController::class, 'sexoComMaisVeiculos']);
Route::get('relatorios/marcas-por-quantidade', [RelatorioController::class, 'marcasPorQuantidade']);
Route::get('relatorios/marcas-por-sexo', [RelatorioController::class, 'marcasPorSexo']);
Route::get('relatorios/pessoas', [RelatorioController::class, 'pessoas']);
Route::get('relatorios/pessoas-por-sexo', [RelatorioController::class, 'pessoasPorSexo']);
Route::get('relatorios/revisoes-por-periodo', [RelatorioController::class, 'revisoesPorPeriodo']);
Route::get('relatorios/marcas-com-mais-revisoes', [RelatorioController::class, 'marcasComMaisRevisoes']);
Route::get('relatorios/pessoas-com-mais-revisoes', [RelatorioController::class, 'pessoasComMaisRevisoes']);
Route::get('relatorios/media-tempo-entre-revisoes', [RelatorioController::class, 'mediaTempoEntreRevisoes']);
Route::get('relatorios/proximas-revisoes', [RelatorioController::class, 'proximasRevisoes']);

// Painel de manutenção (apaga e popula o banco — protegido pelo
// MANUTENCAO_TOKEN do .env; sem token configurado, só o status responde)
Route::get('manutencao', [ManutencaoController::class, 'status']);
Route::post('manutencao/limpar', [ManutencaoController::class, 'limparEPopular']);
