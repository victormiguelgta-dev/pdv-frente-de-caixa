import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import App from './App.tsx'
import './index.css'
import { SessionProvider } from './session'

/*
 * Ponto de entrada do frontend: liga o React à página (index.html).
 *
 * QueryClient é o "gerente" do TanStack Query: guarda o cache das buscas
 * e as regras de repetição. Envolver o App no QueryClientProvider deixa
 * qualquer componente usar useQuery e useMutation.
 */
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1, // se uma BUSCA falhar, tenta mais 1 vez antes de mostrar o erro
      refetchOnWindowFocus: false, // não refaz a busca só porque trocou de janela
    },
    mutations: {
      retry: 0, // NUNCA repete o envio de uma venda sozinho (evita venda duplicada)
    },
  },
})

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      {/* SessionProvider: deixa o usuário logado disponível para todas as telas. */}
      <SessionProvider>
        <App />
      </SessionProvider>
    </QueryClientProvider>
  </StrictMode>,
)
