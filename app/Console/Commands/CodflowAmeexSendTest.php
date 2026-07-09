<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Delivery\AmeexDeliveryService;
use App\Services\DeliveryIntegrationService;
use Illuminate\Console\Command;

class CodflowAmeexSendTest extends Command
{
    protected $signature = 'codflow:ameex:send-test {order : ID du colis à envoyer à Ameex}';

    protected $description = 'Teste l\'envoi Ameex d\'un colis (même logique que le bouton Filament)';

    public function handle(DeliveryIntegrationService $integration, AmeexDeliveryService $ameex): int
    {
        $order = Order::query()
            ->with(['client', 'items.product', 'shipments.deliveryCompany', 'shipment.deliveryCompany'])
            ->find($this->argument('order'));

        if ($order === null) {
            $this->error('Colis introuvable.');

            return self::FAILURE;
        }

        $company = $order->shipments()->first()?->deliveryCompany
            ?? $order->shipment?->deliveryCompany
            ?? \App\Models\DeliveryCompany::query()
                ->where('provider', \App\Enums\DeliveryProvider::Ameex)
                ->where('is_active', true)
                ->first();

        $this->info("Colis #{$order->id} — {$order->order_number}");
        $this->line('Client: '.($order->client?->full_name ?? '—'));
        $this->line('Ville: '.($order->city ?? '—'));
        $this->line('Produits: '.$order->items->count());

        if ($company !== null) {
            $this->line('Hub Ameex: '.($ameex->businessDisplayName($company) ?? '—').' ('.($ameex->businessId($company) ?? 'manquant').')');
        }

        foreach ($order->items as $item) {
            $this->line('  - '.($item->product?->ameexStockReference() ?? 'sans ref').' x'.$item->quantity);
        }

        $this->newLine();

        $result = $integration->sendOrderToCarrier($order);

        $this->table(['Clé', 'Valeur'], [
            ['success', ($result['success'] ?? false) ? 'oui' : 'NON'],
            ['message', $result['message'] ?? '—'],
        ]);

        if ($result['success'] ?? false) {
            $shipment = $order->fresh()->shipments()->first() ?? $order->fresh()->shipment;
            $this->line('Tracking: '.($shipment?->tracking_number ?? '—'));
        }

        return ($result['success'] ?? false) ? self::SUCCESS : self::FAILURE;
    }
}
