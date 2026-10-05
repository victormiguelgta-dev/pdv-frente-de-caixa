import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useRef, useState } from 'react'
import { ApiError } from '../api/client'
import { createSale } from '../api/sales'
import { Cart } from '../components/Cart'
import { Icon } from '../components/Icon'
import { PaymentDialog } from '../components/PaymentDialog'
import { PrintButton } from '../components/PrintButton'
import { ProductSearch } from '../components/ProductSearch'
import { Receipt } from '../components/Receipt'
import { useCart } from '../hooks/useCart'
import { useHotkeys } from '../hooks/useHotkeys'
import type { CreateSalePayload, PaymentMethod, Sale } from '../types'

/*
 * Tela da venda: busca à esquerda, carrinho e total à direita.
 * Ao finalizar, abre o pagamento e, se der certo, mostra o comprovante.
 *
 * Esta tela "orquestra" os componentes: guarda o carrinho (useCart), envia
 * a venda para a API e distribui os erros que voltarem para o lugar certo.
 */

export function SaleScreen({ onExit }: { onExit: () => void }) {
  const queryClient = useQueryClient()
  const cart = useCart()
  const searchRef = useRef<HTMLInputElement>(null)

  const [isPaying, setIsPaying] = useState(false)
  const [finishedSale, setFinishedSale] = useState<Sale | null>(null)

  // Erros que o backend devolveu, separados por onde vão aparecer.
  const [itemErrors, setItemErrors] = useState<Record<number, string>>({})
  const [cartAlert, setCartAlert] = useState<string | null>(null)
  const [paymentError, setPaymentError] = useState<string | null>(null)
  const [amountError, setAmountError] = useState<string | null>(null)

  // Guarda a lista de itens ENVIADA, para traduzir "items.1" (posição na
  // lista) de volta para o produto certo quando o backend devolver erro.
  const submittedItems = useRef<CreateSalePayload['items']>([])

  /*
   * useMutation (TanStack Query) = uma ação que ALTERA dados no servidor.
   * Ele controla o estado "enviando" (isPending), que usamos para travar o
   * botão e evitar que um duplo clique crie duas vendas.
   * Mutations não são repetidas automaticamente em caso de erro (retry: 0
   * no main.tsx), justamente para nunca duplicar uma venda.
   */
  const saleMutation = useMutation({
    mutationFn: createSale,
    onSuccess: (sale) => {
      setFinishedSale(sale)
      setIsPaying(false)
      cart.clear()
      // A venda mudou o estoque e a lista de vendas do dia: marca esses
      // dados como desatualizados para serem buscados de novo.
      void queryClient.invalidateQueries({ queryKey: ['products'] })
      void queryClient.invalidateQueries({ queryKey: ['sales'] })
    },
    onError: (error) => showSaleError(error),
  })

  function showSaleError(error: Error) {
    if (!(error instanceof ApiError)) {
      setPaymentError('Ocorreu um erro inesperado. Tente novamente.')
      return
    }

    /*
     * O backend aponta o item com problema pela POSIÇÃO na lista enviada:
     * "items.1.product_id" = segundo item. Aqui convertemos a posição no id
     * do produto, para marcar a linha certa do carrinho em vermelho.
     */
    const errorsByProduct: Record<number, string> = {}
    for (const [field, messages] of Object.entries(error.fieldErrors)) {
      const match = /^items\.(\d+)\./.exec(field)
      const item = match ? submittedItems.current[Number(match[1])] : undefined
      if (item) errorsByProduct[item.product_id] = messages[0]
    }

    if (Object.keys(errorsByProduct).length > 0) {
      // Erro em itens: fecha o pagamento para o operador ver e corrigir o carrinho.
      setItemErrors(errorsByProduct)
      setCartAlert('Alguns itens não podem ser vendidos. Corrija os itens marcados em vermelho.')
      setIsPaying(false)
      return
    }

    const amountMessage = error.fieldErrors.amount_received_cents?.[0]
    if (amountMessage) {
      setAmountError(amountMessage)
      return
    }

    // Qualquer outro erro (rede, servidor, limite de requisições): mensagem
    // geral no pagamento. O carrinho fica intacto para tentar de novo.
    setPaymentError(error.message)
  }

  function openPayment() {
    if (cart.items.length === 0 || finishedSale) return
    setPaymentError(null)
    setAmountError(null)
    setIsPaying(true)
  }

  function closePayment() {
    setIsPaying(false)
    searchRef.current?.focus()
  }

  function confirmPayment(method: PaymentMethod, amountReceivedCents?: number) {
    setPaymentError(null)
    setAmountError(null)

    // Monta o pedido: SÓ produto e quantidade. Preço e total quem calcula é
    // o backend (regra do teste: o valor final é garantido pela aplicação).
    const payload: CreateSalePayload = {
      items: cart.items.map((item) => ({ product_id: item.product.id, quantity: item.quantity })),
      payment_method: method,
      ...(method === 'cash' ? { amount_received_cents: amountReceivedCents } : {}),
    }

    submittedItems.current = payload.items
    saleMutation.mutate(payload)
  }

  // Quando o operador mexe num item com erro, o erro daquele item some.
  function clearItemError(productId: number) {
    if (!(productId in itemErrors)) return
    const { [productId]: _removed, ...rest } = itemErrors
    setItemErrors(rest)
    if (Object.keys(rest).length === 0) setCartAlert(null)
  }

  function startNewSale() {
    setFinishedSale(null)
    setItemErrors({})
    setCartAlert(null)
    setTimeout(() => searchRef.current?.focus(), 0)
  }

  function exit() {
    if (cart.items.length > 0 && !window.confirm('Sair e descartar o carrinho atual?')) return
    onExit()
  }

  // Atalhos desta tela. Ficam desligados com a janela de pagamento aberta
  // (lá o Esc e o Enter têm outra função).
  useHotkeys(
    finishedSale
      ? { F2: startNewSale, Escape: onExit }
      : {
          F2: () => searchRef.current?.focus(),
          F4: openPayment,
          Escape: exit,
        },
    !isPaying,
  )

  return (
    <div className="screen">
      <header className="topbar">
        <button type="button" className="btn btn--ghost" onClick={finishedSale ? onExit : exit}>
          <Icon name="back" size={20} /> Menu <kbd>Esc</kbd>
        </button>
        <h1>{finishedSale ? 'Venda finalizada' : 'Nova venda'}</h1>
      </header>

      {finishedSale ? (
        // Venda concluída: mostra o comprovante com o que o backend salvou.
        <main className="receipt-screen">
          <p className="success" role="status">
            <Icon name="check" size={28} /> Venda nº {finishedSale.id} finalizada com sucesso!
          </p>
          <Receipt sale={finishedSale} />
          <div className="receipt-actions">
            <PrintButton />
            <button type="button" className="btn btn--primary btn--xl" onClick={startNewSale} autoFocus>
              Nova venda <kbd>F2</kbd>
            </button>
          </div>
        </main>
      ) : (
        <main className="sale">
          <ProductSearch
            inputRef={searchRef}
            onAdd={(product) => {
              clearItemError(product.id)
              return cart.addProduct(product)
            }}
          />

          <div className="sale__cart">
            {cartAlert && (
              <p className="alert" role="alert">
                <Icon name="alert" size={20} /> {cartAlert}
              </p>
            )}
            <Cart
              items={cart.items}
              totalCents={cart.totalCents}
              unitCount={cart.unitCount}
              itemErrors={itemErrors}
              onQuantityChange={(productId, quantity) => {
                clearItemError(productId)
                cart.setQuantity(productId, quantity)
              }}
              onRemove={(productId) => {
                clearItemError(productId)
                cart.removeProduct(productId)
              }}
              onClear={() => {
                setItemErrors({})
                setCartAlert(null)
                cart.clear()
              }}
              onCheckout={openPayment}
            />
          </div>
        </main>
      )}

      {isPaying && (
        <PaymentDialog
          totalCents={cart.totalCents}
          isSubmitting={saleMutation.isPending}
          errorMessage={paymentError}
          amountError={amountError}
          onConfirm={confirmPayment}
          onCancel={closePayment}
        />
      )}
    </div>
  )
}
