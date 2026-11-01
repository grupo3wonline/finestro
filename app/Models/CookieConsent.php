<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToUser;
use App\Traits\HasUuid;
use Database\Factories\CookieConsentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One cookie consent decision of a logged-in user (proof of consent).
 *
 * @property string $id
 * @property string $user_id
 * @property string $version
 * @property bool $analytics
 * @property bool $marketing
 * @property bool $gpc
 * @property string $source banner | settings
 */
class CookieConsent extends Model
{
    /** @use HasFactory<CookieConsentFactory> */
    use BelongsToUser, HasFactory, HasUuid, SoftDeletes;

    public const SOURCE_BANNER = 'banner';

    public const SOURCE_SETTINGS = 'settings';

    protected $fillable = [
        'user_id',
        'version',
        'analytics',
        'marketing',
        'gpc',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'analytics' => 'boolean',
            'marketing' => 'boolean',
            'gpc' => 'boolean',
        ];
    }
}
