<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BudgetController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\GoalController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Middleware\ThrottleApiByIp;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
|
| Token-only REST API (Laravel Sanctum). The API is a Professional-plan
| feature, so every route sits behind:
|   - ThrottleApiByIp per-IP rate limit (`api-ip`), runs BEFORE authentication
|                    so missing/invalid tokens are capped as well
|   - auth:sanctum   authenticate the personal access token
|   - active         EnsureAccountIsActive: 403 forbidden for suspended accounts
|   - verified       the account's e-mail must be confirmed
|   - throttle:api   per-user rate limit (keyed after auth, see AppServiceProvider)
|   - api.plan       EnsureApiAccess: 403 plan_upgrade_required without `api_access`
|
| Default URL prefix is `api`; this group adds `v1`, so routes live under
| `/api/v1/...` and are named `api.v1.*`. Individual routes further restrict
| token scope with the `abilities:` middleware. Ids are UUIDs (whereUuid):
| anything else is a 404 before any query.
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware([ThrottleApiByIp::class, 'auth:sanctum', 'active', 'verified', 'throttle:api', 'api.plan'])
    ->group(function (): void {
        // Account
        Route::get('auth/me', [AuthController::class, 'me'])->middleware('abilities:account:read')->name('auth.me');
        Route::get('subscription', [SubscriptionController::class, 'show'])->middleware('abilities:account:read')->name('subscription.show');

        // Wallets
        Route::get('wallets', [WalletController::class, 'index'])->middleware('abilities:wallets:read')->name('wallets.index');
        Route::post('wallets', [WalletController::class, 'store'])->middleware('abilities:wallets:write')->name('wallets.store');
        Route::get('wallets/{wallet}', [WalletController::class, 'show'])->middleware('abilities:wallets:read')->whereUuid('wallet')->name('wallets.show');
        Route::match(['put', 'patch'], 'wallets/{wallet}', [WalletController::class, 'update'])->middleware('abilities:wallets:write')->whereUuid('wallet')->name('wallets.update');
        Route::delete('wallets/{wallet}', [WalletController::class, 'destroy'])->middleware('abilities:wallets:write')->whereUuid('wallet')->name('wallets.destroy');

        // Transactions
        Route::get('transactions', [TransactionController::class, 'index'])->middleware('abilities:transactions:read')->name('transactions.index');
        Route::post('transactions', [TransactionController::class, 'store'])->middleware('abilities:transactions:write')->name('transactions.store');
        Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->middleware('abilities:transactions:read')->whereUuid('transaction')->name('transactions.show');
        Route::match(['put', 'patch'], 'transactions/{transaction}', [TransactionController::class, 'update'])->middleware('abilities:transactions:write')->whereUuid('transaction')->name('transactions.update');
        Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->middleware('abilities:transactions:write')->whereUuid('transaction')->name('transactions.destroy');
        Route::patch('transactions/{transaction}/pay', [TransactionController::class, 'pay'])->middleware('abilities:transactions:write')->whereUuid('transaction')->name('transactions.pay');
        Route::patch('transactions/{transaction}/unpay', [TransactionController::class, 'unpay'])->middleware('abilities:transactions:write')->whereUuid('transaction')->name('transactions.unpay');
        Route::get('transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->middleware('abilities:transactions:read')->whereUuid('transaction')->name('transactions.receipt');

        // Goals
        Route::get('goals', [GoalController::class, 'index'])->middleware('abilities:goals:read')->name('goals.index');
        Route::post('goals', [GoalController::class, 'store'])->middleware('abilities:goals:write')->name('goals.store');
        Route::get('goals/{goal}', [GoalController::class, 'show'])->middleware('abilities:goals:read')->whereUuid('goal')->name('goals.show');
        Route::match(['put', 'patch'], 'goals/{goal}', [GoalController::class, 'update'])->middleware('abilities:goals:write')->whereUuid('goal')->name('goals.update');
        Route::delete('goals/{goal}', [GoalController::class, 'destroy'])->middleware('abilities:goals:write')->whereUuid('goal')->name('goals.destroy');

        // Budgets (monthly, not paginated)
        Route::get('budgets', [BudgetController::class, 'index'])->middleware('abilities:budgets:read')->name('budgets.index');
        Route::post('budgets', [BudgetController::class, 'store'])->middleware('abilities:budgets:write')->name('budgets.store');
        Route::match(['put', 'patch'], 'budgets/{budget}', [BudgetController::class, 'update'])->middleware('abilities:budgets:write')->whereUuid('budget')->name('budgets.update');
        Route::delete('budgets/{budget}', [BudgetController::class, 'destroy'])->middleware('abilities:budgets:write')->whereUuid('budget')->name('budgets.destroy');

        // Categories
        Route::get('categories', [CategoryController::class, 'index'])->middleware('abilities:categories:read')->name('categories.index');
        Route::post('categories', [CategoryController::class, 'store'])->middleware('abilities:categories:write')->name('categories.store');
        Route::match(['put', 'patch'], 'categories/{category}', [CategoryController::class, 'update'])->middleware('abilities:categories:write')->whereUuid('category')->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->middleware('abilities:categories:write')->whereUuid('category')->name('categories.destroy');

        // Reports
        Route::get('reports/summary', [ReportController::class, 'summary'])->middleware(['abilities:reports:read', 'permission:reports.view'])->name('reports.summary');
    });
