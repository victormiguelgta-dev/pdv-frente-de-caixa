import { Icon } from './Icon'

/*
 * Botão "Imprimir comprovante".
 *
 * window.print() abre a janela de impressão do navegador (impressora comum,
 * impressora térmica de caixa ou "Salvar como PDF"). Quem decide O QUE sai no
 * papel é o CSS de impressão no fim do index.css (@media print): ele esconde a
 * tela inteira e deixa só o comprovante, no formato de cupom de 80 mm.
 */
export function PrintButton() {
  return (
    <button type="button" className="btn btn--outline btn--lg" onClick={() => window.print()}>
      <Icon name="printer" size={22} /> Imprimir comprovante
    </button>
  )
}
