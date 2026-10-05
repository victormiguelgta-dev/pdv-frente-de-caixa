import { useQueryClient } from '@tanstack/react-query'
import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react'
import { fetchMe, login as apiLogin, logout as apiLogout } from './api/auth'
import { setAuthToken, setUnauthorizedHandler } from './api/client'
import type { User } from './types'

/*
 * Sessão = quem está logado no sistema.
 *
 * Usa um "Context" do React: um jeito de deixar uma informação disponível
 * para QUALQUER tela, sem precisar passar de componente em componente.
 * Qualquer tela chama useSession() e recebe o usuário, login e logout.
 *
 * Onde o token fica guardado: sessionStorage (memória da aba do navegador).
 *  - Sobrevive a um F5 (recarregar a página).
 *  - É apagado ao FECHAR a aba/navegador: o caixa não fica logado à toa.
 *  - Não vai em cookie, então não sofre ataque do tipo CSRF.
 * (Num sistema maior, a alternativa mais forte é cookie httpOnly com o modo
 * "SPA" do Sanctum, que exige backend e frontend no mesmo domínio.)
 */

const TOKEN_KEY = 'pdv.token'

// O navegador pode bloquear o sessionStorage (modo privado, por exemplo):
// por isso os acessos ficam protegidos com try/catch.
function readToken(): string | null {
  try {
    return sessionStorage.getItem(TOKEN_KEY)
  } catch {
    return null
  }
}

function writeToken(token: string | null) {
  try {
    if (token) sessionStorage.setItem(TOKEN_KEY, token)
    else sessionStorage.removeItem(TOKEN_KEY)
  } catch {
    // Sem armazenamento: o login vale só até recarregar a página.
  }
}

interface SessionValue {
  status: 'checking' | 'anonymous' | 'authenticated'
  user: User | null
  isManager: boolean
  // Mensagem para a tela de login (ex: "sua sessão expirou").
  notice: string | null
  login: (username: string, password: string) => Promise<void>
  logout: () => Promise<void>
}

const SessionContext = createContext<SessionValue | null>(null)

export function SessionProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient()
  // Começa em "checking" só se houver um login guardado para conferir.
  const [status, setStatus] = useState<SessionValue['status']>(() => (readToken() ? 'checking' : 'anonymous'))
  const [user, setUser] = useState<User | null>(null)
  const [notice, setNotice] = useState<string | null>(null)

  // Limpa tudo do usuário: token, dados em cache (vendas, produtos) e estado.
  const clearSession = useCallback(
    (message: string | null) => {
      setAuthToken(null)
      writeToken(null)
      queryClient.clear() // o próximo usuário não vê nada do anterior
      setUser(null)
      setNotice(message)
      setStatus('anonymous')
    },
    [queryClient],
  )

  useEffect(() => {
    // Se qualquer requisição responder 401, a sessão acabou: volta ao login.
    setUnauthorizedHandler(() => clearSession('Sua sessão expirou. Entre novamente.'))

    // Ao abrir o sistema: havia um login guardado? Confere se ainda vale.
    const saved = readToken()
    if (saved) {
      setAuthToken(saved)
      fetchMe()
        .then((me) => {
          setUser(me)
          setStatus('authenticated')
        })
        .catch(() => clearSession(null))
    }

    return () => setUnauthorizedHandler(null)
  }, [clearSession])

  async function login(username: string, password: string) {
    const result = await apiLogin(username, password) // erro sobe para a tela de login
    setAuthToken(result.token)
    writeToken(result.token)
    setUser(result.user)
    setNotice(null)
    setStatus('authenticated')
  }

  async function logout() {
    try {
      await apiLogout() // apaga o token no backend
    } catch {
      // Mesmo se a API falhar (ex: sem rede), sai do sistema neste computador.
    }
    clearSession(null)
  }

  const value: SessionValue = {
    status,
    user,
    isManager: user?.role === 'manager',
    notice,
    login,
    logout,
  }

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>
}

// Uso em qualquer tela: const { user, isManager, logout } = useSession()
// eslint-disable-next-line react/only-export-components
export function useSession(): SessionValue {
  const value = useContext(SessionContext)
  if (!value) throw new Error('useSession precisa estar dentro de <SessionProvider>')
  return value
}
