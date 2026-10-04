import { useRef, useState } from 'react'
import type { CartItem, Product } from '../types'

/*
 * Hook do carrinho: toda a lógica de adicionar, alterar e remover itens.
 *
 * O que é um hook: uma função do React que guarda estado (dados que mudam e
 * fazem a tela atualizar). Separar o carrinho num hook deixa os componentes
 * só cuidando da aparência, e a regra fica num lugar só.
 *
 * O carrinho vive só no navegador até a venda ser finalizada. Por isso o
 * total calculado aqui é só uma PRÉVIA para o operador acompanhar: o valor
 * oficial é recalculado pelo backend com os preços do banco.
 */

// Limite do backend: quantidade de 1 a 999 por item.
const MAX_QUANTITY = 999

// Quantidade máxima que pode ir para o carrinho: o que tem em estoque, até 999.
export function maxQuantityFor(product: Product): number {
  return Math.min(product.stock, MAX_QUANTITY)
}

export function useCart() {
  const [items, setItems] = useState<CartItem[]>([])

  /*
   * Cópia sempre atualizada da lista. Por que precisa dela?
   * O leitor de código de barras pode bipar dois produtos muito rápido. Se
   * cada bipe usasse a "foto" do carrinho de quando a tela desenhou, o
   * segundo apagaria o primeiro. Lendo daqui, cada alteração parte do
   * carrinho mais recente.
   */
  const latest = useRef<CartItem[]>(items)

  function update(next: CartItem[]) {
    latest.current = next
    setItems(next)
  }

  /*
   * Adiciona 1 unidade. Se o produto já está no carrinho, soma na mesma
   * linha em vez de criar outra (o backend não aceita produto repetido).
   * Devolve false quando não dá para adicionar (sem estoque ou no limite).
   */
  function addProduct(product: Product): boolean {
    const current = latest.current
    const existing = current.find((item) => item.product.id === product.id)

    if ((existing?.quantity ?? 0) >= maxQuantityFor(product)) {
      return false
    }

    update(
      existing
        ? current.map((item) => (item === existing ? { ...item, quantity: item.quantity + 1 } : item))
        : [...current, { product, quantity: 1 }],
    )
    return true
  }

  // Define a quantidade de uma linha, sempre dentro dos limites (1 até o máximo).
  function setQuantity(productId: number, quantity: number) {
    update(
      latest.current.map((item) => {
        if (item.product.id !== productId) return item
        const safeQuantity = Math.min(Math.max(1, Math.floor(quantity)), maxQuantityFor(item.product))
        return { ...item, quantity: safeQuantity }
      }),
    )
  }

  function removeProduct(productId: number) {
    update(latest.current.filter((item) => item.product.id !== productId))
  }

  function clear() {
    update([])
  }

  // Prévia do total, em centavos: soma de preço × quantidade de cada linha.
  const totalCents = items.reduce((sum, item) => sum + item.product.price_cents * item.quantity, 0)

  // Quantidade de unidades no carrinho (2 arroz + 1 feijão = 3).
  const unitCount = items.reduce((sum, item) => sum + item.quantity, 0)

  return { items, addProduct, setQuantity, removeProduct, clear, totalCents, unitCount }
}
