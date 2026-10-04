/*
 * Funções de formatação para mostrar na tela.
 *
 * Regra do projeto: no código o dinheiro é sempre CENTAVOS (inteiro). Só
 * aqui, na hora de exibir, ele vira texto "R$ 19,90". Assim nenhuma conta é
 * feita com número decimal, que o computador arredonda errado
 * (0.1 + 0.2 = 0.30000000000000004).
 */

// Intl.NumberFormat é o formatador de moeda nativo do navegador (sem biblioteca).
const currency = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })

// 1990 → "R$ 19,90"
export function formatCents(cents: number): string {
  return currency.format(cents / 100)
}

// "2026-10-03T21:15:00-03:00" → "03/10/2026 21:15"
export function formatDateTime(iso: string): string {
  const date = new Date(iso).toLocaleDateString('pt-BR')
  return `${date} ${formatTime(iso)}`
}

// "2026-10-03T21:15:00-03:00" → "21:15"
export function formatTime(iso: string): string {
  return new Date(iso).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
}

/*
 * Converte o que o operador digita no campo de dinheiro em centavos.
 * Funciona como a maquininha de cartão: os dígitos entram pela direita.
 *   "6"    → 6     (R$ 0,06)
 *   "600"  → 600   (R$ 6,00)
 *   "6000" → 6000  (R$ 60,00)
 * Qualquer caractere que não seja número é ignorado.
 */
export function digitsToCents(text: string): number {
  const digits = text.replace(/\D/g, '').slice(0, 9) // até R$ 9.999.999,99
  return digits === '' ? 0 : Number.parseInt(digits, 10)
}
