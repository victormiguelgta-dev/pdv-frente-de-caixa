# PDV — Frente de Caixa

Frente de caixa simples: o operador busca produtos, monta o carrinho, finaliza a venda
(com cálculo de troco no dinheiro) e consulta o comprovante.

- **backend/**: API em Laravel 13 (PHP 8.3), banco SQLite
- **frontend/**: React + TypeScript (em construção)

> README em construção. A versão final terá: como rodar, decisões tomadas e testes.

## Rodando o backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

A API sobe em `http://localhost:8000`. Para testar: `http://localhost:8000/api/ping`.
