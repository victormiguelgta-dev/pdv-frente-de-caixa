import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { listCatalogProducts } from '../api/catalog'
import { Icon } from '../components/Icon'
import { ProductFormDialog } from '../components/ProductFormDialog'
import { EmptyState, ErrorMessage, Loading } from '../components/StatusMessage'
import { useDebounce } from '../hooks/useDebounce'
import { useHotkeys } from '../hooks/useHotkeys'
import type { Product } from '../types'
import { formatCents } from '../utils/format'

/*
 * Tela de cadastro de produtos: lista todos (ativos e inativos), com busca,
 * e abre a janela de cadastro/edição.
 */

// null = janela fechada; 'new' = cadastrar; Product = editar esse produto.
type Editing = null | 'new' | Product

export function ProductsScreen({ onExit }: { onExit: () => void }) {
  const [term, setTerm] = useState('')
  const [editing, setEditing] = useState<Editing>(null)
  const [savedMessage, setSavedMessage] = useState<string | null>(null)

  const search = useDebounce(term.trim(), 300)

  const catalogQuery = useQuery({
    queryKey: ['catalog', search],
    queryFn: () => listCatalogProducts(search),
    placeholderData: keepPreviousData,
  })

  // Atalhos desligados com a janela aberta (lá o Esc fecha a janela).
  useHotkeys({ Escape: onExit, F2: () => setEditing('new') }, editing === null)

  const products = catalogQuery.data ?? []

  return (
    <div className="screen">
      <header className="topbar">
        <button type="button" className="btn btn--ghost" onClick={onExit}>
          <Icon name="back" size={20} /> Menu <kbd>Esc</kbd>
        </button>
        <h1>Produtos</h1>
        <button type="button" className="btn btn--primary topbar__action" onClick={() => setEditing('new')}>
          <Icon name="plus" size={20} /> Novo produto <kbd>F2</kbd>
        </button>
      </header>

      <main className="catalog">
        <section className="panel">
          <label className="search__field">
            <Icon name="search" size={24} />
            <input
              type="search"
              value={term}
              onChange={(event) => setTerm(event.target.value)}
              placeholder="Buscar por nome ou código"
              aria-label="Buscar produto no cadastro"
              autoFocus
            />
          </label>

          {savedMessage && (
            <p className="success success--small" role="status">
              <Icon name="check" size={20} /> {savedMessage}
            </p>
          )}

          <div className="catalog__list">
            {catalogQuery.isPending ? (
              <Loading text="Carregando produtos..." />
            ) : catalogQuery.isError ? (
              <ErrorMessage message={catalogQuery.error.message} onRetry={() => catalogQuery.refetch()} />
            ) : products.length === 0 ? (
              <EmptyState
                icon={<Icon name="box" size={40} />}
                title={search ? `Nenhum produto encontrado para "${search}"` : 'Nenhum produto cadastrado'}
                hint="Clique em “Novo produto” para cadastrar."
              />
            ) : (
              <table className="catalog-table">
                <thead>
                  <tr>
                    <th scope="col">Produto</th>
                    <th scope="col" className="num">
                      Preço
                    </th>
                    <th scope="col" className="num">
                      Estoque
                    </th>
                    <th scope="col">Situação</th>
                    <th scope="col">
                      <span className="sr-only">Ações</span>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {products.map((product) => (
                    <tr key={product.id} className={product.active ? undefined : 'is-inactive'}>
                      <td>
                        <strong>{product.name}</strong>
                        <small>Cód. {product.code}</small>
                      </td>
                      <td className="num money">{formatCents(product.price_cents)}</td>
                      <td className="num">
                        {product.stock === 0 ? <span className="tag tag--danger">Sem estoque</span> : product.stock}
                      </td>
                      <td>
                        <span className={`tag ${product.active ? 'tag--success' : 'tag--muted'}`}>
                          {product.active ? 'Ativo' : 'Inativo'}
                        </span>
                      </td>
                      <td className="num">
                        <button
                          type="button"
                          className="btn btn--outline"
                          onClick={() => setEditing(product)}
                          aria-label={`Editar ${product.name}`}
                        >
                          <Icon name="edit" size={18} /> Editar
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </section>
      </main>

      {editing !== null && (
        <ProductFormDialog
          product={editing === 'new' ? null : editing}
          onClose={() => setEditing(null)}
          onSaved={(saved) => {
            setSavedMessage(`"${saved.name}" ${editing === 'new' ? 'cadastrado' : 'atualizado'} com sucesso.`)
            setEditing(null)
          }}
        />
      )}
    </div>
  )
}
