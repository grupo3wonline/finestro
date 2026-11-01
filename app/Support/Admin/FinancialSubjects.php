<?php

declare(strict_types=1);

namespace App\Support\Admin;

use App\Models\AiCredential;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Goal;
use App\Models\Transaction;
use App\Models\TransactionHistory;
use App\Models\Wallet;
use App\Models\WalletMember;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Activity subjects that hold a user's financial data. The admin panel shows
 * only event, type and date for them, never the logged attributes.
 */
final class FinancialSubjects
{
    /** @var list<class-string> */
    public const array MODELS = [
        Wallet::class,
        Transaction::class,
        Goal::class,
        Budget::class,
        Category::class,
        AiCredential::class,
        TransactionHistory::class,
        WalletMember::class,
        Media::class,
    ];

    public static function isFinancial(?string $subjectType): bool
    {
        // No morph map is registered, so subject_type holds the class name.
        return $subjectType !== null && in_array(ltrim($subjectType, '\\'), self::MODELS, true);
    }
}
