<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
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
Route::get('/privacidade', [PageController::class, 'privacy'])->name('privacy');
Route::get('/termos', [PageController::class, 'terms'])->name('terms');

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

    // Simulators
    Route::prefix('simuladores')->name('simulators.')->group(function () {
        Route::get('/', [SimulatorController::class, 'index'])->name('index');
        Route::get('/investimento', [SimulatorController::class, 'investment'])->name('investment');
        Route::get('/aposentadoria', [SimulatorController::class, 'retirement'])->name('retirement');
        Route::get('/orcamento', [SimulatorController::class, 'budget'])->name('budget');
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
    });

    // Profile (from Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
