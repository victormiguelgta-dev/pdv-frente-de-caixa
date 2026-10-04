<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
| Um item no comprovante. Usa os dados "fotografados" no momento da venda
| (nome e preço copiados), e não o produto atual.
|
| @mixin \App\Models\SaleItem
*/
class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->product_id,
            'product_code' => $this->product_code,
            'product_name' => $this->product_name,
            'unit_price_cents' => $this->unit_price_cents,
            'quantity' => $this->quantity,
            'subtotal_cents' => $this->subtotal_cents,
        ];
    }
}
