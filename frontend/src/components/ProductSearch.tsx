import { keepPreviousData, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState, type KeyboardEvent, type RefObject } from 'react'
import { searchProducts } from '../api/products'
import { useDebounce } from '../hooks/useDebounce'
import type { Product } from '../types'
import { formatCents } from '../utils/format'
import { Icon } from './Icon'
import { EmptyState, ErrorMessage, Loading } from './StatusMessage'

/*
 * Busca de produtos por nome ou código, com a lista de resultados.
 *
 * Funciona de 3 jeitos:
 *  1. Digitando o nome: a lista atualiza enquanto digita.
 *  2. Leitor de código de barras: ele "digita" o código e aperta Enter
 *     sozinho. Se o código bater exatamente com um produto, ele já vai
 *     para o carrinho.
 *  3. Teclado: setas ↑ ↓ escolhem na lista e Enter adiciona.
 */

interface Props {
  // Tenta adicionar ao carrinho; devolve false se não deu (limite de estoque).
  onAdd: (product: Product) => boolean
  // Referência ao campo, para a tela focar nele com o atalho F2.
  inputRef: RefObject<HTMLInputElement | null>
}

export function ProductSearch({ onAdd, inputRef }: Props) {
  const queryClient = useQueryClient()
  const [term, setTerm] = useState('')
  const [highlighted, setHighlighted] = useState(0)
  const [notice, setNotice] = useState<string | null>(null)

  const search = useDebounce(term.trim(), 300)

  /*
   * useQuery (TanStack Query) faz a requisição e controla sozinho os estados:
   *   isPending → carregando | isError → deu erro | data → os produtos.
   * queryKey identifica a busca: se o operador buscar "arroz" de novo, o
   * resultado vem do cache, na hora.
   * placeholderData: mantém a lista anterior na tela enquanto a nova carrega,
   * em vez de piscar "carregando" a cada letra.
   */
  const productsQuery = useQuery({
    queryKey: ['products', search],
    queryFn: ({ signal }) => searchProducts(search, signal),
    placeholderData: keepPreviousData,
  })

  const products = productsQuery.data ?? []

  function changeTerm(value: string) {
    setTerm(value)
    setHighlighted(0)
    setNotice(null)
  }

  /*
   * Adiciona ao carrinho. "typed" é o texto que estava na busca quando o
   * operador escolheu o produto: o campo só é limpo se ainda tiver esse
   * mesmo texto. Assim, se ele já começou a digitar (ou bipar) o próximo
   * código, nada do que ele digitou é apagado.
   */
  function add(product: Product, typed = term) {
    if (product.stock <= 0) {
      setNotice(`"${product.name}" está sem estoque.`)
      return
    }

    if (!onAdd(product)) {
      setNotice(`Quantidade máxima em estoque de "${product.name}" já está no carrinho.`)
      return
    }

    setTerm((current) => (current === typed ? '' : current))
    setHighlighted(0)
    setNotice(null)
    inputRef.current?.focus()
  }

  // Escolhe o produto da lista: o código exato, se houver; senão, o destacado.
  function pickFrom(results: Product[], typed: string) {
    const exactCode = results.find((product) => product.code === typed.trim())
    const selected = exactCode ?? results[highlighted] ?? results[0]

    if (selected) {
      add(selected, typed)
    } else {
      setNotice(`Nenhum produto encontrado para "${typed.trim()}".`)
    }
  }

  async function handleEnter() {
    const typed = term
    const key = typed.trim()
    if (key === '') return

    // Se essa busca já está no cache (o caso normal), usa na hora, sem esperar.
    const cached = queryClient.getQueryData<Product[]>(['products', key])
    if (cached) {
      pickFrom(cached, typed)
      return
    }

    /*
     * Leitor de código de barras: ele digita rápido e aperta Enter antes do
     * debounce terminar, então o resultado ainda não existe. Aqui buscamos
     * na hora (fetchQuery) e procuramos o código EXATO.
     */
    try {
      const results = await queryClient.fetchQuery({
        queryKey: ['products', key],
        queryFn: ({ signal }) => searchProducts(key, signal),
      })
      pickFrom(results, typed)
    } catch {
      // O erro já aparece na lista (estado de erro do useQuery).
    }
  }

  function handleKeyDown(event: KeyboardEvent<HTMLInputElement>) {
    if (event.key === 'ArrowDown') {
      event.preventDefault()
      setHighlighted((index) => Math.min(index + 1, products.length - 1))
    } else if (event.key === 'ArrowUp') {
      event.preventDefault()
      setHighlighted((index) => Math.max(index - 1, 0))
    } else if (event.key === 'Enter') {
      event.preventDefault()
      void handleEnter()
    } else if (event.key === 'Escape' && term !== '') {
      // Esc com texto: só limpa a busca (não deixa a tela voltar ao menu).
      event.stopPropagation()
      changeTerm('')
    }
  }

  return (
    <section className="panel search" aria-label="Buscar produtos">
      <label className="search__field">
        <Icon name="search" size={28} />
        <input
          ref={inputRef}
          type="search"
          value={term}
          onChange={(event) => changeTerm(event.target.value)}
          onKeyDown={handleKeyDown}
          placeholder="Digite o nome ou o código do produto"
          aria-label="Buscar produto por nome ou código"
          autoComplete="off"
          autoFocus
        />
        <kbd>F2</kbd>
      </label>

      {notice && (
        <p className="search__notice" role="alert">
          {notice}
        </p>
      )}

      <div className="search__results">
        {productsQuery.isPending ? (
          <Loading text="Buscando produtos..." />
        ) : productsQuery.isError ? (
          <ErrorMessage message={productsQuery.error.message} onRetry={() => productsQuery.refetch()} />
        ) : products.length === 0 ? (
          <EmptyState
            icon={<Icon name="search" size={40} />}
            title={search ? `Nenhum produto encontrado para "${search}"` : 'Nenhum produto cadastrado'}
            hint="Confira o nome ou o código e tente de novo."
          />
        ) : (
          <ul className="product-list">
            {products.map((product, index) => {
              const outOfStock = product.stock <= 0
              return (
                <li key={product.id}>
                  <button
                    type="button"
                    className={`product${index === highlighted ? ' product--highlighted' : ''}`}
                    onClick={() => add(product)}
                    onMouseEnter={() => setHighlighted(index)}
                    disabled={outOfStock}
                  >
                    <span className="product__info">
                      <span className="product__name">{product.name}</span>
                      <span className="product__code">Cód. {product.code}</span>
                    </span>
                    <span className="product__side">
                      <span className="money product__price">{formatCents(product.price_cents)}</span>
                      {outOfStock ? (
                        <span className="tag tag--danger">Sem estoque</span>
                      ) : (
                        <span className="product__add">
                          <Icon name="plus" size={18} /> Adicionar
                        </span>
                      )}
                    </span>
                  </button>
                </li>
              )
            })}
          </ul>
        )}
      </div>
    </section>
  )
}
