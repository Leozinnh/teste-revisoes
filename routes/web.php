<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas da aplicação
|--------------------------------------------------------------------------
|
| A aplicação é uma SPA Vue: o Laravel serve o mesmo "casco" de página
| para qualquer caminho, e quem decide o que mostrar é o Vue Router
| (arquivo resources/js/router.js).
|
| Os dados das telas vêm da API (arquivo routes/api.php).
|
*/

Route::get('/{any}', function () {
    return view('layouts.app');
// O (?!api) evita que esta rota "pegue" as chamadas /api/...,
// que precisam ser respondidas como JSON pelas rotas da API
// (ex.: GET /api/pessoas deve retornar JSON, e não o HTML da SPA).
})->where('any', '^(?!api).*');
