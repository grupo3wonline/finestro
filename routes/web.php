<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\SimulatorController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/sobre', [PageController::class, 'about'])->name('about');
Route::get('/servicos', [PageController::class, 'services'])->name('services');
Route::get('/ferramentas', [PageController::class, 'tools'])->name('tools');
Route::get('/contato', [PageController::class, 'contact'])->name('contact');
Route::post('/contato', [PageController::class, 'sendContact'])
    ->middleware('throttle:5,1')
    ->name('contact.send');
Route::get('/privacidade', [PageController::class, 'privacy'])->name('privacy');
Route::get('/termos', [PageController::class, 'terms'])->name('terms');

// SEO — dynamic robots.txt and sitemap.xml (domain-aware)
Route::get('/robots.txt', [PageController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');

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

Route::middleware(['auth', 'verified'])->group(function () {
    // Terms Acceptance (must be accessible without terms.accepted middleware)
    Route::get('/aceitar-termos', fn () => view('auth.accept-terms'))->name('terms.accept');
    Route::post('/aceitar-termos', function (\Illuminate\Http\Request $request) {
        $request->validate(['terms' => ['required', 'accepted']], [
            'terms.required' => 'Você deve aceitar os Termos de Uso.',
            'terms.accepted' => 'Você deve aceitar os Termos de Uso.',
        ]);
        $request->user()->update(['terms_accepted_at' => now()]);
        return redirect()->route('dashboard')->with('success', 'Termos aceitos com sucesso! Bem-vindo ao Finestro.');
    })->name('terms.store');
});

Route::middleware(['auth', 'verified', 'terms.accepted'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Wallets
    Route::resource('wallets', WalletController::class);

    // Transactions
    Route::resource('transactions', TransactionController::class);

    // Goals
    Route::resource('goals', GoalController::class);

    // Categories
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Calculators (free for all users)
    Route::prefix('calculadoras')->name('calculators.')->group(function () {
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
    Route::prefix('simuladores')->name('simulators.')->group(function () {
        Route::get('/', [SimulatorController::class, 'index'])->name('index');
        Route::get('/investimento', [SimulatorController::class, 'investment'])->name('investment');
        Route::get('/aposentadoria', [SimulatorController::class, 'retirement'])->name('retirement');
        Route::get('/orcamento', [SimulatorController::class, 'budget'])->name('budget');
        Route::get('/perfil-financeiro', [SimulatorController::class, 'financialProfile'])->name('financial-profile');
        Route::get('/perfil-investidor', [SimulatorController::class, 'investorProfile'])->name('investor-profile');
    });

    // Reports
    Route::prefix('relatorios')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/exportar', [ReportController::class, 'export'])->name('export');
    });

    // Subscription / Financial Menu
    Route::prefix('assinatura')->name('subscription.')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::get('/planos', [SubscriptionController::class, 'plans'])->name('plans');
        Route::post('/checkout', [SubscriptionController::class, 'checkout'])->middleware('throttle:5,1')->name('checkout');
        Route::get('/sucesso', [SubscriptionController::class, 'success'])->name('success');
        Route::post('/cancelar', [SubscriptionController::class, 'cancel'])->middleware('throttle:3,1')->name('cancel');
        Route::get('/teste', fn () => view('subscription.test-guide'))->name('test-guide');
    });

    // Settings
    Route::prefix('configuracoes')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::put('/senha', [SettingController::class, 'updatePassword'])->name('password');
        Route::delete('/conta', [SettingController::class, 'deleteAccount'])->name('account.delete');
    });

    // AI Insights (Professional plan only)
    Route::prefix('ia')->name('ai.')->group(function () {
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

require __DIR__ . '/auth.php';
