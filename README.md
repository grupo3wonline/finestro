# Finestro

Gestão financeira pessoal inteligente — Micro SaaS com modelo freemium.

## Stack

- **Backend:** Laravel 13 + PHP 8.5 + MySQL 8
- **Frontend:** Blade + Tailwind CSS + Alpine.js
- **Auth:** Laravel Breeze (login, registro, reset de senha, verificação de e-mail)
- **Ícones:** Font Awesome 6
- **Alertas:** SweetAlert2
- **Arquitetura:** Service Layer, Repository Pattern, DTO, Interfaces, Traits, Injeção de Dependências

## Requisitos

- PHP 8.2+
- Composer 2+
- MySQL 8+
- Node.js 20+ (para build de assets)

## Instalação

```bash
# Clonar repositório
git clone <repo-url> finestro
cd finestro

# Instalar dependências PHP
composer install

# Instalar dependências frontend
npm install

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Configurar banco de dados no .env
# DB_DATABASE=finestro
# DB_USERNAME=root
# DB_PASSWORD=

# Executar migrations e seeders
php artisan migrate --seed

# Build dos assets
npm run build

# Iniciar servidor
php artisan serve
```

## Usuário Demo

- **E-mail:** demo@finestro.com.br
- **Senha:** password

## Funcionalidades

- ✅ Autenticação completa (registro, login, reset de senha, verificação de e-mail)
- ✅ Carteiras financeiras (CRUD)
- ✅ Transações (receitas/despesas com categorias)
- ✅ Metas financeiras com progresso visual
- ✅ Dashboard com resumo financeiro
- ✅ Landing page (hotsite) com SEO
- ✅ Páginas públicas (Sobre, Serviços, Ferramentas, Contato)
- ✅ Tema claro/escuro (persistido via localStorage)
- ✅ Interface responsiva (mobile-first)
- ✅ Breadcrumb em todas as páginas internas
- ✅ Tooltips e botões de ajuda
- ✅ Conformidade LGPD (política de privacidade)
- ✅ Páginas de erro estilizadas (404, 403, 500, 503)
- ✅ ACL por usuário (cada usuário acessa apenas seus dados)
- ✅ UUID como chave primária
- ✅ SoftDeletes em todos os modelos

## Arquitetura

```
Controller → Service (via Interface) → Repository (via Interface) → Model → Database
```

## Desenvolvido por

**HOTWYL | WILLFROMBRASIL**

[Grupo 3W Online](https://www.3wonline.com.br) — Sites e Sistemas Personalizados
