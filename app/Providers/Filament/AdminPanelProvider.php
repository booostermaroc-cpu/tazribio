<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Filament\Support\PanelHome;
use App\Http\Middleware\SetLocale;
use App\Services\SettingService;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->homeUrl(fn (): string => PanelHome::url())
            ->brandName(fn (): string => SettingService::companyName())
            ->brandLogo(fn (): ?string => SettingService::logoUrl())
            ->brandLogoHeight('2.5rem')
            ->databaseNotifications()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::Violet,
                'danger' => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'info' => Color::Cyan,
                'gray' => Color::Slate,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->userMenuItems([
                'locale-fr' => MenuItem::make()
                    ->label(__('codflow.locale.french'))
                    ->icon('heroicon-o-language')
                    ->url(fn () => route('locale.switch', 'fr'))
                    ->hidden(fn () => app()->getLocale() === 'fr'),
                'locale-ar' => MenuItem::make()
                    ->label(__('codflow.locale.arabic'))
                    ->icon('heroicon-o-language')
                    ->url(fn () => route('locale.switch', 'ar'))
                    ->hidden(fn () => app()->getLocale() === 'ar'),
            ])
            ->renderHook(
                PanelsRenderHook::SIMPLE_PAGE_START,
                fn (): HtmlString => new HtmlString(view('filament.hooks.auth-login-brand')->render()),
                scopes: Login::class,
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): HtmlString => new HtmlString(
                    app()->getLocale() === 'ar'
                        ? '<script>document.documentElement.setAttribute("dir","rtl")</script>'
                        : ''
                ),
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn (): HtmlString => new HtmlString(view('filament.hooks.footer')->render()),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
