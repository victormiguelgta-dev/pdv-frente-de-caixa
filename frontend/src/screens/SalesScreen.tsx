import { useQuery } from '@tanstack/react-query'
import { useState, type FormEvent } from 'react'
import { ApiError } from '../api/client'
import { getSale, listTodaySales } from '../api/sales'
import { Icon } from '../components/Icon'
import { Receipt } from '../components/Receipt'
import { EmptyState, ErrorMessage, Loading } from '../components/StatusMessage'
import { useHotkeys } from '../hooks/useHotkeys'
import { formatCents, formatTime } from '../utils/format'

/*
 * Consultar vendas finalizadas.
 *  - À esquerda: buscar pelo número da venda e a lista das vendas de hoje.
 *  - À direita: o comprovante da venda escolhida.
 */

export function SalesScreen({ onExit }: { onExit: () => void }) {
  const [saleNumber, setSaleNumber] = useState('')
  const [selectedId, setSelectedId] = useState<number | null>(null)

  useHotkeys({ Escape: onExit })

  const todayQuery = useQuery({ queryKey: ['sales', 'today'], queryFn: listTodaySales })

  // Só busca o comprovante quando há uma venda escolhida (enabled).
  const saleQuery = useQuery({
    queryKey: ['sales', selectedId],
    queryFn: () => getSale(selectedId as number),
    enabled: selectedId !== null,
    // 404 não adianta repetir: a venda não existe.
    retry: (count, error) => !(error instanceof ApiError && error.status === 404) && count < 1,
  })

  function handleLookup(event: FormEvent) {
    event.preventDefault()
    const id = Number.parseInt(saleNumber, 10)
    if (id > 0) setSelectedId(id)
  }

  const todaySales = todayQuery.data ?? []
  const todayTotal = todaySales.reduce((sum, sale) => sum + sale.total_cents, 0)

  return (
    <div className="screen">
      <header className="topbar">
        <button type="button" className="btn btn--ghost" onClick={onExit}>
          <Icon name="back" size={20} /> Menu <kbd>Esc</kbd>
        </button>
        <h1>Consultar vendas</h1>
      </header>

      <main className="sales">
        <section className="panel" aria-label="Encontrar venda">
          <form className="lookup" onSubmit={handleLookup}>
            <label htmlFor="sale-number">Número da venda</label>
            <div className="lookup__row">
              <input
                id="sale-number"
                type="text"
                inputMode="numeric"
                value={saleNumber}
                onChange={(event) => setSaleNumber(event.target.value.replace(/\D/g, ''))}
                placeholder="Ex: 15"
                autoFocus
              />
              <button type="submit" className="btn btn--primary" disabled={saleNumber === ''}>
                <Icon name="search" size={20} /> Ver comprovante
              </button>
            </div>
          </form>

          <h2 className="section-title">Vendas de hoje</h2>
          {todayQuery.isPending ? (
            <Loading text="Carregando vendas de hoje..." />
          ) : todayQuery.isError ? (
            <ErrorMessage message={todayQuery.error.message} onRetry={() => todayQuery.refetch()} />
          ) : todaySales.length === 0 ? (
            <EmptyState icon={<Icon name="receipt" size={40} />} title="Nenhuma venda hoje ainda." />
          ) : (
            <>
              <ul className="sales-list">
                {todaySales.map((sale) => (
                  <li key={sale.id}>
                    <button
                      type="button"
                      className={`sales-list__item${sale.id === selectedId ? ' sales-list__item--selected' : ''}`}
                      onClick={() => setSelectedId(sale.id)}
                    >
                      <span className="sales-list__id">Nº {sale.id}</span>
                      <span>{formatTime(sale.created_at)}</span>
                      <span>
                        {sale.items_count} {sale.items_count === 1 ? 'item' : 'itens'}
                      </span>
                      <span>{sale.payment_method_label}</span>
                      <span className="money">{formatCents(sale.total_cents)}</span>
                    </button>
                  </li>
                ))}
              </ul>
              <p className="sales-list__summary">
                <span>
                  {todaySales.length} {todaySales.length === 1 ? 'venda' : 'vendas'}
                </span>
                <strong className="money">Total do dia: {formatCents(todayTotal)}</strong>
              </p>
            </>
          )}
        </section>

        <section className="panel" aria-label="Comprovante">
          {selectedId === null ? (
            <EmptyState
              icon={<Icon name="receipt" size={48} />}
              title="Nenhuma venda selecionada"
              hint="Digite o número da venda ou escolha uma venda de hoje."
            />
          ) : saleQuery.isPending ? (
            <Loading text="Carregando comprovante..." />
          ) : saleQuery.isError ? (
            saleQuery.error instanceof ApiError && saleQuery.error.status === 404 ? (
              <EmptyState
                icon={<Icon name="alert" size={48} />}
                title={`Venda nº ${selectedId} não encontrada`}
                hint="Confira o número e tente de novo."
              />
            ) : (
              <ErrorMessage message={saleQuery.error.message} onRetry={() => saleQuery.refetch()} />
            )
          ) : (
            <Receipt sale={saleQuery.data} />
          )}
        </section>
      </main>
    </div>
  )
}
