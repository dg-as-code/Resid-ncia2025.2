<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model de Agenda de Contatos
 * 
 * NOTA: Este model não faz parte do fluxo principal de agentes financeiros.
 * É um módulo separado para gerenciamento de contatos.
 */
class Agenda extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'telefone',
        'email',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
