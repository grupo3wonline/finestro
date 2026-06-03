# Product: Finestro

Finestro is a personal finance management micro SaaS web application with a freemium model. Each user accesses ONLY their own data. The application uses ACL (Access Control List) for access control.

## Business Model

- **Free**: 1 wallet, 100 transactions/month, basic features
- **Padrão (Standard)**: Up to 3 wallets, unlimited transactions, simulators, up to 5 goals
- **Premium**: Unlimited wallets, goals, AI insights, exportable reports (PDF/CSV)

## Target Audience

Adults aged 20–50: freelancers, small business owners, and professionals seeking practical personal finance control.

## Core Features

- Authentication (username, email, password) via Laravel Breeze
- Email confirmation after registration
- Password reset flow
- ACL-based access control (each user sees only their data)
- Wallets (financial accounts) — CRUD
- Transactions (income/expenses) with receipt attachments — CRUD
- Recurring transactions
- Financial simulators (investment, retirement, budget)
- Financial goals with progress tracking
- Dashboard with charts and period comparisons
- Reports and CSV/PDF export (Premium)
- AI-powered insights and alerts (Premium)
- Landing page (hotsite) for conversion

## Public Pages

- **Home**: Landing page in hotsite format highlighting benefits, features, usage instructions, and tips
- **Sobre**: About page with company/product info
- **Serviços**: Services offered
- **Ferramentas**: Tools and utilities
- **Contato**: Contact page with iframe form

Each public page includes a CTA (Call to Action) with persuasive copy promoting Grupo 3W Online (www.3wonline.com.br) — a company that develops custom websites and systems on demand.

## Branding

- Footer on ALL pages: "Sistema desenvolvido por HOTWYL | WILLFROMBRASIL"
- Header and footer visible on every page
- Developer company: Grupo 3W Online (www.3wonline.com.br)

## Language

- **UI and content**: Brazilian Portuguese (pt-BR)
- **Code, comments, and technical documentation**: English

## Compliance

- LGPD (Lei Geral de Proteção de Dados) compliance
- Privacy policy page
- User consent mechanisms
- Data deletion capability
- Secure data handling

## Authentication Requirements

- Credentials: username, email, and password
- Email verification after registration
- Password reset via email
- Session-based auth (Laravel Breeze)
- Each user accesses ONLY their own records (enforced at query level)

## Database Design

- All tables use UUID as primary key
- SoftDeletes on all tables
- Proper indexes for performance
- Foreign keys with proper relationships
- Stored procedures, functions, triggers, and views as needed
- Normalized schema design
