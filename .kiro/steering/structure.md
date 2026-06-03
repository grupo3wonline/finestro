# Project Structure

> Laravel 12 application with Service Layer, Repository Pattern, and clean architecture.

```
Finestro/
├── app/
│   ├── Actions/                 # Single-purpose action classes
│   ├── Console/                 # Artisan commands
│   ├── DTOs/                    # Data Transfer Objects
│   ├── Entities/                # Entity classes (getters/setters, magic methods)
│   ├── Exceptions/              # Custom exception classes
│   ├── Http/
│   │   ├── Controllers/         # Resource controllers (thin, delegates to services)
│   │   ├── Middleware/          # Custom middleware (ACL, locale, etc.)
│   │   ├── Requests/            # Form Request validation classes
│   │   └── Resources/           # API Resource transformers
│   ├── Interfaces/              # Contracts for repositories and services
│   │   ├── Repositories/        # Repository interfaces
│   │   └── Services/            # Service interfaces
│   ├── Models/                  # Eloquent models (UUID, SoftDeletes)
│   ├── Providers/               # Service providers (binding interfaces)
│   ├── Repositories/            # Repository implementations
│   ├── Services/                # Business logic services
│   └── Traits/                  # Shared traits (HasUuid, BelongsToUser, etc.)
├── bootstrap/                   # Framework bootstrap
├── config/                      # Configuration files
├── database/
│   ├── factories/               # Model factories
│   ├── migrations/              # Database migrations
│   ├── seeders/                 # Database seeders
│   └── sql/                     # Stored procedures, functions, triggers, views
├── lang/
│   └── pt_BR/                   # Portuguese translations
├── public/                      # Public assets, index.php
├── resources/
│   ├── css/                     # Tailwind source CSS
│   ├── js/                      # Alpine.js and app scripts
│   └── views/
│       ├── auth/                # Login, Register, Forgot Password, Verify Email
│       ├── components/          # Reusable Blade components
│       │   ├── ui/              # Buttons, modals, inputs, alerts, tooltips
│       │   ├── layout/          # Header, footer, sidebar, breadcrumb
│       │   └── cta/             # CTA sections
│       ├── dashboard/           # User dashboard
│       ├── errors/              # 404, 403, 500, 503 error pages
│       ├── landing/             # Public landing page (hotsite)
│       ├── pages/               # Static pages (Sobre, Serviços, Ferramentas, Contato)
│       ├── wallets/             # Wallet CRUD views
│       ├── transactions/        # Transaction CRUD views
│       ├── goals/               # Financial goals views
│       ├── simulators/          # Financial simulators views
│       ├── reports/             # Reports views
│       ├── settings/            # User settings views
│       └── layouts/             # Master layout templates
│           ├── app.blade.php    # Authenticated layout
│           ├── guest.blade.php  # Guest/public layout
│           └── landing.blade.php # Landing page layout
├── routes/
│   ├── web.php                  # Web routes
│   ├── auth.php                 # Auth routes (Breeze)
│   └── api.php                  # API routes (if needed)
├── storage/                     # Logs, cache, uploads
├── tests/
│   ├── Feature/                 # Feature tests
│   └── Unit/                    # Unit tests
├── .env                         # Environment variables (not committed)
├── .env.example                 # Template for env vars
├── composer.json                # PHP dependencies
├── package.json                 # Frontend dependencies
├── tailwind.config.js           # Tailwind configuration
├── vite.config.js               # Vite build config
└── phpunit.xml                  # Test configuration
```

## Key Conventions

- **Controllers** are thin — delegate all logic to Services
- **Services** contain business logic, injected via interfaces
- **Repositories** handle all database operations, injected via interfaces
- **DTOs** transfer data between layers (no raw arrays)
- **Actions** encapsulate single-purpose operations
- **Entities** represent domain objects with getters/setters and magic methods
- **Interfaces** define contracts for all repositories and services
- **Traits** share common functionality (HasUuid, BelongsToUser, Filterable)
- **Models** use UUID primary keys and SoftDeletes
- **Form Requests** handle all input validation
- **Resources** format API/JSON responses
- **Factories + Seeders** exist for every model
- Data access is ALWAYS scoped to authenticated user (ACL + query scoping)
- All bindings registered in ServiceProvider (interface → implementation)

## Dependency Flow

```
Controller → Service (via Interface) → Repository (via Interface) → Model → Database
     ↑              ↑                         ↑
  Request        DTO/Action              Eloquent Query (scoped by user_id)
```

## Pages Structure

### Public (Guest)
- Landing Page (hotsite) — Home
- Sobre (About)
- Serviços (Services)
- Ferramentas (Tools)
- Contato (Contact — with iframe form)

### Authenticated
- Dashboard
- Wallets (CRUD)
- Transactions (CRUD)
- Goals (CRUD)
- Simulators
- Reports
- Settings
