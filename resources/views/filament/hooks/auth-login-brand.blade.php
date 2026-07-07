<div class="codflow-auth-brand">
    <div class="codflow-auth-brand__top">
        <div class="codflow-auth-brand__badge">TB</div>
        <div>
            <p class="codflow-auth-brand__eyebrow">Bio commerce suite</p>
            <h1 class="codflow-auth-brand__title">{{ __('codflow.brand') }}</h1>
        </div>
    </div>

    <p class="codflow-auth-brand__text">{{ __('codflow.auth.brand_message') }}</p>

    <ul class="codflow-auth-brand__features">
        <li>
            <x-filament::icon icon="heroicon-o-sparkles" class="codflow-auth-brand__icon" />
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

    <div class="codflow-auth-brand__stats">
        <div>
            <strong>24/7</strong>
            <span>Suivi activité</span>
        </div>
        <div>
            <strong>Bio</strong>
            <span>Produits & stock</span>
        </div>
        <div>
            <strong>Ameex</strong>
            <span>Livraison intégrée</span>
        </div>
    </div>
</div>
