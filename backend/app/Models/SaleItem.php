<?php

namespace App\Models;

use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
| Model SaleItem = um produto dentro de uma venda (tabela "sale_items").
| Guarda a "fotografia" do produto no momento da venda: código, nome e preço.
*/
#[Fillable([
    'product_id',
    'product_code',
    'product_name',
    'unit_price_cents',
    'quantity',
    'subtotal_cents',
])]
class SaleItem extends Model
{
    // Item de venda finalizada também não muda.
    use Immutable;

    // A tabela não tem created_at/updated_at (o item nasce com a venda).
    // Sem esta linha, o Laravel tentaria preencher essas colunas e daria erro.
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'unit_price_cents' => 'integer',
            'quantity' => 'integer',
            'subtotal_cents' => 'integer',
        ];
    }

    // O item PERTENCE A uma venda (o lado inverso do hasMany de Sale).
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    // O item PERTENCE A um produto. Usado só como referência: para mostrar
    // nome e preço usamos as colunas copiadas, não o produto atual.
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
