import type { ReactNode } from 'react'
import { Icon } from './Icon'

/*
 * Os 3 "estados" de tela que se repetem em todo lugar que busca dados:
 * carregando, erro e vazio. Ficam aqui uma vez só e são reaproveitados,
 * para o sistema inteiro ter a mesma cara.
 */

// Carregando: aparece enquanto espera a resposta da API.
// role="status" avisa leitores de tela que algo está acontecendo.
export function Loading({ text = 'Carregando...' }: { text?: string }) {
  return (
    <div className="status" role="status">
      <span className="spinner" aria-hidden="true" />
      <span>{text}</span>
    </div>
  )
}

// Erro: mostra a mensagem e, se houver, um botão para tentar de novo.
// role="alert" faz o leitor de tela anunciar o erro na hora.
export function ErrorMessage({ message, onRetry }: { message: string; onRetry?: () => void }) {
  return (
    <div className="status status--error" role="alert">
      <Icon name="alert" size={32} />
      <span>{message}</span>
      {onRetry && (
        <button type="button" className="btn btn--outline" onClick={onRetry}>
          Tentar novamente
        </button>
      )}
    </div>
  )
}

// Vazio: deu certo, mas não há nada para mostrar. Explica o que fazer.
export function EmptyState({ icon, title, hint }: { icon: ReactNode; title: string; hint?: string }) {
  return (
    <div className="status status--empty">
      {icon}
      <strong>{title}</strong>
      {hint && <span>{hint}</span>}
    </div>
  )
}
