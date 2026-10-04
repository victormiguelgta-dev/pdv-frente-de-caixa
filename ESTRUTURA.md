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
│   ├── Services/                 ✅ passo 4  O "COZINHEIRO-CHEFE": as regras de negócio
│   │   └── SaleService.php                   recalcula total, confere troco, bloqueia
│   │                                         produto inativo, salva tudo junto
│   │
│   └── Http/
│       ├── Controllers/          ✅          C do MVC: os "garçons", recebem e repassam
│       │   ├── ProductController.php  ✅ passo 4  busca de produtos
│       │   └── SaleController.php     ✅ passo 4  criar venda, comprovante, vendas do dia
│       │
│       ├── Requests/             ✅ passo 4  O "SEGURANÇA DA PORTA": valida o que chega
│       │   └── StoreSaleRequest.php          ex: "itens é obrigatório", "quantidade ≥ 1"
│       │
│       └── Resources/            ✅ passo 4  O "EMPACOTADOR": define o formato do JSON
│           ├── ProductResource.php           que a API devolve (o que mostrar e o que
│           ├── SaleResource.php              esconder)
│           └── SaleItemResource.php
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
│   └── Feature/                  ✅ passo 5  testes automatizados de cada regra do teste
│       ├── SaleTest.php          ✅          20 testes: total, troco, preço registrado, inativo, estoque...
│       └── ProductSearchTest.php ✅          6 testes: busca por nome, código, inativos escondidos...
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

## Frontend (React + TypeScript) ✅ passo 6

```
frontend/
├── src/
│   ├── main.tsx                  ponto de entrada: liga o React e o TanStack Query
│   ├── App.tsx                   escolhe qual tela aparece (menu, venda, consulta)
│   ├── types.ts                  os "formatos" dos dados (Product, Sale...), iguais aos da API
│   ├── index.css                 todo o visual: cores por função, blocos grandes, celular
│   │
│   ├── api/                      ÚNICO lugar que conversa com o backend
│   │   ├── client.ts             fetch base: endereço da API, JSON, erros viram ApiError
│   │   ├── products.ts           searchProducts()
│   │   └── sales.ts              createSale(), getSale(), listTodaySales()
│   │
│   ├── screens/                  as 3 telas
│   │   ├── HomeScreen.tsx        menu com 2 blocos grandes: Nova venda (F2), Consultar vendas (F3)
│   │   ├── SaleScreen.tsx        busca + carrinho + pagamento + comprovante
│   │   └── SalesScreen.tsx       buscar venda pelo número + vendas de hoje + comprovante
│   │
│   ├── components/               pedaços reaproveitáveis das telas
│   │   ├── ProductSearch.tsx     busca por nome/código, leitor de código de barras, setas ↑↓
│   │   ├── Cart.tsx              itens, quantidade (− +), remover, total, botão finalizar
│   │   ├── PaymentDialog.tsx     forma de pagamento, valor recebido, troco / "faltam"
│   │   ├── Receipt.tsx           comprovante (com os dados que o backend salvou)
│   │   ├── StatusMessage.tsx     estados de carregando, erro (com "tentar de novo") e vazio
│   │   └── Icon.tsx              ícones SVG
│   │
│   ├── hooks/                    lógica reaproveitável
│   │   ├── useCart.ts            regras do carrinho (somar na mesma linha, limites, total)
│   │   ├── useDebounce.ts        espera parar de digitar antes de buscar
│   │   └── useHotkeys.ts         atalhos de teclado
│   │
│   └── utils/format.ts           centavos → "R$ 19,90", datas, máscara de dinheiro
│
├── .env.example                  VITE_API_URL (endereço da API)
└── package.json                  dependências: react, react-dom, @tanstack/react-query
```

### O caminho de uma venda pelo frontend

```
Operador digita "arroz"  → useDebounce espera 300 ms → useQuery chama GET /api/products?search=arroz
Clica no produto         → useCart.addProduct (soma 1 se já estiver no carrinho)
Aperta F4                → abre o PaymentDialog
Escolhe Dinheiro, R$ 60  → troco calculado na hora (prévia)
Confirma                 → useMutation chama POST /api/sales com SÓ { product_id, quantity }
                           ├── 201: mostra o Receipt com o que o BACKEND calculou
                           └── 422: erro de item → linha do carrinho em vermelho
                                    erro de valor → mensagem no campo "valor recebido"
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

## Decisões do passo 4 (API e regras de negócio)

| Decisão | Por quê |
|---|---|
| **O frontend envia só `product_id` e `quantity`** | Regra principal do teste: o cliente pode mandar qualquer coisa. Preço e total enviados pelo front são descartados (`validated()` só devolve os campos validados). |
| **Regras no `SaleService`, formato no `StoreSaleRequest`** | O Request confere o formato (tem itens? quantidade inteira?). O Service confere a regra (produto ativo? tem estoque? o dinheiro cobre?). O Controller só repassa. |
| **`DB::transaction`** | Venda, itens e baixa de estoque são salvos juntos. Se algo falhar, nada fica pela metade. |
| **`lockForUpdate`** | Dois caixas vendendo o último item ao mesmo tempo: o segundo espera e vê o estoque atualizado. |
| **Todos os erros de uma vez, com o campo exato** (`items.1.product_id`) | O operador vê tudo o que precisa corrigir, e o front sabe qual linha do carrinho marcar. |
| **Regras de negócio violadas viram 422** (o mesmo formato da validação) | O front trata todo erro de entrada do mesmo jeito. |
| **Busca só devolve produtos ativos, no máximo 20** | O inativo nem aparece para o operador (e o Service confere de novo). 20 resultados bastam para a tela. |
| **Produto sem estoque aparece na busca** | O operador vê que o produto existe, mas a tela pode mostrar "sem estoque". O Service bloqueia a venda. |
| **`throttle:120,1`** (120 requisições por minuto por IP) | Proteção básica contra abuso. 120 porque a busca roda enquanto o operador digita. |
| **Erros 404 e 429 em português, sem nomes internos** | O padrão do Laravel exporia o nome da classe (`App\Models\Sale`). |
| **Sem rotas de editar/apagar venda** (PUT/DELETE dão 405) | Regra do teste: venda finalizada não muda. |
| **Fuso horário `America/Sao_Paulo`** | Para "vendas do dia" e a hora do comprovante baterem com o relógio da loja. |
| **Resources em vez de devolver o Model** | Só sai no JSON o que está listado. Uma coluna nova não vaza sem ninguém perceber. |

## Decisões do passo 5 (testes automáticos)

Rodar: `cd backend && php artisan test`. São **26 testes**, que levam cerca de 1 segundo.

| Regra do enunciado | Testes que provam |
|---|---|
| Total e subtotais confiáveis | `calcula_total_e_troco_com_o_preco_do_banco`, `ignora_preco_e_total_enviados_pelo_frontend` |
| Preço registrado na venda | `preco_da_venda_nao_muda_quando_o_produto_muda_de_preco` |
| Venda tem 1 ou mais itens | `nao_aceita_venda_sem_itens`, `nao_aceita_quantidade_zero_negativa_ou_quebrada` |
| Dinheiro: recebido ≥ total, troco = diferença | `recusa_dinheiro_menor_que_o_total`, `aceita_dinheiro_exato_com_troco_zero`, `exige_valor_recebido_no_dinheiro` |
| Produto indisponível não entra | `recusa_produto_inativo`, `produto_inativo_nao_aparece_na_busca` |
| Venda finalizada não muda | `api_nao_tem_rota_para_editar_ou_apagar_venda`, `model_bloqueia_alterar_venda_finalizada` |
| Bônus: estoque | `da_baixa_no_estoque_ao_vender`, `recusa_quantidade_maior_que_o_estoque` |
| Bônus: histórico do dia | `historico_mostra_so_as_vendas_de_hoje` |

| Decisão | Por quê |
|---|---|
| **Testes de Feature** (chamam a API de verdade) | Testam o caminho inteiro (rota → validação → serviço → banco → JSON), do jeito que o frontend usa. |
| **Nomes dos testes em português, descrevendo a regra** | A lista de testes vira uma documentação das regras. |
| **Banco em memória e `RefreshDatabase`** | Cada teste começa do zero e não mexe no banco de desenvolvimento. |
| **Os testes conferem também o que NÃO aconteceu** (nada gravado, estoque intacto) | Uma venda recusada não pode deixar rastro pela metade. |
| **Removidos os `ExampleTest` do Laravel** | Eram exemplos vazios que não testavam nada do projeto. |
| **Validado com "sabotagem"** | Quebrei regras de propósito (aceitar inativo, troco +1 centavo) e os testes falharam, como deveriam. |

## Decisões do passo 6 (frontend)

| Decisão | Por quê |
|---|---|
| **Só o que o enunciado pede** | Buscar, carrinho, total, pagamento com troco e consultar venda (mais as vendas do dia, que ajudam a encontrar a venda a consultar). Nenhum botão sem função. |
| **Menu com blocos grandes e uma cor por função** | Qualquer pessoa entende sem treinamento. Azul = vender, verde = pagar/troco, roxo = consultar, vermelho = erro. |
| **Atalhos de teclado** (F2, F3, F4, Enter, Esc, setas) | Operador de caixa trabalha com teclado e leitor de código de barras. As teclas aparecem nos botões. |
| **Leitor de código de barras**: código exato + Enter já adiciona | O leitor "digita" o código e aperta Enter sozinho. |
| **O POST da venda envia só `product_id` e `quantity`** | O total do carrinho é só prévia. O comprovante mostra o que o backend calculou e salvou. |
| **TanStack Query** (a única biblioteca extra) | Controla carregando, erro e cache das buscas, que o enunciado avalia. Sem ele, esse controle seria escrito à mão em cada tela. |
| **Venda nunca é reenviada sozinha** (`mutations.retry: 0`) e o botão trava durante o envio | Evita venda duplicada por erro de rede ou duplo clique. |
| **Erros do backend vão para o lugar certo** | `items.1.product_id` marca a 2ª linha do carrinho em vermelho; `amount_received_cents` aparece no campo do valor; rede fora → mensagem com "Tentar novamente". O carrinho nunca se perde. |
| **Sem react-router** | São 3 telas: uma variável de estado resolve. Uma dependência a menos. |
| **Sem Tailwind e sem biblioteca de componentes** | CSS próprio, com as cores em variáveis. Menos dependências e nada que precise de explicação extra. |
| **`<dialog>` nativo para o pagamento** | O navegador já cuida de foco, fundo escuro e tecla Esc (acessibilidade). |
| **Dinheiro em centavos também no front**, com `Intl.NumberFormat` | Nenhuma conta com decimal. A máscara funciona como maquininha: 6-0-0-0 = R$ 60,00. |
| **TypeScript `strict`** | Checagem rigorosa de tipos: pega erros antes de rodar. |
| **`.env` com `VITE_API_URL`** | O endereço da API não fica fixo no código. Só vai o endereço público, nenhum segredo. |
| **Testado de ponta a ponta num navegador automatizado** | Busca, código de barras, quantidade, sem estoque, troco, "faltam", produto que ficou inativo, consulta, 404, API fora do ar e celular. |
