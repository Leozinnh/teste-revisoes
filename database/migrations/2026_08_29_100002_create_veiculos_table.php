<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veiculos', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete: pessoa com veículos não pode ser excluída
            // (o controller também bloqueia, com mensagem amigável)
            $table->foreignId('pessoa_id')->constrained('pessoas')->restrictOnDelete();
            $table->string('marca');
            $table->string('modelo');
            $table->integer('ano');
            // 7 caracteres: padrão antigo (ABC1234) ou Mercosul (ABC1D23)
            $table->string('placa', 7)->unique();
            $table->timestamps();

            $table->index('pessoa_id');
            $table->index('marca');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculos');
    }
};
