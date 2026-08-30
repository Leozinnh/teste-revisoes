<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Veiculo extends Model
{
    protected $fillable = [
        'pessoa_id',
        'marca',
        'modelo',
        'ano',
        'placa',
    ];

    public function pessoa(): BelongsTo
    {
        return $this->belongsTo(Pessoa::class);
    }

    public function revisoes(): HasMany
    {
        return $this->hasMany(Revisao::class);
    }
}
