# Tech Stack

## Backend

- **Framework**: Laravel 13 (PHP 8.5+)
- **Database**: MySQL 8
- **Auth**: Laravel Breeze (login, register, password reset, email confirmation)
- **Architecture**: Service Layer, Repository Pattern, DTO, Interfaces, Traits, Injeção de Dependências
- **Principles**: SOLID, Clean Code, DRY

## Frontend

- **Markup**: HTML5 + Blade Templates
- **Styling**: Tailwind CSS (mobile-first, responsive: sm, md, lg, xl, 2xl)
- **Behavior**: Alpine.js (reactivity, dark/light toggle, dropdowns, modals)
- **Icons**: Font Awesome
- **Alerts**: SweetAlert2
- **Approach**: Mobile-first, SEO-optimized, UI/UX best practices

## UI/UX Requirements

- Modern and professional layout with header and footer on all pages
- Breadcrumb navigation on all internal pages
- Tooltips and help buttons throughout the interface
- Dark/light theme toggle (persisted via localStorage)
- All forms: `autocomplete="off"` on inputs
- Descriptions, usage examples, tips, and guidance throughout the UI
- Error pages (404, 403, 500, 503) with proper styling
- Footer: "Sistema desenvolvido por HOTWYL | WILLFROMBRASIL"
- CTA sections with persuasive copy linking to www.3wonline.com.br (Grupo 3W Online)

## Security & Compliance

- ACL (Access Control List) for role-based access
- Each user accesses ONLY their own data (scoped queries via user_id)
- LGPD compliance (privacy policy, consent, data deletion)
- CSRF protection, input validation, parameterized queries
- UUID as primary keys, SoftDeletes on all models

## Performance

- Database indexes on frequently queried columns
- Eager loading to prevent N+1 queries
- Caching where appropriate
- Optimized assets (Vite build)

## SEO & Analytics

- Meta tags, Open Graph, Twitter Cards
- Sitemap.xml, robots.txt
- Semantic HTML structure
- Google Analytics, Meta Pixel ready

## Email

- Laravel built-in mail (SMTP configuration)
- Email confirmation after registration

## Hosting

- Any PHP hosting with MySQL support (e.g., shared hosting, VPS, or cloud)

## Common Commands

```bash
# Install PHP dependencies
composer install

# Install frontend dependencies
npm install

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Run dev server
php artisan serve

# Build frontend assets
npm run build

# Watch frontend assets (development)
npm run dev

# Run tests
php artisan test

# Clear caches
php artisan optimize:clear
```

## Conventions

- Use Blade templates with layouts (extends/section or components)
- Use Tailwind utility classes; avoid custom CSS unless absolutely necessary
- Use Alpine.js for all frontend interactivity (no jQuery)
- Use environment variables for all secrets (.env)
- All database access through Repository Pattern
- Business logic in Service Layer, never in Controllers
- DTOs for data transfer between layers
- Interfaces for all repositories and services
- Traits for shared functionality (e.g., HasUuid, BelongsToUser)
- Form Requests for validation
- API Resources for response formatting
- Factories and Seeders for all models
- Stored procedures, functions, triggers, and views in MySQL where needed
- All UI text in Portuguese (pt-BR); code and docs in English
