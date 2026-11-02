<?php

declare(strict_types=1);

use App\Http\Controllers\AiController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PrivacySettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SimulatorController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TermsAcceptanceController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WalletSharingController;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [PageController::class, 'home'])->name('home');
// Legacy pages merged into the landing: keep old links/SEO with 301s.
Route::permanentRedirect('/sobre', '/#sobre')->name('about');
Route::permanentRedirect('/servicos', '/#servicos')->name('services');
Route::permanentRedirect('/ferramentas', '/#ferramentas')->name('tools');
Route::get('/contato', [PageController::class, 'contact'])->name('contact');
Route::get('/privacidade', [PageController::class, 'privacy'])->name('privacy');
Route::get('/termos', [PageController::class, 'terms'])->name('terms');
Route::get('/cookies', [PageController::class, 'cookies'])->name('cookies');

// Cookie consent (guests and users): single write path of the preferences.
Route::post('/consentimento', [ConsentController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('consent.store');
Route::get('/ajuda', [PageController::class, 'faq'])->name('faq');

// SEO — dynamic robots.txt and sitemap.xml (domain-aware)
Route::get('/robots.txt', [PageController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');
// GEO/AEO — llms.txt (llmstxt.org); a route so prices come from PlanPricing.
Route::get('/llms.txt', [PageController::class, 'llms'])->name('llms');

/*
|--------------------------------------------------------------------------
| Stripe Webhook (no auth, no CSRF)
|--------------------------------------------------------------------------
*/

Route::post('/stripe/webhook', [SubscriptionController::class, 'webhook'])
    ->middleware('throttle:60,1')
    ->name('stripe.webhook');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'password.fresh'])->group(function (): void {
    // Terms Acceptance (must be accessible without terms.accepted middleware)
    Route::get('/aceitar-termos', [TermsAcceptanceController::class, 'show'])->name('terms.accept');
    Route::post('/aceitar-termos', [TermsAcceptanceController::class, 'store'])->name('terms.store');
});

Route::middleware(['auth', 'verified', 'password.fresh', 'terms.accepted'])->group(function (): void {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Wallets
    Route::resource('wallets', WalletController::class);
    Route::get('/wallets/{wallet}/fatura', [WalletController::class, 'invoice'])->name('wallets.invoice');
    Route::post('/wallets/{wallet}/fatura/pagar', [WalletController::class, 'payInvoice'])
        ->middleware('throttle:10,1')
        ->name('wallets.invoice.pay');

    // Wallet sharing (invite / accept / revoke) — SCAFFOLD
    Route::post('/wallets/{wallet}/compartilhar', [WalletSharingController::class, 'store'])
        ->name('wallets.share');
    Route::patch('/convites/{member}/aceitar', [WalletSharingController::class, 'accept'])
        ->name('wallets.share.accept');
    Route::delete('/convites/{member}', [WalletSharingController::class, 'destroy'])
        ->name('wallets.share.destroy');

    // Transactions — rotas fixas ANTES do resource (senão "lixeira" vira {transaction})
    Route::get('/transactions/lixeira', [TransactionController::class, 'trash'])->name('transactions.trash');
    Route::patch('/transactions/{transaction}/restaurar', [TransactionController::class, 'restore'])->name('transactions.restore');
    Route::patch('/transactions/{transaction}/pagar', [TransactionController::class, 'pay'])->name('transactions.pay');
    Route::patch('/transactions/{transaction}/reabrir', [TransactionController::class, 'unpay'])->name('transactions.unpay');
    Route::get('/transactions/{transaction}/comprovante', [TransactionController::class, 'receipt'])->name('transactions.receipt');
    Route::resource('transactions', TransactionController::class);

    // Contas a pagar / receber
    Route::get('/contas', [BillController::class, 'index'])
        ->middleware('can:viewAny,App\Models\Transaction')
        ->name('bills.index');

    // Orçamentos mensais
    Route::prefix('orcamentos')->name('budgets.')->group(function (): void {
        Route::get('/', [BudgetController::class, 'index'])->name('index');
        Route::post('/', [BudgetController::class, 'store'])->name('store');
        Route::post('/copiar-mes-anterior', [BudgetController::class, 'copyPrevious'])->name('copy-previous');
        Route::put('/{budget}', [BudgetController::class, 'update'])->name('update');
        Route::delete('/{budget}', [BudgetController::class, 'destroy'])->name('destroy');
    });

    // Goals
    Route::resource('goals', GoalController::class);

    // Categories
    Route::post('/categories/importar-sugeridas', [CategoryController::class, 'importSuggested'])
        ->middleware('throttle:5,1')
        ->name('categories.import-suggested');
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Calculators (free for all users)
    Route::prefix('calculadoras')->name('calculators.')->group(function (): void {
        Route::get('/', [CalculatorController::class, 'index'])->name('index');
        Route::get('/investimento', [CalculatorController::class, 'investment'])->name('investment');
        Route::get('/juros', [CalculatorController::class, 'interest'])->name('interest');
        Route::get('/aposentadoria', [CalculatorController::class, 'retirement'])->name('retirement');
        Route::get('/decimo-terceiro', [CalculatorController::class, 'thirteenthSalary'])->name('thirteenth-salary');
        Route::get('/ferias', [CalculatorController::class, 'vacation'])->name('vacation');
        Route::get('/financiamento', [CalculatorController::class, 'financing'])->name('financing');
        Route::get('/cotacao-moedas', [CalculatorController::class, 'currency'])->name('currency');
    });

    // Simulators
    Route::prefix('simuladores')->name('simulators.')->group(function (): void {
        Route::get('/', [SimulatorController::class, 'index'])->name('index');
        Route::get('/investimento', [SimulatorController::class, 'investment'])->name('investment');
        Route::get('/aposentadoria', [SimulatorController::class, 'retirement'])->name('retirement');
        Route::get('/orcamento', [SimulatorController::class, 'budget'])->name('budget');
        Route::get('/perfil-financeiro', [SimulatorController::class, 'financialProfile'])->name('financial-profile');
        Route::get('/perfil-investidor', [SimulatorController::class, 'investorProfile'])->name('investor-profile');
    });

    // Reports
    Route::prefix('relatorios')->name('reports.')->middleware('permission:reports.view')->group(function (): void {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/exportar', [ReportController::class, 'export'])->middleware('permission:reports.export')->name('export');
        Route::get('/pdf', [ReportController::class, 'pdf'])->middleware('permission:reports.export')->name('pdf');
        Route::get('/imprimir', [ReportController::class, 'print'])->middleware('permission:reports.export')->name('print');
        Route::post('/enviar-email', [ReportController::class, 'email'])->middleware(['permission:reports.export', 'throttle:3,1'])->name('email');
    });

    // Subscription / Financial Menu
    Route::prefix('assinatura')->name('subscription.')->group(function (): void {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::get('/planos', [SubscriptionController::class, 'plans'])->name('plans');
        Route::post('/checkout', [SubscriptionController::class, 'checkout'])->middleware('throttle:5,1')->name('checkout');
        Route::get('/sucesso', [SubscriptionController::class, 'success'])->name('success');
        Route::post('/cancelar', [SubscriptionController::class, 'cancel'])->middleware('throttle:3,1')->name('cancel');
        Route::post('/retomar', [SubscriptionController::class, 'resume'])->middleware('throttle:3,1')->name('resume');
        Route::get('/teste', fn (): Factory|View => view('subscription.test-guide'))->name('test-guide');
    });

    // Settings
    Route::prefix('configuracoes')->name('settings.')->group(function (): void {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::get('/atividade', [SettingController::class, 'activity'])->name('activity');
        Route::put('/senha', [SettingController::class, 'updatePassword'])->name('password');
        Route::delete('/conta', [SettingController::class, 'deleteAccount'])->name('account.delete');

        // Privacy: data export, consent history, account deletion (LGPD/GDPR/CCPA/POPIA)
        Route::get('/privacidade', [PrivacySettingsController::class, 'index'])->name('privacy');
        Route::post('/privacidade/exportar', [PrivacySettingsController::class, 'export'])
            ->middleware('throttle:3,60')
            ->name('privacy.export');
        Route::get('/privacidade/exportacao/{export}', [PrivacySettingsController::class, 'download'])
            ->middleware('signed')
            ->name('privacy.download');

        // Personal API tokens (REST API v1)
        Route::get('/tokens', [ApiTokenController::class, 'index'])->name('tokens.index');
        Route::post('/tokens', [ApiTokenController::class, 'store'])->middleware('throttle:10,1')->name('tokens.store');
        Route::delete('/tokens/{tokenId}', [ApiTokenController::class, 'destroy'])->whereNumber('tokenId')->name('tokens.destroy');
    });

    // AI Insights (Professional plan only)
    Route::prefix('ia')->name('ai.')->middleware('permission:ai.use')->group(function (): void {
        Route::get('/', [AiController::class, 'index'])->name('index');
        Route::get('/configuracoes', [AiController::class, 'settings'])->name('settings');
        Route::post('/configuracoes', [AiController::class, 'saveCredentials'])->name('credentials.save');
        Route::post('/analisar-financas', [AiController::class, 'analyzeFinances'])->name('analyze.finances');
        Route::post('/analisar-metas', [AiController::class, 'analyzeGoals'])->name('analyze.goals');
        Route::post('/gerar-plano', [AiController::class, 'generatePlan'])->name('generate.plan');
        Route::post('/parecer-tecnico', [AiController::class, 'technicalReport'])->name('technical.report');
        Route::post('/planejamento', [AiController::class, 'financialPlanning'])->name('financial.planning');
    });

    // Profile (from Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
