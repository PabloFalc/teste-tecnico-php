# Minha API

API de vagas e candidaturas feita com Laravel.

## Como rodar

Requisitos: PHP, Composer e PostgreSQL.

1. Instale as dependências:

```bash
composer install
```

2. Crie o `.env` e gere a chave:

```bash
cp .env.example .env
php artisan key:generate
```

3. Crie o banco `app` no PostgreSQL e confira usuário e senha no `.env` (`DB_USERNAME`, `DB_PASSWORD`).

4. Crie as tabelas e popule o banco:

```bash
php artisan migrate:fresh --seed
```

5. Suba o servidor:

```bash
php artisan serve
```

A API fica em `http://127.0.0.1:8000`.

## Seed

O seed cria 20 candidatos, 2 recrutadores e 250 vagas. Cada candidato já vem candidatado a algumas vagas.

Recrutadores para login (senha de todos: `password`):

- `recrutador1@teste.com`
- `recrutador2@teste.com`

Os candidatos têm e-mails aleatórios e a mesma senha `password`.

Atenção: `migrate:fresh` apaga todos os dados do banco.
