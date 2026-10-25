<?php

namespace App\Providers\Filament;

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
use Illuminate\Support\HtmlString;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('control')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            // The app is deployed under a subdirectory (symlinked at /evermore-api
            // instead of the domain root — see config/livewire.php). Laravel's
            // relative-URL generator strips that subdirectory off the Livewire
            // update endpoint it reports to the browser (returns "/livewire/update"
            // instead of "/evermore-api/livewire/update"), so every Livewire
            // request — including the login form submit — 404s. Livewire's JS
            // re-reads the endpoint from the DOM/window on every request (it's
            // not cached at boot), so patching it once on DOMContentLoaded is
            // enough to fix it for the whole panel.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var base = window.location.pathname.replace(/\/control(\/.*)?$/, '');
                        if (!base) return;

                        var el = document.querySelector('script[data-update-uri]');
                        if (el) {
                            var uri = el.getAttribute('data-update-uri');
                            if (uri && uri.indexOf(base) !== 0) {
                                el.setAttribute('data-update-uri', base + uri);
                            }
                        }

                        if (window.livewireScriptConfig && window.livewireScriptConfig.uri
                            && window.livewireScriptConfig.uri.indexOf(base) !== 0) {
                            window.livewireScriptConfig.uri = base + window.livewireScriptConfig.uri;
                        }
                    });
                    </script>
                    HTML),
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
