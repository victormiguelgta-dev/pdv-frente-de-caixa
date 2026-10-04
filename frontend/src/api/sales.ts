import type { CreateSalePayload, Sale } from '../types'
import { request } from './client'

// Finaliza uma venda. Chama: POST /api/sales
export function createSale(payload: CreateSalePayload): Promise<Sale> {
  return request<Sale>('/sales', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

// Comprovante de uma venda. Chama: GET /api/sales/{id}
export function getSale(id: number): Promise<Sale> {
  return request<Sale>(`/sales/${id}`)
}

// Vendas finalizadas hoje. Chama: GET /api/sales
export function listTodaySales(): Promise<Sale[]> {
  return request<Sale[]>('/sales')
}
