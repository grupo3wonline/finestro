<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\Repositories\ActivityRepositoryInterface;
use App\Interfaces\Repositories\AdminBillingRepositoryInterface;
use App\Interfaces\Repositories\AdminUserRepositoryInterface;
use App\Interfaces\Repositories\AiCredentialRepositoryInterface;
use App\Interfaces\Repositories\ApiTokenRepositoryInterface;
use App\Interfaces\Repositories\BudgetRepositoryInterface;
use App\Interfaces\Repositories\CategoryRepositoryInterface;
use App\Interfaces\Repositories\CookieConsentRepositoryInterface;
use App\Interfaces\Repositories\DataExportRepositoryInterface;
use App\Interfaces\Repositories\FaqRepositoryInterface;
use App\Interfaces\Repositories\GoalRepositoryInterface;
use App\Interfaces\Repositories\PaymentHistoryRepositoryInterface;
use App\Interfaces\Repositories\PersonalDataRepositoryInterface;
use App\Interfaces\Repositories\StripeWebhookEventRepositoryInterface;
use App\Interfaces\Repositories\SubscriptionRepositoryInterface;
use App\Interfaces\Repositories\TransactionRepositoryInterface;
use App\Interfaces\Repositories\WalletRepositoryInterface;
use App\Interfaces\Services\AccountActivityServiceInterface;
use App\Interfaces\Services\AccountDeletionServiceInterface;
use App\Interfaces\Services\AdminMetricsServiceInterface;
use App\Interfaces\Services\AdminUserServiceInterface;
use App\Interfaces\Services\AiServiceInterface;
use App\Interfaces\Services\ApiTokenServiceInterface;
use App\Interfaces\Services\BudgetServiceInterface;
use App\Interfaces\Services\CategoryServiceInterface;
use App\Interfaces\Services\ConsentServiceInterface;
use App\Interfaces\Services\CreditCardInvoiceServiceInterface;
use App\Interfaces\Services\DataExportServiceInterface;
use App\Interfaces\Services\FaqServiceInterface;
use App\Interfaces\Services\GoalServiceInterface;
use App\Interfaces\Services\LegalConsentServiceInterface;
use App\Interfaces\Services\PlanPricingInterface;
use App\Interfaces\Services\ReceiptServiceInterface;
use App\Interfaces\Services\ReportServiceInterface;
use App\Interfaces\Services\SecurityActivityLoggerInterface;
use App\Interfaces\Services\StripeConfigCheckInterface;
use App\Interfaces\Services\StripeGatewayInterface;
use App\Interfaces\Services\StructuredDataServiceInterface;
use App\Interfaces\Services\SubscriptionServiceInterface;
use App\Interfaces\Services\TransactionServiceInterface;
use App\Interfaces\Services\UserCacheInterface;
use App\Interfaces\Services\WalletServiceInterface;
use App\Interfaces\Services\WalletSharingServiceInterface;
use App\Listeners\ClaimWalletInvitations;
use App\Listeners\LogAuthenticationActivity;
use App\Repositories\ActivityRepository;
use App\Repositories\AdminBillingRepository;
use App\Repositories\AdminUserRepository;
use App\Repositories\AiCredentialRepository;
use App\Repositories\ApiTokenRepository;
use App\Repositories\BudgetRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CookieConsentRepository;
use App\Repositories\DataExportRepository;
use App\Repositories\FaqRepository;
use App\Repositories\GoalRepository;
use App\Repositories\PaymentHistoryRepository;
use App\Repositories\PersonalDataRepository;
use App\Repositories\StripeWebhookEventRepository;
use App\Repositories\SubscriptionRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\WalletRepository;
use App\Services\AccountActivityService;
use App\Services\AccountDeletionService;
use App\Services\AdminMetricsService;
use App\Services\AdminUserService;
use App\Services\AiService;
use App\Services\ApiTokenService;
use App\Services\BudgetService;
use App\Services\CategoryService;
use App\Services\ConsentService;
use App\Services\CreditCardInvoiceService;
use App\Services\DataExportService;
use App\Services\FaqService;
use App\Services\GoalService;
use App\Services\LegalConsentService;
use App\Services\PlanPricing;
use App\Services\ReceiptService;
use App\Services\ReportService;
use App\Services\SecurityActivityLogger;
use App\Services\StripeConfigCheck;
use App\Services\StripeGateway;
use App\Services\StructuredDataService;
use App\Services\SubscriptionService;
use App\Services\TransactionService;
use App\Services\UserCache;
use App\Services\WalletService;
use App\Services\WalletSharingService;
use App\Support\Scout\SubqueryDatabaseEngine;
use Illuminate\Auth\Events\Verified;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(WalletRepositoryInterface::class, WalletRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
        $this->app->bind(GoalRepositoryInterface::class, GoalRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(BudgetRepositoryInterface::class, BudgetRepository::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, SubscriptionRepository::class);
        $this->app->bind(PaymentHistoryRepositoryInterface::class, PaymentHistoryRepository::class);
        $this->app->bind(StripeWebhookEventRepositoryInterface::class, StripeWebhookEventRepository::class);
        $this->app->bind(FaqRepositoryInterface::class, FaqRepository::class);
        $this->app->bind(AiCredentialRepositoryInterface::class, AiCredentialRepository::class);
        $this->app->bind(ActivityRepositoryInterface::class, ActivityRepository::class);
        $this->app->bind(ApiTokenRepositoryInterface::class, ApiTokenRepository::class);
        $this->app->bind(AdminUserRepositoryInterface::class, AdminUserRepository::class);
        $this->app->bind(AdminBillingRepositoryInterface::class, AdminBillingRepository::class);
        $this->app->bind(CookieConsentRepositoryInterface::class, CookieConsentRepository::class);
        $this->app->bind(DataExportRepositoryInterface::class, DataExportRepository::class);
        $this->app->bind(PersonalDataRepositoryInterface::class, PersonalDataRepository::class);

        // Service bindings
        $this->app->bind(WalletServiceInterface::class, WalletService::class);
        $this->app->bind(WalletSharingServiceInterface::class, WalletSharingService::class);
        $this->app->bind(TransactionServiceInterface::class, TransactionService::class);
        $this->app->bind(GoalServiceInterface::class, GoalService::class);
        $this->app->bind(CategoryServiceInterface::class, CategoryService::class);
        $this->app->bind(BudgetServiceInterface::class, BudgetService::class);
        $this->app->bind(CreditCardInvoiceServiceInterface::class, CreditCardInvoiceService::class);
        $this->app->bind(AiServiceInterface::class, AiService::class);
        $this->app->bind(PlanPricingInterface::class, PlanPricing::class);
        $this->app->bind(StripeGatewayInterface::class, StripeGateway::class);
        $this->app->bind(SubscriptionServiceInterface::class, SubscriptionService::class);
        $this->app->bind(FaqServiceInterface::class, FaqService::class);
        $this->app->bind(AccountDeletionServiceInterface::class, AccountDeletionService::class);
        $this->app->bind(SecurityActivityLoggerInterface::class, SecurityActivityLogger::class);
        $this->app->bind(LegalConsentServiceInterface::class, LegalConsentService::class);
        $this->app->bind(AccountActivityServiceInterface::class, AccountActivityService::class);
        $this->app->bind(ReceiptServiceInterface::class, ReceiptService::class);
        $this->app->bind(UserCacheInterface::class, UserCache::class);
        $this->app->bind(ReportServiceInterface::class, ReportService::class);
        $this->app->bind(StripeConfigCheckInterface::class, StripeConfigCheck::class);
        $this->app->bind(ApiTokenServiceInterface::class, ApiTokenService::class);
        $this->app->bind(AdminMetricsServiceInterface::class, AdminMetricsService::class);
        $this->app->bind(AdminUserServiceInterface::class, AdminUserService::class);
        $this->app->bind(ConsentServiceInterface::class, ConsentService::class);
        $this->app->bind(DataExportServiceInterface::class, DataExportService::class);
        $this->app->bind(StructuredDataServiceInterface::class, StructuredDataService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Em produção, força todas as URLs geradas a usarem HTTPS. Isso evita
        // conteúdo misto (mixed content) e garante que links/assets/redirects
        // não caiam em http mesmo atrás de proxies/balanceadores.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Warn (once a day) when production runs with a Stripe test key.
        if ($this->app->isProduction()) {
            $this->app->make(StripeConfigCheckInterface::class)->reportIfMisconfigured();
        }

        // REST API quota, keyed per user (not per token): creating more tokens
        // never multiplies the limit. It runs after auth:sanctum, so a user is
        // always present; guests are capped earlier by the `api-ip` limiter.
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute((int) config('api.rate_limit_per_minute'))
            ->by('api:'.$request->user()?->getAuthIdentifier()));

        // Per-IP quota applied before authentication (ThrottleApiByIp).
        RateLimiter::for('api-ip', fn (Request $request): Limit => Limit::perMinute((int) config('api.ip_rate_limit_per_minute'))
            ->by('api-ip:'.$request->ip()));

        // Scout `database` driver that can expose its search query, so
        // repositories filter with an SQL subquery instead of an id list.
        $this->app->make(EngineManager::class)->extend('database', fn (): SubqueryDatabaseEngine => new SubqueryDatabaseEngine);

        // Claim pending wallet invitations once the invitee's email is verified.
        // Event auto-discovery is disabled, so the listener is registered here.
        Event::listen(Verified::class, ClaimWalletInvitations::class);

        // Auth and role events go to the `security` activity log.
        Event::subscribe(LogAuthenticationActivity::class);
    }
}
