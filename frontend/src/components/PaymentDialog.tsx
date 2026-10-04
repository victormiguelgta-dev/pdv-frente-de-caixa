import { useEffect, useRef, useState, type FormEvent } from 'react'
import type { PaymentMethod } from '../types'
import { digitsToCents, formatCents } from '../utils/format'
import { Icon, type IconName } from './Icon'

/*
 * Janela de pagamento: escolher a forma de pagamento e, no dinheiro,
 * informar o valor recebido e ver o troco.
 *
 * Usa o <dialog> nativo do navegador. Ele já resolve sozinho coisas de
 * acessibilidade: prende o foco dentro da janela, escurece o fundo e fecha
 * com Esc.
 */

const METHODS: { value: PaymentMethod; label: string; icon: IconName }[] = [
  { value: 'cash', label: 'Dinheiro', icon: 'cash' },
  { value: 'debit', label: 'Débito', icon: 'card' },
  { value: 'credit', label: 'Crédito', icon: 'card' },
  { value: 'pix', label: 'Pix', icon: 'pix' },
]

interface Props {
  totalCents: number
  isSubmitting: boolean
  // Mensagem geral de erro (ex: servidor fora do ar).
  errorMessage: string | null
  // Erro no valor recebido vindo do backend (ex: "menor que o total").
  amountError: string | null
  onConfirm: (method: PaymentMethod, amountReceivedCents?: number) => void
  onCancel: () => void
}

export function PaymentDialog({ totalCents, isSubmitting, errorMessage, amountError, onConfirm, onCancel }: Props) {
  const dialogRef = useRef<HTMLDialogElement>(null)
  const amountRef = useRef<HTMLInputElement>(null)
  const confirmRef = useRef<HTMLButtonElement>(null)
  const [method, setMethod] = useState<PaymentMethod | null>(null)
  const [amountCents, setAmountCents] = useState(0)

  // Abre a janela assim que o componente aparece na tela.
  useEffect(() => {
    dialogRef.current?.showModal()
  }, [])

  const isCash = method === 'cash'

  // Troco = recebido − total. Negativo quer dizer que falta dinheiro.
  const changeCents = amountCents - totalCents
  const missingMoney = isCash && changeCents < 0

  const canConfirm = method !== null && !missingMoney && !isSubmitting

  function chooseMethod(value: PaymentMethod) {
    setMethod(value)
    // Leva o foco para o próximo passo, para o operador só apertar Enter:
    // no dinheiro, o campo do valor recebido; nos outros, o botão Confirmar.
    // (setTimeout espera a tela atualizar antes de mover o foco.)
    setTimeout(() => (value === 'cash' ? amountRef.current : confirmRef.current)?.focus(), 0)
  }

  function handleSubmit(event: FormEvent) {
    event.preventDefault() // impede o navegador de recarregar a página
    if (!canConfirm || method === null) return
    onConfirm(method, isCash ? amountCents : undefined)
  }

  return (
    <dialog
      ref={dialogRef}
      className="dialog"
      aria-labelledby="payment-title"
      // "cancel" = o usuário apertou Esc. Durante o envio, não deixa fechar.
      onCancel={(event) => {
        event.preventDefault()
        if (!isSubmitting) onCancel()
      }}
    >
      <form onSubmit={handleSubmit}>
        <header className="dialog__header">
          <h2 id="payment-title">Pagamento</h2>
          <div className="total total--dialog">
            <span>Total a pagar</span>
            <strong className="money">{formatCents(totalCents)}</strong>
          </div>
        </header>

        {errorMessage && (
          <p className="alert" role="alert">
            <Icon name="alert" size={20} /> {errorMessage}
          </p>
        )}

        <fieldset className="methods" disabled={isSubmitting}>
          <legend>Forma de pagamento</legend>
          {METHODS.map((option) => (
            <button
              key={option.value}
              type="button"
              className={`method method--${option.value}${method === option.value ? ' method--selected' : ''}`}
              onClick={() => chooseMethod(option.value)}
              aria-pressed={method === option.value}
            >
              <Icon name={option.icon} size={36} />
              <span>{option.label}</span>
            </button>
          ))}
        </fieldset>

        {isCash && (
          <div className="cash">
            <label className="cash__field">
              <span>Valor recebido</span>
              <input
                ref={amountRef}
                type="text"
                inputMode="numeric"
                className="money"
                value={formatCents(amountCents)}
                // Os dígitos entram pela direita, como numa maquininha: 6-0-0-0 = R$ 60,00.
                onChange={(event) => setAmountCents(digitsToCents(event.target.value))}
                disabled={isSubmitting}
                aria-invalid={missingMoney || amountError !== null}
                aria-describedby="cash-result"
              />
            </label>

            <div className="cash__shortcuts">
              <button type="button" className="btn btn--outline" onClick={() => setAmountCents(totalCents)}>
                Valor exato
              </button>
              {suggestedBills(totalCents).map((value) => (
                <button key={value} type="button" className="btn btn--outline" onClick={() => setAmountCents(value)}>
                  {formatCents(value)}
                </button>
              ))}
            </div>

            <div id="cash-result" className={`change${missingMoney ? ' change--missing' : ''}`} aria-live="polite">
              {missingMoney ? (
                <>
                  <span>Faltam</span>
                  <strong className="money">{formatCents(-changeCents)}</strong>
                </>
              ) : (
                <>
                  <span>Troco</span>
                  <strong className="money">{formatCents(changeCents)}</strong>
                </>
              )}
            </div>

            {amountError && (
              <p className="field-error" role="alert">
                {amountError}
              </p>
            )}
          </div>
        )}

        <footer className="dialog__footer">
          <button type="button" className="btn btn--outline btn--lg" onClick={onCancel} disabled={isSubmitting}>
            Voltar <kbd>Esc</kbd>
          </button>
          <button ref={confirmRef} type="submit" className="btn btn--success btn--lg" disabled={!canConfirm}>
            {isSubmitting ? (
              'Finalizando...'
            ) : (
              <>
                Confirmar pagamento <kbd>Enter</kbd>
              </>
            )}
          </button>
        </footer>
      </form>
    </dialog>
  )
}

/*
 * Sugestões de notas para agilizar o troco: o total arredondado para cima
 * em R$ 10, R$ 50 e R$ 100, sem repetir e só valores acima do total.
 * Ex: total R$ 52,55 → R$ 60,00 e R$ 100,00.
 */
function suggestedBills(totalCents: number): number[] {
  const roundUp = (step: number) => Math.ceil(totalCents / step) * step
  const values = [roundUp(1000), roundUp(5000), roundUp(10000)].filter((value) => value > totalCents)
  return [...new Set(values)]
}
