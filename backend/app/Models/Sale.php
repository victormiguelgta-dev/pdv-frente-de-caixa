<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\Immutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
| Model Sale = uma venda finalizada (tabela "sales").
*/
#[Fillable(['user_id', 'payment_method', 'total_cents', 'amount_received_cents', 'change_cents'])]
class Sale extends Model
{
    // Venda finalizada não pode ser alterada nem apagada (ver Concerns/Immutable.php).
    use Immutable;

    protected function casts(): array
    {
        return [
            // Ao ler do banco, 'cash' vira PaymentMethod::Cash. Assim dá para
            // usar $venda->payment_method->label() e ->isCash() direto.
            // Ao salvar, um valor fora do Enum dá erro em vez de ir para o banco.
            'payment_method' => PaymentMethod::class,
            'total_cents' => 'integer',
            'amount_received_cents' => 'integer',
            'change_cents' => 'integer',
        ];
    }

    /*
    | Relacionamento: uma venda TEM MUITOS itens (hasMany).
    | Com isso, $venda->items devolve a lista de itens daquela venda, sem
    | precisar escrever a consulta no banco à mão.
    */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    // A venda PERTENCE AO operador que a fez (quem estava logado).
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
