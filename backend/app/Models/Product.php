<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
| Model Product = a tabela "products" em forma de classe PHP.
| Cada objeto Product é uma linha da tabela. Exemplos de uso:
|   Product::find(3)            busca o produto de id 3
|   Product::active()->get()    todos os produtos disponíveis
*/

// Campos que podem ser preenchidos de uma vez com Product::create([...]).
// É uma proteção: um campo fora desta lista é ignorado, mesmo que alguém o envie.
#[Fillable(['code', 'name', 'price_cents', 'active', 'stock'])]
class Product extends Model
{
    // HasFactory permite gerar produtos falsos nos testes (passo 3).
    use HasFactory;

    /*
    | Conversão automática de tipos (cast). O banco SQLite guarda "active"
    | como 0 ou 1. Com o cast, no PHP ele vira true ou false de verdade.
    */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'active' => 'boolean',
            'stock' => 'integer',
        ];
    }

    /*
    | Scope = um "filtro com nome" reaproveitável.
    | Em vez de repetir ->where('active', true) em vários lugares, escrevemos
    | Product::active(). Se a regra de "disponível" mudar um dia, muda só aqui.
    */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }
}
