<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // O search_path aponta para o schema "leonardo" (configurado no .env),
    // então as tabelas são criadas dentro dele automaticamente
    public function up(): void
    {
        Schema::create('pessoas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cpf', 14)->unique();
            // 'M' ou 'F' - o padrão é validado no Form Request
            $table->string('sexo', 1);
            $table->date('data_nascimento');
            $table->string('telefone', 20);
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pessoas');
    }
};
