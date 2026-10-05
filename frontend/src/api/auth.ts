import type { User } from '../types'
import { requestJson } from './client'

// Entrar. Chama: POST /api/login → devolve o token e o usuário.
export function login(username: string, password: string): Promise<{ token: string; user: User }> {
  return requestJson('/login', { method: 'POST', body: JSON.stringify({ username, password }) })
}

// Quem está logado (confere se o token guardado ainda vale). Chama: GET /api/me
export async function fetchMe(): Promise<User> {
  const body = await requestJson<{ user: User }>('/me')
  return body.user
}

// Sair: o backend apaga o token. Chama: POST /api/logout
export function logout(): Promise<unknown> {
  return requestJson('/logout', { method: 'POST' })
}
