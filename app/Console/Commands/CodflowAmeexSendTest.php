<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Delivery\AmeexDeliveryService;
use App\Services\DeliveryIntegrationService;
use Illuminate\Console\Command;

class CodflowAmeexSendTest extends Command
{
    protected $signature = 'codflow:ameex:send-test
                            {order : ID ou numéro du colis (ex: 15 ou ORD-20260709-M4UR)}
                            {--order-only : Créer uniquement la commande warehouse Ameex (colis déjà envoyé)}
                            {--force : Forcer la recréation même si déjà synchronisée}';

    protected $description = 'Teste l\'envoi Ameex d\'un colis ou la création de commande warehouse';

    public function handle(DeliveryIntegrationService $integration, AmeexDeliveryService $ameex): int
    {
        $reference = trim((string) $this->argument('order'));
        $order = $this->resolveOrder($reference);

        if ($order === null) {
            $this->error("Colis introuvable pour « {$reference} ».");
            $this->line('Utilisez l\'ID numérique du colis ou son numéro complet (ex: ORD-20260709-M4UR).');
            $this->line('Ce n\'est pas l\'ID du transporteur (Transporteurs → AMEEX /2 = transporteur #2, pas colis #2).');
            $this->suggestRecentOrders();

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
            $hubName = $ameex->hubDisplayName($company) ?? '—';
            $hubId = $ameex->hubId($company) ?? 'manquant';
            $senderId = $ameex->senderBusinessId($company) ?? 'manquant';
            $this->line("Expéditeur Ameex: {$senderId}");
            $this->line("Hub warehouse: {$hubName} ({$hubId})");

            if (blank($ameex->hubId($company))) {
                $this->warn('Hub warehouse manquant : renseignez 17 (Agadir Hub Principal) dans Transporteurs → AMEEX.');
            }
        }

        if ($shipment !== null) {
            $this->line('Tracking: '.($shipment->tracking_number ?? '—'));
            $raw = is_array($shipment->ameex_raw_response) ? $shipment->ameex_raw_response : [];
            $this->line('Mode STOCK colis: '.((($raw['ameex_parcel_stock_mode'] ?? false) === true) ? 'oui' : 'non'));
            $this->line('Hub colis payload: '.($raw['ameex_parcel_hub_id'] ?? '—'));
            $this->line('Commande Ameex sync: '.($ameex->isAmeexOrderSynced($shipment) ? 'oui' : 'non'));

            if (filled($raw['ameex_order_sync_error'] ?? null)) {
                $this->warn('Dernière erreur sync warehouse: '.$raw['ameex_order_sync_error']);
            }
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
                unset($raw['ameex_order_synced'], $raw['ameex_order_manual'], $raw['ameex_order_warehouse'], $raw['ameex_order_path']);
                $shipment->update(['ameex_raw_response' => $raw]);
                $shipment->refresh();
                $this->warn('Flag ameex_order_synced réinitialisé.');
            }

            $result = $integration->sendShipmentOrderToAmeex($shipment);
        } else {
            if ($shipment !== null && $ameex->isAmeexOrderSynced($shipment->fresh())) {
                $this->warn('Colis déjà envoyé. Pour créer la commande warehouse : --order-only --force');
            }

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

    protected function resolveOrder(string $reference): ?Order
    {
        $query = Order::query()
            ->with(['client', 'items.product', 'shipments.deliveryCompany', 'shipment.deliveryCompany']);

        if (ctype_digit($reference)) {
            return $query->find((int) $reference);
        }

        return $query->where('order_number', $reference)->first();
    }

    protected function suggestRecentOrders(): void
    {
        $orders = Order::query()
            ->with(['shipments' => fn ($query) => $query->select('id', 'order_id', 'tracking_number')])
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id', 'order_number']);

        if ($orders->isEmpty()) {
            return;
        }

        $this->newLine();
        $this->warn('Colis récents :');

        foreach ($orders as $order) {
            $tracking = $order->shipments->first()?->tracking_number ?? '—';
            $this->line("  #{$order->id} — {$order->order_number} (tracking: {$tracking})");
        }
    }
}
