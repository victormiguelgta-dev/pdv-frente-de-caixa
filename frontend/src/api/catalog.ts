import type { Product, ProductInput } from '../types'
import { request } from './client'

/*
 * Chamadas do CADASTRO de produtos (tela "Produtos").
 * Diferente da busca do caixa (products.ts), aqui vêm também os inativos.
 */

// Lista todos os produtos. Chama: GET /api/catalog/products?search=...
export function listCatalogProducts(search: string): Promise<Product[]> {
  return request<Product[]>(`/catalog/products?search=${encodeURIComponent(search)}`)
}

// Cadastra um produto. Chama: POST /api/catalog/products
export function createProduct(input: ProductInput): Promise<Product> {
  return request<Product>('/catalog/products', { method: 'POST', body: JSON.stringify(input) })
}

// Edita um produto. Chama: PUT /api/catalog/products/{id}
export function updateProduct(id: number, input: ProductInput): Promise<Product> {
  return request<Product>(`/catalog/products/${id}`, { method: 'PUT', body: JSON.stringify(input) })
}
