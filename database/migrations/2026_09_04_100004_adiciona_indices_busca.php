<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Busca com %termo% (curinga no começo) não usa o índice comum,
    // então em tabela grande vira varredura completa. No PostgreSQL
    // isso se resolve com pg_trgm: um índice GIN de trígramas que
    // deixa o LIKE de ponta a ponta ir por índice.
    // O SQLite dos testes não tem a extensão, então só roda em pgsql.
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // As expressões precisam ser idênticas às do whereRaw no
        // VeiculoController::index() (LOWER e o REPLACE do hífen),
        // senão o banco não reconhece o índice na consulta
        DB::statement('CREATE INDEX pessoas_nome_trgm ON pessoas USING GIN (LOWER(nome) gin_trgm_ops)');
        DB::statement('CREATE INDEX veiculos_marca_trgm ON veiculos USING GIN (LOWER(marca) gin_trgm_ops)');
        DB::statement('CREATE INDEX veiculos_modelo_trgm ON veiculos USING GIN (LOWER(modelo) gin_trgm_ops)');
        DB::statement("CREATE INDEX veiculos_placa_trgm ON veiculos USING GIN (LOWER(REPLACE(placa, '-', '')) gin_trgm_ops)");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS pessoas_nome_trgm');
        DB::statement('DROP INDEX IF EXISTS veiculos_marca_trgm');
        DB::statement('DROP INDEX IF EXISTS veiculos_modelo_trgm');
        DB::statement('DROP INDEX IF EXISTS veiculos_placa_trgm');
    }
};
