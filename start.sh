#!/bin/sh
# Script de inicialização usado pelo Render (start command) em deploys Docker.
#
# 1. Aplica as migrations pendentes (idempotente — só roda as que faltam);
# 2. Roda o seeder APENAS se o banco estiver vazio, porque o DatabaseSeeder
#    não é idempotente (cria emails/placas únicos — repetir quebraria);
# 3. Sobe o servidor na porta definida pelo Render ($PORT).
set -e

# Gera o manifesto de packages (a imagem compila o vendor com --no-scripts)
php artisan package:discover --ansi

php artisan migrate --force --no-interaction

# Seeda só quando não há nenhuma pessoa cadastrada
CONTAGEM=$(php artisan tinker --execute="echo \App\Models\Pessoa::query()->count();" 2>/dev/null | grep -E '^[0-9]+$' | tail -1)
if [ "$CONTAGEM" = "0" ]; then
    echo "Banco vazio — rodando seeder..."
    php artisan db:seed --no-interaction
else
    echo "Banco já populado — pulando seeder."
fi

php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
