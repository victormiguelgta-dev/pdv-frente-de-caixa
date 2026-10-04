<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
| Resource = o "empacotador": decide EXATAMENTE quais campos saem no JSON.
|
| Por que não devolver o Model direto? Porque aí qualquer coluna nova da
| tabela (até uma que não deveria ser pública) apareceria na API sem ninguém
| perceber. Com o Resource, só sai o que está listado aqui.
|
| @mixin \App\Models\Product
*/
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'price_cents' => $this->price_cents,
            'stock' => $this->stock,
        ];
    }
}
