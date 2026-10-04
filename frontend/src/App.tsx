import { useState } from 'react'
import { HomeScreen } from './screens/HomeScreen'
import { SaleScreen } from './screens/SaleScreen'
import { SalesScreen } from './screens/SalesScreen'

/*
 * Componente raiz: decide qual tela aparece.
 *
 * São só 3 telas, então uma variável de estado resolve, sem precisar de
 * uma biblioteca de rotas (react-router). Uma dependência a menos para
 * instalar, manter e explicar.
 */

type Screen = 'home' | 'sale' | 'sales'

export default function App() {
  const [screen, setScreen] = useState<Screen>('home')

  const goHome = () => setScreen('home')

  if (screen === 'sale') return <SaleScreen onExit={goHome} />
  if (screen === 'sales') return <SalesScreen onExit={goHome} />

  return <HomeScreen onNewSale={() => setScreen('sale')} onSales={() => setScreen('sales')} />
}
