import { useEffect, useState } from 'react'

/*
 * "Debounce": espera o usuário PARAR de digitar antes de usar o valor.
 *
 * Sem isso, digitar "arroz" faria 5 buscas na API (a, ar, arr, arro, arroz).
 * Com debounce de 300 ms, só a última é feita. Isso economiza requisições
 * e respeita o limite por minuto do backend.
 */
export function useDebounce<T>(value: T, delayMs = 300): T {
  const [debounced, setDebounced] = useState(value)

  useEffect(() => {
    // A cada nova tecla, o timer anterior é cancelado e um novo começa.
    const timer = setTimeout(() => setDebounced(value), delayMs)
    return () => clearTimeout(timer)
  }, [value, delayMs])

  return debounced
}
