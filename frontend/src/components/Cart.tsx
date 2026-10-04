import { useState } from 'react'
import { maxQuantityFor } from '../hooks/useCart'
import type { CartItem } from '../types'
import { formatCents } from '../utils/format'
import { Icon } from './Icon'
import { EmptyState } from './StatusMessage'

/*
 * Carrinho: os itens da venda em andamento e o total.
 * O componente só MOSTRA. Quem guarda e altera os dados é o hook useCart,
 * que vem da tela da venda pelas props.
 */

interface Props {
  items: CartItem[]
  totalCents: number
  unitCount: number
  // Erros que o backend devolveu para itens específicos, pelo id do produto
  // (ex: "produto não está mais disponível"). A linha fica em vermelho.
  itemErrors: Record<number, string>
  onQuantityChange: (productId: number, quantity: number) => void
  onRemove: (productId: number) => void
  onClear: () => void
  onCheckout: () => void
}

export function Cart({
  items,
  totalCents,
  unitCount,
  itemErrors,
  onQuantityChange,
  onRemove,
  onClear,
  onCheckout,
}: Props) {
  const isEmpty = items.length === 0

  function confirmClear() {
    // Confirmação simples para não perder o carrinho inteiro por um clique errado.
    if (window.confirm('Remover todos os itens do carrinho?')) {
      onClear()
    }
  }

  return (
    <section className="panel cart" aria-label="Carrinho">
      <header className="cart__header">
        <h2>
          Carrinho{' '}
          <span className="cart__count">
            {unitCount} {unitCount === 1 ? 'item' : 'itens'}
          </span>
        </h2>
        {!isEmpty && (
          <button type="button" className="btn btn--ghost" onClick={confirmClear}>
            Limpar
          </button>
        )}
      </header>

      <div className="cart__items">
        {isEmpty ? (
          <EmptyState
            icon={<Icon name="cart" size={48} />}
            title="Carrinho vazio"
            hint="Busque um produto ao lado para começar a venda."
          />
        ) : (
          <ul>
            {items.map((item) => (
              <CartRow
                key={item.product.id}
                item={item}
                error={itemErrors[item.product.id]}
                onQuantityChange={onQuantityChange}
                onRemove={onRemove}
              />
            ))}
          </ul>
        )}
      </div>

      <footer className="cart__footer">
        <div className="total">
          <span>Total</span>
          {/* aria-live: o leitor de tela anuncia o total quando ele muda. */}
          <strong className="money" aria-live="polite">
            {formatCents(totalCents)}
          </strong>
        </div>
        <button type="button" className="btn btn--success btn--xl" onClick={onCheckout} disabled={isEmpty}>
          Finalizar venda <kbd>F4</kbd>
        </button>
      </footer>
    </section>
  )
}

interface RowProps {
  item: CartItem
  error?: string
  onQuantityChange: (productId: number, quantity: number) => void
  onRemove: (productId: number) => void
}

// Uma linha do carrinho: nome, preço, quantidade (− campo +), subtotal e remover.
function CartRow({ item, error, onQuantityChange, onRemove }: RowProps) {
  const { product, quantity } = item
  const max = maxQuantityFor(product)

  /*
   * "Rascunho" do campo de quantidade: enquanto o operador digita, o valor
   * fica só aqui (ele pode apagar tudo para digitar outro número). Ao sair
   * do campo ou apertar Enter, o valor é aplicado ao carrinho.
   */
  const [draft, setDraft] = useState<string | null>(null)

  function commitDraft() {
    if (draft !== null) {
      const parsed = Number.parseInt(draft, 10)
      if (!Number.isNaN(parsed)) onQuantityChange(product.id, parsed)
      setDraft(null)
    }
  }

  return (
    <li className={`cart-row${error ? ' cart-row--error' : ''}`}>
      <div className="cart-row__info">
        <span className="cart-row__name">{product.name}</span>
        <span className="cart-row__unit money">{formatCents(product.price_cents)} cada</span>
      </div>

      <div className="stepper" role="group" aria-label={`Quantidade de ${product.name}`}>
        <button
          type="button"
          onClick={() => onQuantityChange(product.id, quantity - 1)}
          disabled={quantity <= 1}
          aria-label="Diminuir quantidade"
        >
          <Icon name="minus" size={20} />
        </button>
        <input
          type="text"
          inputMode="numeric"
          value={draft ?? String(quantity)}
          onChange={(event) => setDraft(event.target.value.replace(/\D/g, ''))}
          onBlur={commitDraft}
          onKeyDown={(event) => event.key === 'Enter' && commitDraft()}
          aria-label="Quantidade"
        />
        <button
          type="button"
          onClick={() => onQuantityChange(product.id, quantity + 1)}
          disabled={quantity >= max}
          aria-label="Aumentar quantidade"
        >
          <Icon name="plus" size={20} />
        </button>
      </div>

      <span className="cart-row__subtotal money">{formatCents(product.price_cents * quantity)}</span>

      <button
        type="button"
        className="icon-btn icon-btn--danger"
        onClick={() => onRemove(product.id)}
        aria-label={`Remover ${product.name}`}
        title="Remover item"
      >
        <Icon name="trash" size={20} />
      </button>

      {error && (
        <p className="cart-row__error" role="alert">
          {error}
        </p>
      )}
    </li>
  )
}
