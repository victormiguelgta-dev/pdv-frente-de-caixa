import { useState } from 'react'
import { HomeScreen } from './screens/HomeScreen'
import { ProductsScreen } from './screens/ProductsScreen'
import { SaleScreen } from './screens/SaleScreen'
import { SalesScreen } from './screens/SalesScreen'

/*
 * Componente raiz: decide qual tela aparece.
 *
 * São só 4 telas, então uma variável de estado resolve, sem precisar de
 * uma biblioteca de rotas (react-router). Uma dependência a menos para
 * instalar, manter e explicar.
 */

type Screen = 'home' | 'sale' | 'sales' | 'products'

export default function App() {
  const [screen, setScreen] = useState<Screen>('home')

  const goHome = () => setScreen('home')

  if (screen === 'sale') return <SaleScreen onExit={goHome} />
  if (screen === 'sales') return <SalesScreen onExit={goHome} />
  if (screen === 'products') return <ProductsScreen onExit={goHome} />

  return (
    <HomeScreen
      onNewSale={() => setScreen('sale')}
      onSales={() => setScreen('sales')}
      onProducts={() => setScreen('products')}
    />
  )
}
