<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle of a personal data export (Configurações > Privacidade).
 */
enum DataExportStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Em preparação',
            self::Ready => 'Pronta para baixar',
            self::Failed => 'Falhou',
            self::Expired => 'Expirada',
        };
    }

    /**
     * Badge variant of <x-ui.badge>.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending => 'info',
            self::Ready => 'success',
            self::Failed => 'danger',
            self::Expired => 'neutral',
        };
    }
}
