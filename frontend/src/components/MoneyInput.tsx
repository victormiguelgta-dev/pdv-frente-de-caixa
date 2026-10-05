import { forwardRef, type InputHTMLAttributes, type SyntheticEvent } from 'react'
import { digitsToCents, formatCents } from '../utils/format'

/*
 * Campo de dinheiro no estilo maquininha de cartão: os dígitos entram pela
 * direita (6-0-0-0 = R$ 60,00) e o Backspace apaga o último dígito.
 *
 * O valor é sempre em CENTAVOS (número inteiro), nunca texto ou decimal.
 *
 * Detalhe importante: o cursor fica SEMPRE no final do campo. Sem isso, se o
 * operador clicasse no meio do texto "R$ 7,99" e digitasse, o número entraria
 * no lugar errado e o valor sairia trocado.
 */

type Props = Omit<InputHTMLAttributes<HTMLInputElement>, 'value' | 'onChange' | 'type'> & {
  valueCents: number
  onChangeCents: (cents: number) => void
}

export const MoneyInput = forwardRef<HTMLInputElement, Props>(function MoneyInput(
  { valueCents, onChangeCents, className, ...rest },
  ref,
) {
  // Move o cursor para o final sempre que o campo é clicado, focado ou
  // quando a seleção muda. Exceção: "selecionar tudo" (Ctrl+A ou duplo
  // clique) continua valendo, para poder apagar ou digitar o valor por cima.
  function keepCaretAtEnd(event: SyntheticEvent<HTMLInputElement>) {
    const input = event.currentTarget
    const end = input.value.length
    const start = input.selectionStart ?? end
    const finish = input.selectionEnd ?? end
    const allSelected = start === 0 && finish === end
    if (!allSelected && (start !== end || finish !== end)) {
      input.setSelectionRange(end, end)
    }
  }

  return (
    <input
      {...rest}
      ref={ref}
      type="text"
      inputMode="numeric"
      className={`money${className ? ` ${className}` : ''}`}
      value={formatCents(valueCents)}
      onChange={(event) => onChangeCents(digitsToCents(event.target.value))}
      onSelect={keepCaretAtEnd}
      onFocus={keepCaretAtEnd}
      onClick={keepCaretAtEnd}
    />
  )
})
