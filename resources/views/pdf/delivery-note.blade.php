<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bon de livraison {{ $order->order_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .header { display: table; width: 100%; margin-bottom: 24px; }
        .header-left, .header-right { display: table-cell; vertical-align: top; width: 50%; }
        .title { font-size: 22px; font-weight: bold; margin-bottom: 8px; }
        .muted { color: #555; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table.items th, table.items td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        table.items th { background: #f5f5f5; }
        .totals { margin-top: 16px; width: 100%; }
        .totals td { padding: 4px 0; }
        .totals .label { text-align: right; padding-right: 12px; }
        .totals .value { text-align: right; font-weight: bold; width: 120px; }
        .footer { margin-top: 32px; font-size: 11px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            @php
                $logoPath = null;
                if ($settings->logo) {
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($settings->logo)) {
                        $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($settings->logo);
                    } elseif (\Illuminate\Support\Facades\Storage::disk('local')->exists($settings->logo)) {
                        $logoPath = \Illuminate\Support\Facades\Storage::disk('local')->path($settings->logo);
                    }
                }
            @endphp
            @if($logoPath)
                <img src="{{ $logoPath }}" alt="Logo" style="max-height: 60px; margin-bottom: 8px;">
            @endif
            <div class="title">{{ $settings->company_name ?? 'Tazri Bio' }}</div>
            @if($settings->phone)<div>{{ $settings->phone }}</div>@endif
            @if($settings->address)<div class="muted">{{ $settings->address }}</div>@endif
            @if($settings->rib)<div class="muted">RIB: {{ $settings->rib }}</div>@endif
        </div>
        <div class="header-right" style="text-align: right;">
            <div class="title">Bon de livraison</div>
            <div><strong>N° commande:</strong> {{ $order->order_number }}</div>
            <div><strong>Date:</strong> {{ $order->created_at?->format('d/m/Y H:i') }}</div>
            <div><strong>Statut:</strong> {{ $order->status?->label() }}</div>
        </div>
    </div>

    <div>
        <strong>Client</strong><br>
        {{ $order->client?->full_name }}<br>
        {{ $order->client?->phone }}<br>
        @if($order->city){{ $order->city }}<br>@endif
        @if($order->address){{ $order->address }}@endif
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Produit</th>
                <th>SKU</th>
                <th>Qté</th>
                <th>Prix unitaire</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product?->name }}</td>
                    <td>{{ $item->product?->sku }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format((float) $item->unit_price, 2, ',', ' ') }} MAD</td>
                    <td>{{ number_format((float) $item->total_price, 2, ',', ' ') }} MAD</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Sous-total</td>
            <td class="value">{{ number_format((float) $order->total_amount, 2, ',', ' ') }} MAD</td>
        </tr>
        <tr>
            <td class="label">Commission commande</td>
            <td class="value">-{{ number_format((float) $order->delivery_fee, 2, ',', ' ') }} MAD</td>
        </tr>
        @if((float) $order->discount > 0)
            <tr>
                <td class="label">Remise</td>
                <td class="value">-{{ number_format((float) $order->discount, 2, ',', ' ') }} MAD</td>
            </tr>
        @endif
        <tr>
            <td class="label">Montant net colis</td>
            <td class="value">{{ number_format((float) $order->final_amount, 2, ',', ' ') }} MAD</td>
        </tr>
        <tr>
            <td class="label"><strong>Total COD client (Ameex)</strong></td>
            <td class="value"><strong>{{ number_format((float) $order->carrierCodAmount(), 2, ',', ' ') }} MAD</strong></td>
        </tr>
    </table>

    @if($order->notes)
        <div style="margin-top: 16px;">
            <strong>Notes:</strong> {{ $order->notes }}
        </div>
    @endif

    <div class="footer">
        Document généré par Tazri Bio — {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
