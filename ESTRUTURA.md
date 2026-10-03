# Guia da estrutura do projeto

Mapa de estudo: o que é cada pasta, para que serve e em qual passo ela entra.

- ✅ = já existe
- 🔜 = vai ser criado no passo indicado

---

## Visão geral

```
pdv-frente-de-caixa/
├── backend/          ✅ API em Laravel (PHP): guarda os dados e aplica as regras
├── frontend/         🔜 tela do operador em React + TypeScript (domingo)
├── .gitignore        ✅ lista do que NÃO vai para o GitHub (segredos, dependências)
├── README.md         ✅ como rodar o projeto (versão final no último passo)
└── ESTRUTURA.md      ✅ este arquivo
```

**Por que backend e frontend no mesmo repositório?** O avaliador baixa um único projeto
e encontra tudo. Cada parte fica na sua pasta e roda de forma independente.

---

## Backend (Laravel)

```
backend/
├── app/                          ← O CÓDIGO DO SISTEMA (é aqui que mais trabalhamos)
│   ├── Enums/                    ✅ passo 2  Listas fixas de opções
│   │   └── PaymentMethod.php     ✅          dinheiro, débito, crédito, pix
│   │
│   ├── Models/                   ✅          M do MVC: a "despensa", uma classe por tabela
│   │   ├── Concerns/
│   │   │   └── Immutable.php     ✅ passo 2  regra "venda finalizada não muda" (usada por Sale e SaleItem)
│   │   ├── User.php              ✅          operador do caixa (já vem com o Laravel)
│   │   ├── Product.php           ✅ passo 2  produto
│   │   ├── Sale.php              ✅ passo 2  venda (tem muitos itens)
│   │   └── SaleItem.php          ✅ passo 2  item da venda ("fotografia" do produto)
│   │
│   ├── Services/                 🔜 passo 5  O "COZINHEIRO-CHEFE": as regras de negócio
│   │   └── SaleService.php                   recalcula total, confere troco, bloqueia
│   │                                         produto inativo, salva tudo junto
│   │
│   └── Http/
│       ├── Controllers/          ✅          C do MVC: os "garçons", recebem e repassam
│       │   ├── ProductController.php  🔜 passo 4  busca de produtos
│       │   └── SaleController.php     🔜 passo 4  criar e consultar venda
│       │
│       ├── Requests/             🔜 passo 4  O "SEGURANÇA DA PORTA": valida o que chega
│       │   └── StoreSaleRequest.php          ex: "itens é obrigatório", "quantidade ≥ 1"
│       │
│       └── Resources/            🔜 passo 4  O "EMPACOTADOR": define o formato do JSON
│           ├── ProductResource.php           que a API devolve (o que mostrar e o que
│           └── SaleResource.php              esconder)
│
├── database/
│   ├── migrations/               ✅          "plantas" das tabelas (criar products, sales...)
│   ├── seeders/                  ✅          massa de dados de exemplo
│   │   ├── DatabaseSeeder.php    ✅ passo 3  o seeder principal: chama os outros
│   │   └── ProductSeeder.php     ✅ passo 3  31 produtos de mercadinho (2 inativos, 1 sem estoque)
│   ├── factories/                ✅          fábricas de dados falsos, usadas nos testes
│   │   └── ProductFactory.php    ✅ passo 3  produto aleatório + variações inactive() e outOfStock()
│   └── database.sqlite           ✅          o banco em si: um arquivo só (não vai pro Git)
│
├── routes/
│   ├── api.php                   ✅          o "cardápio" da API: endereço → controller
│   └── web.php                   ✅          só avisa que a API está no ar
│
├── tests/
│   └── Feature/                  🔜 passo 6  testes automatizados de cada regra do teste
│
├── config/                       ✅          configurações (cors.php = quem pode chamar a API)
├── bootstrap/app.php             ✅          liga tudo: rotas, middlewares, erros em JSON
├── public/index.php              ✅          porta de entrada: toda requisição passa aqui
├── storage/                      ✅          logs e cache gerados pelo Laravel
├── vendor/                       ✅          bibliotecas baixadas pelo Composer (não vai pro Git)
├── .env                          ✅          SEGREDOS desta máquina (não vai pro Git)
├── .env.example                  ✅          modelo do .env sem segredos (vai pro Git)
├── composer.json                 ✅          lista de bibliotecas PHP (igual ao package.json do JS)
├── phpunit.xml                   ✅          configuração dos testes
└── artisan                       ✅          a "ferramenta" de linha de comando do Laravel
```

### O caminho de uma venda pelo backend

```
React manda:  POST /api/sales  { items: [{product_id: 3, quantity: 2}], payment_method: "cash", ... }
                 │
                 ▼
 routes/api.php              "POST /sales? Isso é com o SaleController"
                 │
                 ▼
 StoreSaleRequest            confere o formato: tem itens? quantidade é número ≥ 1?
                 │           se não → devolve erro 422 e PARA aqui
                 ▼
 SaleController              não decide nada, só chama o serviço
                 │
                 ▼
 SaleService                 busca o preço REAL no banco (ignora qualquer preço do front)
                 │           recusa produto inativo, calcula subtotais, total e troco
                 │           salva venda + itens de uma vez (se algo falhar, nada é salvo)
                 ▼
 Models (Sale, SaleItem)     gravam no banco
                 │
                 ▼
 SaleResource                monta o JSON de resposta (o comprovante)
                 │
                 ▼
React recebe:  201 Created  { id: 15, total: 1980, change: 20, items: [...] }
```

### Por que dividir em tantas peças?

Cada peça tem **uma responsabilidade só**. Quando algo dá errado, você sabe onde procurar:

| Problema | Onde olhar |
|---|---|
| "A API aceitou quantidade negativa" | `Requests/` (validação) |
| "O troco saiu errado" | `Services/SaleService.php` (regra) |
| "O JSON está mostrando um campo que não devia" | `Resources/` (formato da resposta) |
| "A rota não existe" | `routes/api.php` |

---

## Frontend (React + TypeScript) 🔜 domingo

A estrutura do frontend vai ser definida a partir do layout que você vai trazer.
A ideia geral:

```
frontend/src/
├── api/          funções que conversam com o backend (um lugar só para o endereço da API)
├── components/   pedaços da tela: busca, carrinho, pagamento, comprovante
├── hooks/        lógica reaproveitável (ex: o carrinho)
├── types/        os "formatos" dos dados em TypeScript (Product, Sale...)
└── utils/        funções pequenas (ex: formatar centavos em "R$ 19,90")
```

---

## Decisões já tomadas no passo 1

| Decisão | Por quê |
|---|---|
| **Laravel só como API** (removidos Vite, CSS, JS e a página de boas-vindas) | Quem desenha a tela é o React. Arquivo que não é usado só confunde quem revisa. |
| **SQLite** | O banco é um arquivo só, então o avaliador roda sem instalar MySQL. Trocar para MySQL é só mudar o `.env`. |
| **Sanctum instalado** (`php artisan install:api`) | Cria o `routes/api.php` e prepara o login por token (bônus). |
| **CORS restrito** ao endereço do front (`FRONTEND_URL`) | Só o nosso React pode chamar a API pelo navegador. Com `*`, qualquer site poderia. |
| **CORS só com GET e POST** | Não existe editar nem apagar venda, então não há motivo para liberar PUT/DELETE. |
| **Erros sempre em JSON** nas rotas `/api` | O front sempre recebe um formato que consegue ler, nunca uma página HTML de erro. |
| **`.env` fora do Git** e `.env.example` dentro | O `.env` tem a `APP_KEY` (chave secreta). O `.env.example` mostra quais variáveis existem, sem valores secretos. |

## Decisões do passo 2 (banco de dados)

```
products                  sales                         sale_items
──────────────            ──────────────────            ──────────────────────
id                        id  ◄──────────────────────── sale_id
code (único)              payment_method                product_id ──► products.id
name                      total_cents                   product_code     ┐
price_cents               amount_received_cents         product_name     │ "fotografia"
active                    change_cents                  unit_price_cents ┘
stock                     created_at (= hora da venda)  quantity
                                                        subtotal_cents
```

| Decisão | Por quê |
|---|---|
| **Dinheiro em centavos, número inteiro** (`1990` = R$ 19,90) | Computador erra conta com decimal (`0.1 + 0.2 = 0.30000000000000004`). Com inteiro, a conta é sempre exata. |
| **`sale_items` copia código, nome e preço** | Regra do teste: o preço da venda fica registrado mesmo se o produto mudar depois. |
| **Produto tem `active`, e não é apagado** | Regra do teste: indisponível não entra em venda nova. Apagar quebraria o histórico das vendas antigas. |
| **Sem venda "em aberto" no banco** | O carrinho vive na tela. A venda só é gravada já finalizada, então não precisa de coluna de status, e `created_at` é a hora da venda. |
| **`restrictOnDelete` nas chaves estrangeiras** | O próprio banco impede apagar venda com itens, e produto que já foi vendido. |
| **Trait `Immutable` em Sale e SaleItem** | Segunda proteção da regra "venda finalizada não muda": qualquer `update` ou `delete` pelo Model dá erro. A primeira proteção é a API não ter rota para isso. |
| **Enum `PaymentMethod`** | Lista fechada de formas de pagamento num lugar só, sem textos soltos digitados errado. |
| **Troco gravado na venda** | Daria para recalcular, mas o comprovante deve mostrar exatamente o que foi devolvido no dia. |

## Decisões do passo 3 (dados de exemplo)

| Decisão | Por quê |
|---|---|
| **31 produtos fixos e realistas** no `ProductSeeder` | O teste pede massa de dados para testar sem digitar. Nomes de mercado ajudam quem avalia a usar a tela. |
| **Casos especiais de propósito**: 2 inativos, 1 sem estoque, preços quebrados (R$ 0,75, R$ 4,99) | Quem avalia consegue testar as regras na tela sem precisar mexer no banco. |
| **`updateOrCreate` pelo código** | Rodar o seeder duas vezes não duplica nada: atualiza o que já existe. |
| **Seeder ≠ Factory** | Seeder = dados fixos, para pessoas testarem na tela. Factory = dados aleatórios, para os testes automáticos criarem o cenário de que precisam. |
| **Removido o usuário de teste padrão** do `DatabaseSeeder` | Ainda não há login. Se fizermos o bônus, o operador de exemplo entra aqui. |
