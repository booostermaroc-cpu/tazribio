<div class="codflow-dashboard-banner">
    <div class="codflow-dashboard-banner__inner">
        <div class="codflow-dashboard-banner__content">
            <h2 class="codflow-dashboard-banner__title">
                {{ __('codflow.dashboard.banner.hello') }}, {{ auth()->user()?->name }} 👋
            </h2>
            <p class="codflow-dashboard-banner__subtitle">
                {{ __('codflow.dashboard.banner.message') }}
            </p>
        </div>
        <div class="codflow-dashboard-banner__date">
            <x-filament::icon icon="heroicon-o-calendar-days" class="h-4 w-4" />
            <span>{{ now()->subDays(30)->translatedFormat('d M Y') }} — {{ now()->translatedFormat('d M Y') }}</span>
            <x-filament::icon icon="heroicon-m-chevron-down" class="h-4 w-4 opacity-70" />
        </div>
    </div>
</div>
