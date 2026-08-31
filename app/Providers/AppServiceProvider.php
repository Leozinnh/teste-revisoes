<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Proxies como o Render encerram o TLS e chegam no container via HTTP,
        // avisando pelo header X-Forwarded-Proto. Sem forçar o scheme, o
        // @vite()/asset() geraria URLs http:// e o navegador bloquearia
        // os assets (Mixed Content). Localmente o header não existe, então
        // o http:// do ambiente de dev continua funcionando.
        if (request()->header('x-forwarded-proto') === 'https') {
            URL::forceScheme('https');
        }
    }
}
