<x-filament-widgets::widget class="codflow-dashboard-banner-widget">
    <div class="codflow-dashboard-banner" wire:key="dashboard-welcome-{{ app()->getLocale() }}">
        <div class="codflow-dashboard-banner__inner">
            <div class="codflow-dashboard-banner__content">
                <h2 class="codflow-dashboard-banner__title">
                    {{ __('codflow.dashboard.banner.hello') }}, {{ auth()->user()?->name }} 👋
                </h2>
                <p class="codflow-dashboard-banner__subtitle">
                    {{ __('codflow.dashboard.subtitle') }}
                </p>
            </div>
            <div class="codflow-dashboard-banner__date">
                <x-filament::icon icon="heroicon-o-calendar-days" class="h-4 w-4" />
                <span>
                    {{ now()->subDays(30)->locale(app()->getLocale())->translatedFormat('d M Y') }}
                    —
                    {{ now()->locale(app()->getLocale())->translatedFormat('d M Y') }}
                </span>
                <x-filament::icon icon="heroicon-m-chevron-down" class="h-4 w-4 opacity-70" />
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
