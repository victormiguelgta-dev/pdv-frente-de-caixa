<?php

namespace App\Models\Concerns;

use LogicException;

/*
| Trait "Immutable" (imutável = não muda depois de criado).
|
| O que é um trait: um pedaço de código que várias classes podem "vestir".
| Sale e SaleItem precisam da mesma regra, então ela fica escrita UMA vez
| aqui e as duas classes usam com "use Immutable;".
|
| Regra do teste: "uma venda finalizada não deve ser alterada".
|
| Esta é a SEGUNDA linha de defesa. A primeira é que a API não tem rota de
| editar nem de apagar venda. Esta aqui protege contra um erro de programação
| no futuro, por exemplo alguém escrever $venda->update([...]) sem querer.
|
| Limite honesto: comandos que vão direto no banco, sem passar pelo Model
| (ex: Sale::query()->update([...])), não disparam estes eventos. Por isso a
| proteção principal continua sendo não existir rota nem código que altere.
*/
trait Immutable
{
    /*
    | O Laravel chama automaticamente um método chamado "boot" + nome do trait
    | quando o Model é carregado. É aqui que registramos os "vigias".
    */
    protected static function bootImmutable(): void
    {
        // "updating" = logo antes de salvar uma ALTERAÇÃO. Lançar o erro cancela.
        static::updating(function () {
            throw new LogicException('Registro de venda finalizada não pode ser alterado.');
        });

        // "deleting" = logo antes de APAGAR.
        static::deleting(function () {
            throw new LogicException('Registro de venda finalizada não pode ser apagado.');
        });
    }
}
