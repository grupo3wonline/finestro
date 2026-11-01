<?php

namespace App\Providers\Filament;

use App\Filament\AvatarProviders\InitialsAvatarProvider;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Finestro admin panel (/admin).
 *
 * Uses its own `admin` session guard so every admin signs in through the
 * panel login with required app (TOTP) MFA. No profile page (no password
 * or e-mail editing from the panel) and no SPA mode.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->authGuard('admin')
            ->brandName('Finestro')
            ->brandLogo(fn (): View => view('filament.admin.brand'))
            ->favicon(asset('favicon.svg'))
            ->colors([
                'primary' => '#039855',
                'warning' => Color::Amber,
            ])
            ->darkMode(true)
            ->font('Inter', provider: GoogleFontProvider::class)
            ->globalSearch(false)
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable()
                    ->brandName('Finestro'),
            ], isRequired: true)
            ->renderHook(PanelsRenderHook::FOOTER, fn (): View => view('filament.admin.footer'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
