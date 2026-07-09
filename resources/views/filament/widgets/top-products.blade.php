@php use App\Filament\Support\DashboardLabels; @endphp
<x-filament-widgets::widget class="codflow-panel-widget">
    <div class="codflow-panel">
        <div class="codflow-panel__header">
            <h3 class="codflow-panel__title">{{ DashboardLabels::get('charts.top_products') }}</h3>
            <a href="{{ $this->getViewAllUrl() }}" class="codflow-panel__link">
                {{ DashboardLabels::get('view_all') }}
            </a>
        </div>

        <div class="codflow-panel__body">
            @forelse ($this->getProducts() as $product)
                <div class="codflow-list-item">
                    <div class="codflow-list-item__avatar codflow-list-item__avatar--product">
                        @if ($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
                        @else
                            <x-filament::icon icon="heroicon-o-cube" class="h-5 w-5" />
                        @endif
                    </div>
                    <div class="codflow-list-item__content">
                        <div class="codflow-list-item__title">{{ $product->name }}</div>
                        <div class="codflow-list-item__subtitle">{{ $product->sku }}</div>
                    </div>
                    <div class="codflow-list-item__metric">
                        <span class="codflow-list-item__value">{{ number_format((int) ($product->total_sold ?? 0)) }}</span>
                        <span class="codflow-list-item__unit">{{ DashboardLabels::get('units') }}</span>
                    </div>
                </div>
            @empty
                <p class="codflow-empty">{{ DashboardLabels::get('no_data') }}</p>
            @endforelse
        </div>
    </div>
</x-filament-widgets::widget>
