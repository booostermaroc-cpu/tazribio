<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Demande d'enlèvement #{{ $pickup->id }}</title>
    <style>body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }</style>
</head>
<body>
    <h1>Demande d'enlèvement</h1>
    <p>Transporteur : {{ $pickup->deliveryCompany?->name }}</p>
    <p>Date prévue : {{ $pickup->requested_date?->format('d/m/Y') }}</p>
    <p>Statut : {{ $pickup->status?->label() }}</p>
    <p>Notes : {{ $pickup->notes }}</p>
</body>
</html>
