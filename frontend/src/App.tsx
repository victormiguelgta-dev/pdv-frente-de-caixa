import { useState } from 'react'
import { Loading } from './components/StatusMessage'
import { HomeScreen } from './screens/HomeScreen'
import { LoginScreen } from './screens/LoginScreen'
import { ProductsScreen } from './screens/ProductsScreen'
import { SaleScreen } from './screens/SaleScreen'
import { SalesScreen } from './screens/SalesScreen'
import { useSession } from './session'

/*
 * Componente raiz: decide qual tela aparece.
 *
 * Primeiro vem o login: sem usuário logado, só a tela de login existe.
 * Depois, são 4 telas, e uma variável de estado resolve, sem precisar de uma
 * biblioteca de rotas (react-router). Uma dependência a menos.
 */

type Screen = 'home' | 'sale' | 'sales' | 'products'

export default function App() {
  const { status, isManager } = useSession()
  const [screen, setScreen] = useState<Screen>('home')

  const goHome = () => setScreen('home')

  // Conferindo se o login guardado ainda vale (ao abrir o sistema).
  if (status === 'checking') return <Loading text="Abrindo o sistema..." />

  if (status === 'anonymous') return <LoginScreen />

  if (screen === 'sale') return <SaleScreen onExit={goHome} />
  if (screen === 'sales') return <SalesScreen onExit={goHome} />
  // Produtos só para o gerente. (O backend também bloqueia: isto é só a tela.)
  if (screen === 'products' && isManager) return <ProductsScreen onExit={goHome} />

  return (
    <HomeScreen
      onNewSale={() => setScreen('sale')}
      onSales={() => setScreen('sales')}
      onProducts={() => setScreen('products')}
    />
  )
}
