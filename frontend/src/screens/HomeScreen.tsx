import { Icon } from '../components/Icon'
import { useHotkeys } from '../hooks/useHotkeys'

/*
 * Tela inicial: dois blocos grandes, um para cada função do sistema.
 * Pensada para qualquer pessoa entender sem treinamento: ícone grande,
 * nome claro, uma frase explicando e a tecla de atalho.
 */

interface Props {
  onNewSale: () => void
  onSales: () => void
}

export function HomeScreen({ onNewSale, onSales }: Props) {
  useHotkeys({ F2: onNewSale, F3: onSales })

  return (
    <main className="home">
      <button type="button" className="tile tile--primary" onClick={onNewSale}>
        <Icon name="cart" size={96} />
        <span className="tile__title">Nova venda</span>
        <span className="tile__hint">Buscar produtos, montar o carrinho e receber o pagamento</span>
        <kbd>F2</kbd>
      </button>

      <button type="button" className="tile tile--secondary" onClick={onSales}>
        <Icon name="receipt" size={72} />
        <span className="tile__title">Consultar vendas</span>
        <span className="tile__hint">Ver o comprovante de uma venda já finalizada</span>
        <kbd>F3</kbd>
      </button>
    </main>
  )
}
