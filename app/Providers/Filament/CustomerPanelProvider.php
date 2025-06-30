<?php

namespace App\Providers\Filament;

use App\Filament\Customer\Pages\Auth\Login;
use App\Filament\Customer\Pages\Auth\Register;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class CustomerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('customer')
            ->path('/')
            ->colors([
                'primary' => Color::Rose,
                'indigo' => Color::Indigo,
                'rose' => Color::Rose,
            ])
            ->defaultThemeMode(ThemeMode::Light)
            ->authGuard('customer')
            ->login(Login::class)
            ->registration(Register::class)
            ->topNavigation()
            ->discoverResources(in: app_path('Filament/Customer/Resources'), for: 'App\\Filament\\Customer\\Resources')
            ->discoverPages(in: app_path('Filament/Customer/Pages'), for: 'App\\Filament\\Customer\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Customer/Widgets'), for: 'App\\Filament\\Customer\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
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
            ])
            ->authMiddleware([
                // Authenticate::class,
            ])
            ->theme(asset('css/filament/customer/theme.css'))
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn(): string => filament()->auth()->check()
                    ? ''
                    : Blade::render(<<<'BLADE'
                    <a href="/login"
                        class="ml-4 flex items-center gap-1 text-sm text-primary-600 font-medium transition-colors duration-200 hover:text-primary-700 hover:bg-gray-100 dark:hover:bg-gray-800 px-2 py-1 rounded-md"
                        >
                            <x-heroicon-o-arrow-left-end-on-rectangle class="w-5 h-5" />
                            Login
                        </a>
                BLADE)
            );
    }
}
