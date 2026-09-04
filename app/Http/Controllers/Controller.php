<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class Controller
{
    /**
     * Resposta padrão das listagens paginadas (pessoas, veículos e revisões):
     * { success, data: [...], meta: { current_page, total, per_page, last_page } }
     */
    protected function respostaPaginada(LengthAwarePaginator $paginator): array
    {
        return [
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

    /**
     * Itens por página pedidos na URL (padrão 25). O teto de 500 existe para
     * os dropdowns das telas, que pedem per_page=500 e vêm com a lista inteira.
     */
    protected function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 25);

        return min(max($perPage, 1), 500);
    }
}
