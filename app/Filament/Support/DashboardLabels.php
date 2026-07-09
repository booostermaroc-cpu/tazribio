<?php

namespace App\Filament\Support;

class DashboardLabels
{
    /** @var array<string, string> */
    protected static array $fallbacks = [
        'title' => 'Tableau de bord',
        'subtitle' => 'Vue d\'ensemble de votre activité COD en temps réel',
        'parcel_status' => 'Statut des colis',
        'total_orders' => 'Colis totaux',
        'delivered' => 'Livrées',
        'returned' => 'Retournées',
        'cancelled' => 'Annulées',
        'shipped' => 'Expédiées',
        'shipped_hint' => 'En transit chez le transporteur',
        'stuck_at_carrier' => 'Bloquées transporteur',
        'stuck_at_carrier_hint' => ':count colis expédié(s) depuis plus de :days jours',
        'revenue' => 'Chiffre d\'affaires',
        'revenue_hint' => 'Encaissements hors COD',
        'profit' => 'Profit estimé',
        'profit_hint' => 'Hors COD — calcul auto ou total manuel dans Paramètres',
        'profit_manual_hint' => 'Total saisi manuellement dans Paramètres',
        'carrier_payable' => 'À payer Ameex',
        'carrier_payable_month' => 'Ce mois : :amount MAD',
        'vs_last_month' => 'vs mois dernier',
        'view_all' => 'Voir tout',
        'units' => 'unités',
        'no_data' => 'Aucune donnée disponible',
        'stock_current' => 'Stock actuel',
        'stock_threshold' => 'Seuil',
        'stock_ok' => 'Aucune alerte stock',
        'distribution.delivered' => 'Livrées',
        'distribution.returned' => 'Retournées',
        'distribution.cancelled' => 'Annulées',
        'distribution.stuck_at_carrier' => 'Bloquées chez transporteur',
        'distribution.in_progress' => 'En cours',
        'charts.revenue_evolution' => 'Évolution du chiffre d\'affaires',
        'charts.filter_14' => '14 jours',
        'charts.filter_30' => '30 derniers jours',
        'charts.revenue_label' => 'MAD',
        'charts.status' => 'Répartition des colis',
        'charts.top_products' => 'Top produits',
        'charts.stock_alerts' => 'Alertes stock',
        'charts.latest_orders' => 'Colis récents',
        'table.order' => 'Colis',
        'table.client' => 'Client',
        'table.city' => 'Ville',
        'table.amount' => 'Montant',
        'table.date' => 'Date',
        'banner.hello' => 'Bienvenue',
        'banner.message' => 'Voici un aperçu de votre activité COD en temps réel.',
    ];

    /** @param  array<string, string|int|float>  $replace */
    public static function get(string $key, array $replace = []): string
    {
        $fullKey = "codflow.dashboard.{$key}";
        $translated = __($fullKey, $replace);

        if ($translated !== $fullKey) {
            return $translated;
        }

        $fallback = static::$fallbacks[$key] ?? null;

        if ($fallback === null) {
            return $key;
        }

        foreach ($replace as $search => $value) {
            $fallback = str_replace(':'.$search, (string) $value, $fallback);
        }

        return $fallback;
    }

    /**
     * @param  array<string, int>  $distribution
     * @return array<string, int>
     */
    public static function labeledDistribution(array $distribution): array
    {
        $labeled = [];

        foreach ($distribution as $key => $count) {
            $labeled[static::get("distribution.{$key}")] = $count;
        }

        return $labeled;
    }
}
