<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bon de retour {{ $returnBon->return_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .header { margin-bottom: 20px; }
        .title { font-size: 20px; font-weight: bold; }
        .qr { margin-top: 16px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $settings->company_name ?? 'CODFlow' }}</div>
        <div>Bon de retour — {{ $returnBon->return_number }}</div>
        <div>Commande : {{ $order?->order_number }}</div>
        <div>Client : {{ $order?->client?->full_name }} — {{ $order?->client?->phone }}</div>
        <div>Motif : {{ $returnBon->reason }}</div>
    </div>

    @if($qrCode ?? null)
        <div class="qr">
            <img src="{{ $qrCode }}" alt="QR" width="120" height="120">
            <div>Scanner ce code pour traiter le retour</div>
        </div>
    @endif
</body>
</html>
