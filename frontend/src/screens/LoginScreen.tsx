import { useState, type FormEvent } from 'react'
import { ApiError } from '../api/client'
import { Icon } from '../components/Icon'
import { useSession } from '../session'

/*
 * Tela de login: usuário e senha, grandes e simples, como o resto do sistema.
 */
export function LoginScreen() {
  const { login, notice } = useSession()
  const [username, setUsername] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setError(null)
    setSubmitting(true)
    try {
      await login(username.trim(), password)
    } catch (err) {
      // 422 = usuário/senha errados; 429 = tentativas demais; 0 = sem conexão.
      if (err instanceof ApiError) {
        const fieldMessage = Object.values(err.fieldErrors)[0]?.[0]
        setError(fieldMessage ?? err.message)
      } else {
        setError('Não foi possível entrar. Tente novamente.')
      }
      setPassword('') // senha errada: limpa para digitar de novo
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main className="login">
      <form className="login__card" onSubmit={handleSubmit} noValidate>
        <div className="login__brand">
          <Icon name="cart" size={48} />
          <h1>PDV Frente de Caixa</h1>
          <p>Entre com o seu usuário para começar</p>
        </div>

        {(error ?? notice) && (
          <p className="alert" role="alert">
            <Icon name="alert" size={20} /> {error ?? notice}
          </p>
        )}

        <label className="field">
          <span>Usuário</span>
          <input
            value={username}
            onChange={(event) => setUsername(event.target.value)}
            autoComplete="username"
            autoCapitalize="none"
            autoFocus
            disabled={submitting}
          />
        </label>

        <label className="field">
          <span>Senha</span>
          <input
            type="password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            autoComplete="current-password"
            disabled={submitting}
          />
        </label>

        <button
          type="submit"
          className="btn btn--primary btn--xl"
          disabled={submitting || username.trim() === '' || password === ''}
        >
          {submitting ? 'Entrando...' : 'Entrar'}
        </button>
      </form>
    </main>
  )
}
