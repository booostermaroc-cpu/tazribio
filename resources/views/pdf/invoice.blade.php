<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 8px; }
    </style>
</head>
<body>
    <h1>Facture {{ $invoice->invoice_number }}</h1>
    <p>Commande : {{ $invoice->order?->order_number }}</p>
    <p>Client : {{ $invoice->order?->client?->full_name }}</p>
    <p>Montant : {{ number_format((float) $invoice->amount, 2) }} MAD</p>
    <p>Statut : {{ $invoice->status?->label() }}</p>
</body>
</html>
