import type { Sale } from '../types'
import { formatCents, formatDateTime } from '../utils/format'

/*
 * Comprovante (resumo) de uma venda finalizada.
 *
 * Mostra SEMPRE os dados que vieram do backend: preço e nome registrados
 * no momento da venda. Nunca usa o carrinho, porque o oficial é o que
 * o backend calculou e salvou.
 */
export function Receipt({ sale }: { sale: Sale }) {
  const items = sale.items ?? []

  return (
    <article className="receipt" aria-label={`Comprovante da venda ${sale.id}`}>
      <header className="receipt__header">
        <h2>Venda nº {sale.id}</h2>
        <time dateTime={sale.created_at}>{formatDateTime(sale.created_at)}</time>
      </header>

      <table className="receipt__items">
        <thead>
          <tr>
            <th scope="col">Produto</th>
            <th scope="col" className="num">
              Qtd × Preço
            </th>
            <th scope="col" className="num">
              Subtotal
            </th>
          </tr>
        </thead>
        <tbody>
          {items.map((item) => (
            <tr key={item.product_id}>
              <td>
                {item.product_name}
                <small>Cód. {item.product_code}</small>
              </td>
              <td className="num money">
                {item.quantity} × {formatCents(item.unit_price_cents)}
              </td>
              <td className="num money">{formatCents(item.subtotal_cents)}</td>
            </tr>
          ))}
        </tbody>
      </table>

      <dl className="receipt__summary">
        <div className="receipt__total">
          <dt>Total</dt>
          <dd className="money">{formatCents(sale.total_cents)}</dd>
        </div>
        <div>
          <dt>Pagamento</dt>
          <dd>{sale.payment_method_label}</dd>
        </div>
        {/* Quem vendeu (vendas antigas, de antes do login, não têm). */}
        {sale.operator_name && (
          <div>
            <dt>Operador</dt>
            <dd>{sale.operator_name}</dd>
          </div>
        )}
        {/* Valor recebido e troco só existem no pagamento em dinheiro. */}
        {sale.amount_received_cents !== null && (
          <>
            <div>
              <dt>Valor recebido</dt>
              <dd className="money">{formatCents(sale.amount_received_cents)}</dd>
            </div>
            <div className="receipt__change">
              <dt>Troco</dt>
              <dd className="money">{formatCents(sale.change_cents)}</dd>
            </div>
          </>
        )}
      </dl>
    </article>
  )
}
