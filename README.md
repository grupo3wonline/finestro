# Finestro

Micro SaaS de finanças pessoais para freelancers, pequenos empresários e
profissionais. Cada usuário vê apenas suas próprias carteiras, transações,
metas, categorias e orçamentos; o acesso é controlado por papéis e permissões
(ACL). A interface é em português do Brasil; código e documentação são em
inglês.

## Stack

| Camada | Tecnologia |
| --- | --- |
| Backend | Laravel 12, PHP 8.5 (restrição do Composer `^8.3`; o lock exige PHP 8.4+), MySQL 8 |
| Front end | Blade, Alpine.js, Tailwind CSS 3, Vite 5, Font Awesome, SweetAlert2 |
| Autenticação | Sessão do Laravel (controllers de origem Breeze, pacote removido), verificação de e-mail, redefinição de senha |
| ACL | spatie/laravel-permission (papéis `user`, `admin`) + policies, escopo de consulta por usuário (`BelongsToUser`) |
| Painel admin | Filament 5 em `/admin` (MFA obrigatório) |
| API | REST v1 em `/api/v1` com tokens pessoais Laravel Sanctum, docs OpenAPI via L5 Swagger em `/api/documentation` |
| Dados | DTOs spatie/laravel-data, Spatie Media Library (comprovantes), Spatie Activitylog, Laravel Excel, Scout, filas e cache |
| Cobrança | Stripe Checkout e webhooks |
| Proteção contra robôs | Google reCAPTCHA v3 e Cloudflare Turnstile nos formulários públicos (cada um ativo quando suas chaves estão no `.env`) |
| Qualidade | Pest 4 / PHPUnit 12, Pint, PHPStan nível 6 + Larastan, Rector |

Arquitetura: `Controller → Service (interface) → Repository (interface) → Model`,
com FormRequests para validação, DTOs entre camadas e bindings em
`app/Providers/AppServiceProvider.php`.

## Planos

| Plano | O que inclui |
| --- | --- |
| **Básico** (`free`) | 1 carteira, 100 transações/mês, 2 metas, 5 categorias, 3 orçamentos/mês, dashboard, calculadoras financeiras, testes de perfil, cotações de moedas |
| **Profissional** (`professional`) | Tudo ilimitado, compartilhamento de carteiras, simuladores, relatórios com exportação CSV/XLSX/PDF, recursos de IA, API REST, suporte prioritário |

Os limites ficam em `User::getPlanLimitsAttribute()`. Os preços não são
fixados no código: vêm das configurações `STRIPE_*` através do `PlanPricing`
(`App\Interfaces\Services\PlanPricingInterface`), e um teste falha se um
literal de preço aparecer em uma view.

## Configuração

Requisitos: PHP 8.3+ (8.5 usado em desenvolvimento) com `gd`, `zip`, `intl`;
Composer 2; MySQL 8; Node.js 20+.

```bash
composer setup          # composer install, .env, key, migrate, npm install, npm run build
php artisan db:seed     # dados de demonstração e papéis (opcional)
```

Edite o `.env` antes ou depois do `composer setup` (toda variável está
documentada em `.env.example`):

- `DB_*`, `APP_URL`, `MAIL_*`. Sessões, cache e fila usam por padrão o driver
  `database`. Em produção sobre HTTPS, defina `SESSION_SECURE_COOKIE=true`.
- E-mail (SMTP da HostGator): crie uma caixa de e-mail no cPanel e use o mesmo
  endereço em `MAIL_USERNAME` e `MAIL_FROM_ADDRESS`, com
  `MAIL_HOST=mail.<seu domínio>`, `MAIL_PORT=465` e `MAIL_SCHEME=smtps` (ou
  porta 587 com `MAIL_SCHEME` vazio). Ativação de conta, recuperação de senha
  e lembretes vão para a fila, então ela precisa ser processada (veja
  [Agendador e fila](#agendador-e-fila)).
- Proteção contra robôs: `RECAPTCHA_SITE_KEY` / `RECAPTCHA_SECRET_KEY` (Google
  reCAPTCHA v3, com `RECAPTCHA_MIN_SCORE`) e `TURNSTILE_SITE_KEY` /
  `TURNSTILE_SECRET_KEY` (Cloudflare Turnstile). Um provedor só fica ativo
  quando as duas chaves dele estão preenchidas; com os dois ativos, o
  formulário precisa passar nos dois.
- Limite de escritas: `WRITE_RATE_LIMIT_PER_MINUTE` (60 requisições POST/PUT/PATCH/DELETE por minuto por usuário logado; leituras não contam).
- Segurança da conta: `PASSWORD_MAX_AGE_DAYS` (90), `PASSWORD_WARNING_DAYS` (7),
  `RENEWAL_WARNING_DAYS` (7) e `AUTH_VERIFICATION_EXPIRE` (validade do link de
  ativação, em minutos; 1440).
- `STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY`, `STRIPE_WEBHOOK_SECRET`,
  `STRIPE_PROFESSIONAL_PRICE_ID`, `STRIPE_PROFESSIONAL_PRICE` (use chaves de
  teste fora de produção e nunca as versione). `php artisan stripe:setup`
  cria o produto e o preço e imprime o id do preço. Uma promoção opcional é
  configurada com `STRIPE_PROMO_*`.
- `LEGAL_CONTROLLER_NAME`, `LEGAL_CONTROLLER_CNPJ`, `LEGAL_CONTROLLER_ADDRESS`,
  `PRIVACY_DPO_EMAIL` (exibidos na política de privacidade). Incremente
  `LEGAL_POLICY_VERSION` sempre que os Termos de Uso ou a Política de
  Privacidade mudarem; todos os usuários serão então solicitados a aceitar
  novamente.
- API: `SANCTUM_TOKEN_EXPIRATION`, `API_RATE_LIMIT_PER_MINUTE`,
  `API_IP_RATE_LIMIT_PER_MINUTE`, `CORS_ALLOWED_ORIGINS`.
- Outras: `CACHE_USER_TTL`, `ACTIVITYLOG_RETENTION_DAYS`,
  `PRIVACY_EXPORT_TTL_HOURS`, `SCOUT_DRIVER` (`database` por padrão),
  `MARKETING_*` (links sociais e CTA do rodapé).
- Opcionais, condicionadas a consentimento: `GOOGLE_TAG_MANAGER_ID`,
  `GOOGLE_ANALYTICS_ID`, `FACEBOOK_PIXEL_ID`.

Conta de demonstração após o seed: usuário `demo`, e-mail
`demo@finestro.com.br`, senha `password`. Não faça o seed de dados de
demonstração em produção.

### Criando um administrador

O painel `/admin` admite apenas usuários verificados com a permissão
`admin.access`. Promova um usuário existente e verificado:

```bash
php artisan finestro:make-admin user@example.com
php artisan finestro:admin-mfa-reset user@example.com   # perdeu o autenticador: limpa o MFA, reinscreva no próximo login
```

## Operação

### Agendador e fila

A produção precisa de uma linha de cron para o agendador e uma forma de
processar a fila (e-mails, exportações de dados, conversões de imagem):

```bash
* * * * * cd /path/to/finestro && php artisan schedule:run >> /dev/null 2>&1
php artisan queue:work --tries=1          # VPS: mantenha rodando sob Supervisor
```

Em hospedagem compartilhada sem um worker de longa duração, defina
`QUEUE_RUN_VIA_SCHEDULER=true`. A mesma linha de cron então também executa
`queue:work --stop-when-empty --max-time=50` a cada minuto. Deixe em `false`
quando um worker já estiver rodando.

Tarefas agendadas (todas diárias, veja `routes/console.php`; os dois lembretes rodam às 09:00):

| Comando | Finalidade |
| --- | --- |
| `transactions:process-recurring` | Gera as próximas ocorrências de transações recorrentes (`--days-ahead=7`, `--dry-run`) |
| `activitylog:clean --force` | Remove entradas do log de atividades mais antigas que `ACTIVITYLOG_RETENTION_DAYS` |
| `sanctum:prune-expired --hours=24` | Exclui tokens de API expirados |
| `privacy:purge-exports` | Exclui arquivos de exportação de dados pessoais cujo link expirou |
| `security:remind-password-expiry` | Envia e-mail a quem tem a senha expirando em até 7 dias, uma vez por senha |
| `billing:remind-renewals` | Envia e-mail a assinantes Profissional cujo plano renova (ou termina) em até 7 dias, uma vez por período |

Comandos de manutenção manual: `receipts:import-media` (importa comprovantes
legados para a Media Library sem apagar os originais, com `--dry-run`
disponível), `stripe:setup`, `finestro:make-admin`, `finestro:admin-mfa-reset`.

### Deploy

Passos de release para um servidor onde você pode rodar Composer e npm:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan l5-swagger:generate          # spec OpenAPI para /api/documentation
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

O `composer install` também executa `filament:upgrade`, que publica os assets
do Filament em `public/`. Mantenha a linha de cron do agendador rodando.

O repositório inclui um workflow do GitHub Actions,
`.github/workflows/deploy.yml`, que envia o repositório clonado por FTP para
`finestro/` na conta HostGator a cada push em `main` ou `master` (e em pull
requests para esses branches). As credenciais vêm dos secrets do repositório
`FTP_HOST`, `FTP_USER` e `FTP_PASSWORD`. O workflow é deliberadamente apenas
FTP: não executa nenhuma etapa de Composer, npm ou build. `vendor/` e
`public/build/` são ignorados pelo git, então a pipeline nunca os toca;
atualize-os no servidor quando as dependências ou os assets de front-end
mudarem (passos de release acima).

Atualmente não há workflow de CI que rode as verificações de qualidade
(`.github/workflows/quality.yml` foi removido). Rode `composer quality`
localmente antes de fazer push.

## Comandos comuns

```bash
composer dev            # servidor + fila + logs + Vite
composer quality        # lint (pint --test), analyse (phpstan), refactor (rector --dry-run), test
composer test           # limpa caches do framework, depois php artisan test (Pest, SQLite em memória)
composer lint           # apenas verificação de estilo
composer format         # corrige o estilo do código com Pint
npm run build           # assets de produção
php artisan migrate     # migrations aditivas
php artisan optimize:clear
```

Os testes de carga usam k6 e são executados manualmente; veja [tests/load/README.md](tests/load/README.md).

## Como entrar

| Onde | Credenciais |
| --- | --- |
| App (`/login`) | **usuário + e-mail + senha** da mesma conta. Toda divergência recebe o mesmo erro genérico; 5 tentativas falhas por usuário + e-mail + IP bloqueiam o par por um minuto. A conta só fica **ativa depois da confirmação do e-mail** (o cadastro não abre sessão; contas não confirmadas não entram e podem pedir novo link em `/reenviar-confirmacao`). A senha expira a cada **90 dias**: um aviso aparece nos últimos 7 dias e, com a senha vencida, o usuário é levado a uma tela de troca obrigatória. |
| Painel admin (`/admin/login`) | E-mail + senha, depois **MFA obrigatório** (app autenticador TOTP), em um guard separado. Exige uma conta verificada, não suspensa, com a permissão `admin.access`. |
| API (`/api/v1`) | `Authorization: Bearer <token>` com um token pessoal Sanctum criado em **Configurações > Tokens de API** (plano Profissional). |

## Documentação

| Fase | Documento |
| --- | --- |
| 1 | [Auditoria de ACL](docs/security/phase-1-acl-audit.md) |
| 2 | [Cobrança Stripe](docs/billing/phase-2-stripe.md) |
| 3 | [Ferramentas de qualidade](docs/quality/phase-3-tooling.md) |
| 4 | [Pacotes e infraestrutura](docs/infrastructure/phase-4-packages.md) |
| 5 | [API REST](docs/api/phase-5-api.md) |
| 6 | [Painel admin Filament](docs/admin/phase-6-filament.md) |
| 6.5 | [Atualização de dependências](docs/security/phase-6-5-dependency-update.md) |
| 7 | [Conformidade de privacidade (LGPD, GDPR, CCPA, POPIA)](docs/compliance/phase-7-privacy.md) |
| 7 | [SEO, GEO, AEO, SEM e performance](docs/seo/phase-7-seo.md) |
| 7 | [Testes: crawler, fluxos, segurança, carga](docs/testing/phase-7-tests.md) |
| 7 | [Limpeza de órfãos](docs/maintenance/phase-7-cleanup.md) |
| 8 | [Proteção contra robôs, ativação por e-mail, validade de senha e lembretes](docs/security/phase-8-auth-hardening.md) |
| 9 | [Migração do Laravel 13 para o Laravel 12](docs/maintenance/laravel-12-downgrade.md) |
| 10 | [Auditoria e refatoração (convenções de interface, limites, limpeza)](docs/maintenance/audit-2026-11.md) |

Notas de integração anteriores: [docs/INTEGRACAO-SISTEMAS-LEGADOS.md](docs/INTEGRACAO-SISTEMAS-LEGADOS.md).

## Créditos

Sistema desenvolvido pelo desenvolvedor HOTWYL | WILLFROMBRASIL.
Desenvolvido por [Grupo 3W Online](https://www.3wonline.com.br), sites e sistemas sob demanda.
