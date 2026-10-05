<?php

namespace App\Enums;

/*
| Perfis de usuário do sistema (lista fechada, como o PaymentMethod).
|
|   operator = operador de caixa: vende, consulta vendas e imprime comprovante.
|   manager  = gerente: tudo do operador + cadastro de produtos e estoque.
*/
enum UserRole: string
{
    case Operator = 'operator';
    case Manager = 'manager';

    public function label(): string
    {
        return match ($this) {
            self::Operator => 'Operador de caixa',
            self::Manager => 'Gerente',
        };
    }
}
