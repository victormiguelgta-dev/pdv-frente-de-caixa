/*
 * Ícones desenhados em SVG direto no código (traços no estilo "Lucide").
 * Usar SVG próprio evita instalar uma biblioteca inteira de ícones para
 * usar meia dúzia deles.
 *
 * aria-hidden: o ícone é só decoração. Quem usa leitor de tela ouve o texto
 * do botão, não o desenho.
 */

const paths = {
  cart: 'M2 3h3l2.6 12.4a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 7H6 M9 21h.01 M18 21h.01',
  receipt: 'M5 2v20l3-2 2 2 2-2 2 2 2-2 3 2V2l-3 2-2-2-2 2-2-2-2 2-3-2Z M9 8h6 M9 12h6 M9 16h4',
  search: 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z M21 21l-4.3-4.3',
  cash: 'M2 6h20v12H2z M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z M6 12h.01 M18 12h.01',
  card: 'M2 5h20v14H2z M2 10h20 M6 15h4',
  pix: 'M12 2l4 4-4 4-4-4 4-4Z M12 14l4 4-4 4-4-4 4-4Z M2 12l4-4 4 4-4 4-4-4Z M14 12l4-4 4 4-4 4-4-4Z',
  trash: 'M3 6h18 M8 6V4h8v2 M19 6l-1 14H6L5 6 M10 11v6 M14 11v6',
  plus: 'M12 5v14 M5 12h14',
  minus: 'M5 12h14',
  back: 'M19 12H5 M12 19l-7-7 7-7',
  alert: 'M12 9v4 M12 17h.01 M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z',
  check: 'M20 6 9 17l-5-5',
} as const

export type IconName = keyof typeof paths

export function Icon({ name, size = 24 }: { name: IconName; size?: number }) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
    >
      <path d={paths[name]} />
    </svg>
  )
}
