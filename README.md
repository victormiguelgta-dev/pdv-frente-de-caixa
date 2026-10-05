# PDV — Frente de Caixa

Frente de caixa simples: o operador busca produtos, monta o carrinho, finaliza a venda
(com cálculo de troco no dinheiro) e consulta o comprovante.

- **backend/**: API em Laravel 13 (PHP 8.3), banco SQLite
- **frontend/**: React 19 + TypeScript + Vite

O guia completo das pastas e das decisões tomadas está em [ESTRUTURA.md](ESTRUTURA.md).

## Rodando o backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed   # cria as tabelas e os produtos de exemplo
php artisan serve
```

A API sobe em `http://localhost:8000`. Para testar, abra `http://localhost:8000/api/products`.

## Rodando o frontend

Em outro terminal, com o backend ligado:

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

Abra `http://localhost:5173`.

**Atalhos:** no menu, F2 nova venda · F3 consultar vendas · F4 produtos. Na venda, F2 buscar · F4 pagamento · Enter confirmar · Esc voltar.

**Imprimir:** o comprovante tem o botão "Imprimir comprovante", que imprime só o cupom (80 mm), em impressora comum, térmica ou "Salvar como PDF".

> **Decisão consciente: não há login.** A tela de Produtos (cadastrar e mudar preço) fica aberta para qualquer pessoa que use o sistema. Num caixa real, ela ficaria protegida por login com perfil de gerente. É o próximo passo natural (o Laravel Sanctum já está instalado).

Para testar as regras pela tela: o **Azeite** está sem estoque, e os produtos "descontinuados" estão inativos (não aparecem na busca).

## Testes automáticos

```bash
cd backend
php artisan test
```

São 36 testes cobrindo todas as regras de negócio do enunciado e o cadastro de produtos. Rodam num banco em memória, sem mexer nos seus dados.

## Endpoints da API

| Método | Endereço | O que faz |
|---|---|---|
| GET | `/api/products?search=arroz` | busca produtos disponíveis por nome ou código (máx. 20) |
| POST | `/api/sales` | finaliza uma venda |
| GET | `/api/sales/{id}` | comprovante de uma venda |
| GET | `/api/sales` | vendas do dia |
| GET | `/api/catalog/products?search=` | cadastro: lista todos os produtos (inclusive inativos) |
| POST | `/api/catalog/products` | cadastro: cria um produto |
| PUT | `/api/catalog/products/{id}` | cadastro: edita nome, preço, estoque ou ativo/inativo |

Exemplo de venda (o frontend envia só produto e quantidade; preços e total são calculados no backend):

```json
POST /api/sales
{
  "items": [{ "product_id": 1, "quantity": 2 }],
  "payment_method": "cash",
  "amount_received_cents": 6000
}
```

Formas de pagamento: `cash` (dinheiro), `debit`, `credit`, `pix`. Valores sempre em centavos.
