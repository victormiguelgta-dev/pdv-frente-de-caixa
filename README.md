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

### Usuários de exemplo

| Usuário | Senha | Perfil | Pode |
|---|---|---|---|
| `caixa` | `Caixa@2026` | Operador de caixa | vender, consultar vendas, imprimir |
| `gerente` | `Gerente@2026` | Gerente | tudo do operador + cadastro de produtos |

> São senhas de **demonstração**, criadas pelo seeder. A permissão é garantida no **backend**: o operador recebe **403** mesmo chamando a API de produtos direto.

Para testar as regras pela tela: o **Azeite** está sem estoque, e os produtos "descontinuados" estão inativos (não aparecem na busca).

## Testes automáticos

```bash
cd backend
php artisan test
```

São 49 testes cobrindo as regras de negócio do enunciado, o cadastro de produtos, o login, as permissões e as proteções de segurança. Rodam num banco em memória, sem mexer nos seus dados.

## Segurança

O que o projeto já faz:

| Proteção | Como |
|---|---|
| Valor da venda garantido pelo servidor | o front envia só produto e quantidade; preço e total vêm do banco |
| Login com token (Laravel Sanctum) | token vale 12 h; logout apaga o token no servidor; tokens vencidos são limpos diariamente |
| Permissões no backend | cadastro de produtos só para gerente (403 para o operador, mesmo chamando a API direto) |
| Senhas | criptografadas com bcrypt; nunca saem em nenhuma resposta |
| Força bruta | máximo de 5 tentativas de login por minuto; mesma mensagem e mesmo tempo de resposta para "usuário não existe" e "senha errada" |
| Abuso da API | máximo de 120 requisições por minuto por IP |
| CORS | só o endereço do frontend (`FRONTEND_URL`) pode chamar a API pelo navegador |
| Validação | toda entrada é validada; campos extras são descartados; consultas com parâmetros (sem SQL Injection) |
| Cabeçalhos de segurança | `nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Cache-Control: no-store` em todas as respostas, inclusive erros |
| Segredos | `.env` fora do Git; só o `.env.example` (sem segredos) é versionado |
| Dependências | `composer audit` e `npm audit` sem vulnerabilidades conhecidas |

**Checklist para colocar em produção** (não se aplica a rodar localmente):
- [ ] `APP_ENV=production` e `APP_DEBUG=false` no `.env` (erros sem detalhes internos)
- [ ] HTTPS obrigatório (o token trafega no cabeçalho)
- [ ] `FRONTEND_URL` com o domínio real do frontend
- [ ] Criar usuários reais e **não** rodar o `UserSeeder` (as senhas dele são de demonstração)
- [ ] Rodar o agendador (`php artisan schedule:run` no cron) para limpar tokens vencidos
- [ ] Banco com backup (MySQL/PostgreSQL no lugar do SQLite, se houver vários caixas)

## Endpoints da API

Todas as rotas, menos o login, exigem o token no cabeçalho `Authorization: Bearer <token>`.

| Método | Endereço | O que faz |
|---|---|---|
| POST | `/api/login` | entra com `username` e `password` e devolve o token (máx. 5 tentativas por minuto) |
| GET | `/api/me` | usuário logado |
| POST | `/api/logout` | sai (apaga o token) |
| GET | `/api/products?search=arroz` | busca produtos disponíveis por nome ou código (máx. 20) |
| POST | `/api/sales` | finaliza uma venda |
| GET | `/api/sales/{id}` | comprovante de uma venda |
| GET | `/api/sales` | vendas do dia |
| GET | `/api/catalog/products?search=` | **só gerente** · cadastro: lista todos os produtos (inclusive inativos) |
| POST | `/api/catalog/products` | **só gerente** · cadastro: cria um produto |
| PUT | `/api/catalog/products/{id}` | **só gerente** · cadastro: edita nome, preço, estoque ou ativo/inativo |

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
