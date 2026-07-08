<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\DeliveryIntegrationService;
use Illuminate\Console\Command;

class CodflowAmeexSendTest extends Command
{
    protected $signature = 'codflow:ameex:send-test {order : ID du colis à envoyer à Ameex}';

    protected $description = 'Teste l\'envoi Ameex d\'un colis (même logique que le bouton Filament)';

    public function handle(DeliveryIntegrationService $integration): int
    {
        $order = Order::query()
            ->with(['client', 'items.product', 'shipments', 'shipment'])
            ->find($this->argument('order'));

        if ($order === null) {
            $this->error('Colis introuvable.');

            return self::FAILURE;
        }

        $this->info("Colis #{$order->id} — {$order->order_number}");
        $this->line('Client: '.($order->client?->full_name ?? '—'));
        $this->line('Ville: '.($order->city ?? '—'));
        $this->line('Produits: '.$order->items->count());
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
