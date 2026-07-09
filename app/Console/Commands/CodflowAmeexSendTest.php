<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Delivery\AmeexDeliveryService;
use App\Services\DeliveryIntegrationService;
use Illuminate\Console\Command;

class CodflowAmeexSendTest extends Command
{
    protected $signature = 'codflow:ameex:send-test
                            {order : ID du colis à envoyer à Ameex}
                            {--order-only : Créer uniquement la commande manuelle Ameex (colis déjà envoyé)}
                            {--force : Forcer la recréation même si déjà synchronisée}';

    protected $description = 'Teste l\'envoi Ameex d\'un colis ou la création de commande manuelle';

    public function handle(DeliveryIntegrationService $integration, AmeexDeliveryService $ameex): int
    {
        $order = Order::query()
            ->with(['client', 'items.product', 'shipments.deliveryCompany', 'shipment.deliveryCompany'])
            ->find($this->argument('order'));

        if ($order === null) {
            $this->error('Colis introuvable.');

            return self::FAILURE;
        }

        $shipment = $order->shipments()->first() ?? $order->shipment;

        $company = $shipment?->deliveryCompany
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

        if ($shipment !== null) {
            $this->line('Tracking: '.($shipment->tracking_number ?? '—'));
            $this->line('Commande Ameex sync: '.($ameex->isAmeexOrderSynced($shipment) ? 'oui' : 'non'));
        }

        foreach ($order->items as $item) {
            $this->line('  - '.($item->product?->ameexStockReference() ?? 'sans ref').' x'.$item->quantity);
        }

        $this->newLine();

        if ($this->option('order-only')) {
            if ($shipment === null) {
                $this->error('Aucun shipment lié à ce colis.');

                return self::FAILURE;
            }

            if ($company === null) {
                $this->error('Aucun transporteur Ameex configuré.');

                return self::FAILURE;
            }

            if ($this->option('force') && is_array($shipment->ameex_raw_response)) {
                $raw = $shipment->ameex_raw_response;
                unset($raw['ameex_order_synced'], $raw['ameex_order_manual'], $raw['ameex_order_path']);
                $shipment->update(['ameex_raw_response' => $raw]);
                $shipment->refresh();
                $this->warn('Flag ameex_order_synced réinitialisé.');
            }

            $result = $integration->sendShipmentOrderToAmeex($shipment);
        } else {
            $result = $integration->sendOrderToCarrier($order);
        }

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
