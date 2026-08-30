<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pessoa extends Model
{
    protected $fillable = [
        'nome',
        'cpf',
        'sexo',
        'data_nascimento',
        'telefone',
        'email',
    ];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
        ];
    }

    public function veiculos(): HasMany
    {
        return $this->hasMany(Veiculo::class);
    }

    public function idade(): int
    {
        return $this->data_nascimento->age;
    }
}
