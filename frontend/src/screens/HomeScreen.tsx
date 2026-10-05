import { Icon } from '../components/Icon'
import { useHotkeys } from '../hooks/useHotkeys'
import { useSession } from '../session'

/*
 * Tela inicial: blocos grandes, um para cada função do sistema.
 * Pensada para qualquer pessoa entender sem treinamento: ícone grande,
 * nome claro, uma frase explicando e a tecla de atalho.
 *
 * O bloco "Produtos" só aparece para o gerente.
 */

interface Props {
  onNewSale: () => void
  onSales: () => void
  onProducts: () => void
}

export function HomeScreen({ onNewSale, onSales, onProducts }: Props) {
  const { user, isManager, logout } = useSession()

  useHotkeys(isManager ? { F2: onNewSale, F3: onSales, F4: onProducts } : { F2: onNewSale, F3: onSales })

  return (
    <div className="home-screen">
      {/* Quem está logado e o botão de sair. */}
      <header className="userbar">
        <span>
          <strong>{user?.name}</strong> · {user?.role_label}
        </span>
        <button type="button" className="btn btn--outline" onClick={() => void logout()}>
          <Icon name="logout" size={18} /> Sair
        </button>
      </header>

      <main className="home">
        <button type="button" className="tile tile--primary" onClick={onNewSale}>
          <Icon name="cart" size={96} />
          <span className="tile__title">Nova venda</span>
          <span className="tile__hint">Buscar produtos, montar o carrinho e receber o pagamento</span>
          <kbd>F2</kbd>
        </button>

        <div className="home__side">
          <button type="button" className="tile tile--secondary" onClick={onSales}>
            <Icon name="receipt" size={56} />
            <span className="tile__title">Consultar vendas</span>
            <span className="tile__hint">Ver o comprovante de uma venda já finalizada</span>
            <kbd>F3</kbd>
          </button>

          {isManager && (
            <button type="button" className="tile tile--tertiary" onClick={onProducts}>
              <Icon name="box" size={56} />
              <span className="tile__title">Produtos</span>
              <span className="tile__hint">Cadastrar produtos, mudar preço e estoque</span>
              <kbd>F4</kbd>
            </button>
          )}
        </div>
      </main>
    </div>
  )
}
