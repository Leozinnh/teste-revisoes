<?php

// Token do painel de manutenção — o endpoint apaga e popula o banco,
// então só funciona com o valor certo. Vazio no .env = recurso desligado.
return [
    'token' => env('MANUTENCAO_TOKEN'),
];
