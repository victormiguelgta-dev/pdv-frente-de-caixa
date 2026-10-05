# Roadmap — PDV Frente de Caixa

Mapa do que o sistema **já tem**, do que um PDV de verdade **costuma ter** e do que **ainda falta**.
Legenda: ✅ feito · 🔜 próximo passo · 💡 ideia para o futuro · 🚫 fora do escopo (de propósito)

---

## 1. O que o sistema já tem ✅

### Pedido no teste
| Funcionalidade | Onde |
|---|---|
| ✅ Buscar produto por nome ou código (inclusive leitor de código de barras) | tela Nova venda |
| ✅ Carrinho: adicionar, mudar quantidade, remover, limpar | tela Nova venda |
| ✅ Total calculado na hora (prévia) e recalculado pelo backend (oficial) | Nova venda / `SaleService` |
| ✅ Formas de pagamento: dinheiro, débito, crédito, pix | janela de pagamento |
| ✅ Dinheiro: valor recebido, troco, "faltam R$ X" | janela de pagamento |
| ✅ Comprovante da venda | fim da venda e Consultar vendas |
| ✅ Todas as regras de negócio, com testes automáticos | `backend/tests` |

### Bônus do teste
| Bônus | Situação |
|---|---|
| ✅ Testes automatizados | 45 testes |
| ✅ Histórico de vendas do dia | Consultar vendas |
| ✅ Cuidados com estoque | baixa ao vender, bloqueio sem estoque, trava para dois caixas |
| ✅ Login/autenticação | operador e gerente (seção 2) |

### Além do pedido (para parecer um caixa real)
| Funcionalidade | Situação |
|---|---|
| ✅ Cadastro de produtos (criar, editar, ativar/desativar) | tela Produtos |
| ✅ Imprimir comprovante (cupom 80 mm) | botão no comprovante |
| ✅ Atalhos de teclado e telas de blocos grandes | todo o sistema |

---

## 2. Login com perfis ✅ (feito)

Dois perfis com credenciais diferentes (usuários de exemplo no README).

| Perfil | Pode fazer | Não pode |
|---|---|---|
| **Operador de caixa** | vender, consultar vendas, imprimir comprovante | entrar em Produtos, mudar preço ou estoque |
| **Gerente** | tudo do operador + cadastro de produtos e estoque | — |

O que foi feito:

**Backend**
- [x] Coluna `role` (perfil) na tabela `users`: `operator` ou `manager`
- [x] Rota de login (`POST /api/login`) e de sair (`POST /api/logout`) com **Laravel Sanctum** (já instalado)
- [x] Todas as rotas exigem login (`auth:sanctum`)
- [x] Rotas de Produtos (`/api/catalog/...`) exigem perfil **gerente** → senão responde **403**
- [x] Venda registra **quem vendeu** (`sales.user_id`) e o comprovante mostra o nome do operador
- [x] Seeder com 2 usuários de exemplo (operador e gerente), senhas no README
- [x] Limite de tentativas de login (contra adivinhação de senha)
- [x] Testes: sem login → 401; operador em Produtos → 403; gerente em Produtos → ok; venda grava o operador

**Frontend**
- [x] Tela de login (e-mail e senha), simples e grande como o resto
- [x] Guardar o token e enviá-lo em toda requisição (`Authorization: Bearer ...`)
- [x] Bloco **Produtos** só aparece para o gerente
- [x] Nome do usuário e botão **Sair** no topo
- [x] Se o token expirar (401), voltar para a tela de login


---

## 3. O que um PDV de verdade costuma ter

### 3.1 Operação do caixa
| Funcionalidade | O que é | Situação |
|---|---|---|
| Abertura de caixa | operador informa o dinheiro inicial (fundo de troco) ao começar o turno | 💡 |
| Fechamento de caixa | ao fim do turno: total por forma de pagamento, conferência do dinheiro na gaveta | 💡 |
| Sangria | retirada de dinheiro da gaveta durante o dia (por segurança), com registro | 💡 |
| Suprimento | colocar mais dinheiro de troco na gaveta, com registro | 💡 |
| Relatório do turno | resumo do que o operador vendeu | 💡 (base: histórico do dia) |

### 3.2 Vendas
| Funcionalidade | O que é | Situação |
|---|---|---|
| Remover item antes de finalizar | | ✅ |
| Multiplicador (`3*` + código = 3 unidades) | agiliza produto repetido | 💡 |
| Consulta de preço sem vender | cliente pergunta "quanto custa?" | ✅ (a busca já mostra) |
| Desconto | por item ou na venda, **com senha de gerente** | 💡 (o login já existe) |
| Pagamento dividido | parte em dinheiro, parte no cartão | 💡 |
| Cancelamento/estorno de venda | **não altera a venda**: cria um registro de estorno, com senha de gerente | 💡 (cuidado com a regra "venda não se altera") |
| Troca e devolução | devolve o item ao estoque e gera crédito | 💡 |
| Produto por peso (balança) | quilo de frutas, frios | 💡 |

### 3.3 Produtos e estoque
| Funcionalidade | O que é | Situação |
|---|---|---|
| Cadastro de produtos | | ✅ (só gerente) |
| Desativar sem apagar | | ✅ |
| Histórico de movimentação de estoque | quem mudou, quando, quanto entrou ou saiu | 💡 |
| Entrada de mercadoria | registrar a chegada de produtos (nota do fornecedor) | 💡 |
| Estoque mínimo / alerta | avisar quando o produto está acabando | 💡 |
| Categorias e unidade (un, kg, cx) | organizar o catálogo | 💡 |

### 3.4 Relatórios
| Funcionalidade | Situação |
|---|---|
| Vendas do dia | ✅ |
| Vendas por período (semana, mês) | 💡 |
| Total por forma de pagamento | 💡 |
| Produtos mais vendidos | 💡 |

### 3.5 Coisas de PDV real que NÃO fazem sentido neste teste 🚫
| Funcionalidade | Por que fica de fora |
|---|---|
| Nota fiscal (NFC-e / SAT), CPF na nota | exige certificado digital e integração com a Sefaz; nada a ver com o teste |
| TEF / maquininha integrada | exige contrato e SDK de adquirente (Cielo, Stone...) |
| Gaveta de dinheiro e impressora térmica direta (ESC/POS) | depende de hardware; a impressão pelo navegador resolve |
| Funcionar sem internet (offline) | complexo (sincronização); fora do escopo de 1 a 5 dias |
| Cadastro de clientes, fidelidade, delivery, mesas, comandas | são outros sistemas (CRM, restaurante), não frente de caixa |

---

## 4. Regras que nunca podem ser quebradas (do enunciado)

Qualquer funcionalidade nova precisa respeitar:
1. O total é sempre calculado pelo **backend**.
2. O preço da venda fica **registrado** (não muda depois).
3. Venda tem **1 ou mais itens**.
4. Dinheiro: recebido **≥ total**; troco = diferença.
5. Produto **inativo não entra** em venda nova.
6. Venda finalizada **não é alterada** (cancelamento, se existir, é um registro novo de estorno).

---

## 5. Ordem sugerida para continuar

| # | O quê | Por quê | Tempo |
|---|---|---|---|
| ✅ | ~~Login com perfis (operador e gerente)~~ | feito | — |
| 1 | **Relatório de vendas por período, imprimível (só gerente)** | pedido para a próxima etapa | 1–2 h |
| 2 | Revisar o projeto e treinar as respostas para a entrevista | o mais importante para segunda | 1 h |
| 3 | Abertura e fechamento de caixa | o recurso mais "caixa de verdade" que falta | 3–4 h |
| 4 | Desconto com senha de gerente | usa o login; muito comum em PDV | 2 h |
| 5 | Histórico de movimentação de estoque | auditoria: quem mexeu no estoque | 2 h |
