import { useEffect, useRef } from 'react'

/*
 * Atalhos de teclado. Operador de caixa trabalha com o teclado (e o leitor
 * de código de barras), então as ações principais têm uma tecla.
 *
 * Uso:  useHotkeys({ F2: () => focarBusca(), Escape: () => voltar() })
 *
 * O nome da tecla é o "event.key" do navegador: 'F2', 'F4', 'Escape'...
 */
export function useHotkeys(handlers: Record<string, () => void>, enabled = true) {
  // useRef guarda sempre a versão mais nova das funções, sem precisar
  // remover e registrar o "ouvinte" de teclado a cada atualização da tela.
  const handlersRef = useRef(handlers)

  useEffect(() => {
    handlersRef.current = handlers
  })

  useEffect(() => {
    if (!enabled) return

    function onKeyDown(event: KeyboardEvent) {
      const handler = handlersRef.current[event.key]
      if (handler) {
        // Impede a ação padrão do navegador (ex: F3 abriria a busca da página).
        event.preventDefault()
        handler()
      }
    }

    window.addEventListener('keydown', onKeyDown)
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [enabled])
}
