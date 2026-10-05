/*
 * Cliente HTTP: o ÚNICO lugar do frontend que conversa com o backend.
 *
 * Por que centralizar aqui?
 *  - O endereço da API fica num lugar só.
 *  - Todo erro (rede fora, 404, 422...) vira o mesmo tipo, ApiError, então
 *    as telas tratam erro sempre do mesmo jeito.
 *  - Os componentes nunca chamam fetch direto.
 */

// Endereço da API, lido do arquivo .env (VITE_API_URL).
// O valor padrão é o endereço do "php artisan serve" na máquina local.
const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api'

/*
 * Token de login (Sanctum). Guardado aqui, na memória, e enviado em toda
 * requisição no cabeçalho "Authorization: Bearer <token>".
 * Quem define é a sessão (src/session.ts), no login e ao abrir o sistema.
 */
let authToken: string | null = null

export function setAuthToken(token: string | null) {
  authToken = token
}

/*
 * O que fazer quando a API responder 401 (token expirado ou inválido):
 * a sessão registra aqui uma função que volta para a tela de login.
 */
let onUnauthorized: (() => void) | null = null

export function setUnauthorizedHandler(handler: (() => void) | null) {
  onUnauthorized = handler
}

/*
 * Erro padronizado da API.
 *  - status: código HTTP (422, 404...). 0 quando nem chegou ao servidor (rede).
 *  - message: mensagem pronta para mostrar ao operador.
 *  - fieldErrors: erros por campo, como o Laravel devolve no 422. Exemplo:
 *      { "items.1.product_id": ["O produto ... não está mais disponível."] }
 */
export class ApiError extends Error {
  readonly status: number
  readonly fieldErrors: Record<string, string[]>

  constructor(status: number, message: string, fieldErrors: Record<string, string[]> = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.fieldErrors = fieldErrors
  }
}

// Mensagens para quando o backend não manda uma mensagem própria.
const NETWORK_ERROR_MESSAGE =
  'Não foi possível conectar ao servidor. Verifique se o sistema está ligado e tente novamente.'
const UNEXPECTED_ERROR_MESSAGE = 'Ocorreu um erro inesperado. Tente novamente.'

/*
 * Faz uma requisição e devolve o campo "data" da resposta (o Laravel
 * embrulha as respostas dos Resources em { "data": ... }).
 *
 * <T> é um "tipo genérico": quem chama diz o que espera receber, ex:
 * request<Product[]>('/products') devolve uma lista de Product.
 */
export async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const body = await requestJson<{ data: T }>(path, options)
  return body.data
}

/*
 * Faz uma requisição e devolve o corpo inteiro da resposta (usado no login,
 * que não vem dentro de "data"). Toda a parte de erro fica aqui.
 */
export async function requestJson<T>(path: string, options: RequestInit = {}): Promise<T> {
  let response: Response

  try {
    response = await fetch(`${API_URL}${path}`, {
      ...options,
      headers: {
        // Accept: pedimos resposta em JSON, inclusive nos erros.
        Accept: 'application/json',
        'Content-Type': 'application/json',
        // Só envia o token se houver alguém logado.
        ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
        ...options.headers,
      },
    })
  } catch {
    // fetch só cai aqui quando a requisição nem chegou: servidor desligado,
    // sem internet, endereço errado ou bloqueio de CORS.
    throw new ApiError(0, NETWORK_ERROR_MESSAGE)
  }

  // Tenta ler o corpo como JSON. Se não for JSON (ex: erro 500 do servidor
  // web), segue com null em vez de quebrar a tela.
  const body: unknown = await response.json().catch(() => null)

  // 401 com alguém logado = o token venceu ou foi invalidado: volta ao login.
  if (response.status === 401 && authToken) {
    onUnauthorized?.()
  }

  if (!response.ok) {
    const errorBody = (body ?? {}) as { message?: string; errors?: Record<string, string[]> }

    // Erro 500 pode trazer detalhes técnicos; para o operador mostramos uma
    // mensagem genérica. Nos outros erros, o backend já manda mensagem em português.
    const message =
      response.status >= 500 ? UNEXPECTED_ERROR_MESSAGE : errorBody.message ?? UNEXPECTED_ERROR_MESSAGE

    throw new ApiError(response.status, message, errorBody.errors ?? {})
  }

  return body as T
}
