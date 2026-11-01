<?php

declare(strict_types=1);

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Local initials avatar rendered as an inline SVG data URI, so admin names
 * are never sent to a third-party avatar service (ui-avatars.com).
 */
class InitialsAvatarProvider implements AvatarProvider
{
    private const string BACKGROUND = '#039855';

    public function get(Model|Authenticatable $record): string
    {
        $initials = collect(preg_split('/\s+/u', trim(Filament::getNameForDefaultAvatar($record))) ?: [])
            ->map(fn (string $segment): string => (string) preg_replace('/^[^\p{L}\p{N}]+/u', '', $segment))
            ->filter()
            ->take(2)
            ->map(fn (string $segment): string => mb_strtoupper(mb_substr($segment, 0, 1)))
            ->join('');

        $text = htmlspecialchars($initials !== '' ? $initials : '?', ENT_QUOTES | ENT_XML1, 'UTF-8');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" fill="'.self::BACKGROUND.'"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="#FFFFFF" '
            .'font-family="Inter, Arial, sans-serif" font-size="26" font-weight="600">'.$text.'</text>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
