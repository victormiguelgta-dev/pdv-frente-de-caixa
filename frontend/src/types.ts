/*
 * Tipos (os "formatos" dos dados) usados no frontend.
 *
 * O TypeScript usa estes tipos para avisar ANTES de rodar se algo está errado,
 * por exemplo tentar ler "produto.preco" quando o campo se chama "price_cents".
 *
 * Os nomes dos campos são exatamente os que a API Laravel devolve
 * (ver backend/app/Http/Resources). Valores em dinheiro são sempre em
 * CENTAVOS, número inteiro: 1990 = R$ 19,90.
 */

// Formas de pagamento aceitas pelo backend (App\Enums\PaymentMethod).
export type PaymentMethod = 'cash' | 'debit' | 'credit' | 'pix'

// Produto, como vem da busca (GET /api/products).
export interface Product {
  id: number
  code: string
  name: string
  price_cents: number
  stock: number
  active: boolean // disponível para venda
}

// O que o frontend envia para cadastrar ou editar um produto.
export type ProductInput = Omit<Product, 'id'>

// Item de uma venda finalizada, com o preço "fotografado" no dia da venda.
export interface SaleItem {
  product_id: number
  product_code: string
  product_name: string
  unit_price_cents: number
  quantity: number
  subtotal_cents: number
}

// Venda finalizada (comprovante e lista de vendas do dia).
export interface Sale {
  id: number
  payment_method: PaymentMethod
  payment_method_label: string
  total_cents: number
  amount_received_cents: number | null
  change_cents: number
  operator_name?: string | null // quem vendeu (null nas vendas antigas, sem login)
  created_at: string // data/hora no formato ISO 8601
  items?: SaleItem[] // vem no comprovante
  items_count?: number // vem na lista de vendas do dia
}

// O que o frontend ENVIA para finalizar a venda.
// Repare: só produto e quantidade. Preço e total quem calcula é o backend.
export interface CreateSalePayload {
  items: { product_id: number; quantity: number }[]
  payment_method: PaymentMethod
  amount_received_cents?: number
}

// Uma linha do carrinho. O carrinho existe só no frontend, até a venda ser finalizada.
export interface CartItem {
  product: Product
  quantity: number
}

// Perfis: operador de caixa (vende) e gerente (vende + cadastra produtos).
export type UserRole = 'operator' | 'manager'

// Usuário logado, como vem de POST /api/login e GET /api/me.
export interface User {
  id: number
  name: string
  username: string
  role: UserRole
  role_label: string
}
