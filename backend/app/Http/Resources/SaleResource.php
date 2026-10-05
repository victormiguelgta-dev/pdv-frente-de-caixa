<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
| A venda em JSON: é o comprovante/resumo que o frontend mostra.
|
| @mixin \App\Models\Sale
*/
class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_method' => $this->payment_method->value,   // "cash"
            'payment_method_label' => $this->payment_method->label(), // "Dinheiro"
            'total_cents' => $this->total_cents,
            'amount_received_cents' => $this->amount_received_cents,
            'change_cents' => $this->change_cents,
            // Nome de quem vendeu. null nas vendas feitas antes do login existir.
            'operator_name' => $this->whenLoaded('user', fn () => $this->user?->name),
            // Data e hora no formato padrão ISO 8601 (ex: 2026-10-03T21:15:00-03:00).
            // O frontend converte para "03/10/2026 21:15".
            'created_at' => $this->created_at->toIso8601String(),

            // whenCounted / whenLoaded: o campo só aparece se tiver sido pedido.
            // No histórico do dia mandamos só a QUANTIDADE de itens (mais leve);
            // no comprovante mandamos a lista completa.
            'items_count' => $this->whenCounted('items'),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
