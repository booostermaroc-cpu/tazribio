<div class="codflow-auth-brand">
    <div class="codflow-auth-brand__badge">COD</div>
    <h1 class="codflow-auth-brand__title">{{ __('codflow.brand') }}</h1>
    <p class="codflow-auth-brand__text">{{ __('codflow.auth.brand_message') }}</p>

    <ul class="codflow-auth-brand__features">
        <li>
            <x-filament::icon icon="heroicon-o-shopping-bag" class="codflow-auth-brand__icon" />
            <span>{{ __('codflow.auth.feature_orders') }}</span>
        </li>
        <li>
            <x-filament::icon icon="heroicon-o-cube" class="codflow-auth-brand__icon" />
            <span>{{ __('codflow.auth.feature_stock') }}</span>
        </li>
        <li>
            <x-filament::icon icon="heroicon-o-chart-bar" class="codflow-auth-brand__icon" />
            <span>{{ __('codflow.auth.feature_dashboard') }}</span>
        </li>
    </ul>
</div>
