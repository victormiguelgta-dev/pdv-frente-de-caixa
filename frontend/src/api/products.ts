import type { Product } from '../types'
import { request } from './client'

/*
 * Busca produtos disponíveis por nome ou código.
 * Chama: GET /api/products?search=...
 *
 * encodeURIComponent: "protege" o texto para ir na URL. Sem ele, um "&" ou
 * espaço digitado quebraria o endereço.
 */
export function searchProducts(search: string, signal?: AbortSignal): Promise<Product[]> {
  return request<Product[]>(`/products?search=${encodeURIComponent(search)}`, { signal })
}
