# Product: Finestro

Finestro is a personal finance management micro SaaS web application with a freemium model. Each user accesses ONLY their own data. The application uses ACL (Access Control List) for access control.

## Business Model

Two plans: **Free** (`free`) and **Profissional** (`professional`). The plan is
derived from the user's subscription (`User::current_plan`). Limits live in
`User::freePlanLimits()` / `User::getPlanLimitsAttribute()` — that code is the
single source of truth; keep this table and the landing pricing in sync with it.

- **Free** (grátis, para sempre): 1 wallet, 100 transactions/month, 2 goals,
  5 categories, 3 budgets/month, dashboard summary, 7 financial calculators,
  profile tests, real-time currency quotes.
- **Profissional** (paid, monthly — price from `PlanPricingInterface`):
  everything unlimited (wallets, transactions, goals, categories, budgets),
  plus wallet sharing, 3 financial simulators, reports with charts + CSV/PDF
  export, 5 AI features, REST API access, and priority support.

Feature gating is enforced via plan limits and policies (e.g. `WalletPolicy`,
`ApiTokenService`, `AiService`); access falls back to Free when a subscription
is `past_due`/`unpaid`/`canceled` (see `App\Enums\SubscriptionStatus`).

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
- Reports and CSV/PDF export (Profissional)
- AI-powered insights and alerts (Profissional)
- REST API access with personal tokens (Profissional)
- Landing page (hotsite) for conversion

## Public Pages

The public site is a **single-page hotsite** (`landing/home.blade.php`). The
former standalone pages are now in-page sections reached via anchors; legacy
routes redirect to those anchors (see `PublicPagesTest`).

- **Home** (`#inicio`): hero highlighting benefits and the main sign-up CTA
- **Sobre** (`#sobre`): company/product info, vision/mission, tech stack
- **Serviços** (`#servicos`): services offered, with Free/Pro gating badges
- **Ferramentas** (`#ferramentas`): calculators, simulators, profile tests, AI
- **Planos** (`#planos`): pricing table (price from `PlanPricingInterface`)
- **Contato** (`#contato`): direct channels only — WhatsApp + e-mail, no form/iframe

The page includes a CTA (Call to Action) with persuasive copy promoting Grupo 3W Online (www.3wonline.com.br) — a company that develops custom websites and systems on demand.

## Branding

- Footer on ALL pages: "Sistema desenvolvido pelo desenvolvedor HOTWYL | WILLFROMBRASIL"
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
