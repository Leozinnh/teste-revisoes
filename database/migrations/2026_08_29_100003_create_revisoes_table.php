<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisoes', function (Blueprint $table) {
            $table->id();
            // Veículo com revisões não pode ser excluído (mesma regra do veículo/pessoa)
            $table->foreignId('veiculo_id')->constrained('veiculos')->restrictOnDelete();
            $table->date('data_revisao');
            $table->integer('quilometragem');
            $table->string('descricao');
            $table->decimal('valor', 10, 2);
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index('data_revisao');
            $table->index('veiculo_id');
        });

        // CHECKs só no PostgreSQL: o SQLite dos testes não aceita
        // ADD CONSTRAINT depois que a tabela já foi criada.
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE revisoes ADD CONSTRAINT revisoes_quilometragem_check CHECK (quilometragem >= 0)');
            DB::statement('ALTER TABLE revisoes ADD CONSTRAINT revisoes_valor_check CHECK (valor >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('revisoes');
    }
};
