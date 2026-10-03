<?php

namespace App\Enums;

/*
| Formas de pagamento aceitas no caixa.
|
| O que é um Enum: uma lista FECHADA de opções. Em vez de espalhar textos
| soltos pelo código ("cash", "dinheiro", "Dinheiro"...), que podem ser
| digitados errado, existe um lugar só que diz quais opções valem.
|
| O valor entre aspas ('cash', 'pix'...) é o que vai gravado no banco e o
| que o frontend envia. Ficou em inglês porque é o padrão em código e APIs.
| O texto em português para mostrar na tela está no método label().
*/
enum PaymentMethod: string
{
    case Cash = 'cash';      // dinheiro
    case Debit = 'debit';    // cartão de débito
    case Credit = 'credit';  // cartão de crédito
    case Pix = 'pix';

    // Nome em português, usado no comprovante.
    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Dinheiro',
            self::Debit => 'Cartão de débito',
            self::Credit => 'Cartão de crédito',
            self::Pix => 'Pix',
        };
    }

    // Só no dinheiro existe "valor recebido" e "troco".
    // Fica aqui para a regra não ser repetida em vários lugares.
    public function isCash(): bool
    {
        return $this === self::Cash;
    }
}
