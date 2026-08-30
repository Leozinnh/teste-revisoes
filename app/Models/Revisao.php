<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revisao extends Model
{
    // A tabela não segue o plural padrão ("revisoes", não "revisaos")
    protected $table = 'revisoes';

    protected $fillable = [
        'veiculo_id',
        'data_revisao',
        'quilometragem',
        'descricao',
        'valor',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'data_revisao' => 'date',
            'valor' => 'decimal:2',
        ];
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }
}
