<?php

namespace App\Filament\Support;

class AmeexLabels
{
    /** @var array<string, string> */
    protected static array $deliveryFallbacks = [
        'ameex_config_section' => 'Configuration Ameex',
        'ameex_business_id' => 'ID expéditeur Ameex (livraison)',
        'ameex_business_id_help' => 'Expéditeur livraison (souvent = C-Api-Id 21820). Différent du hub warehouse.',
        'ameex_hub_id' => 'ID hub warehouse (AGADIR)',
        'ameex_hub_id_help' => 'Hub entrepôt pour mode STOCK. Agadir Hub Principal = 17.',
        'ameex_business_saved' => 'sauvegardé',
        'ameex_send_without_stock' => 'Envoyer sans stock Ameex (direct)',
        'ameex_send_without_stock_help' => 'Si activé : colis Livraison uniquement (pas de STOCK). Désactivez pour afficher aussi dans Warehouse → Commandes (hub AGADIR + stock Ameex requis).',
        'ameex_sync_summary' => 'Données synchronisées',
        'ameex_sync_summary_text' => ':hubs hub(s) et :cities ville(s) Ameex synchronisée(s). Utilisez « Synchronisation Ameex » pour mettre à jour.',
        'ameex_auto_sync_scheduled' => 'Envoi et suivi Ameex automatiques toutes les 5 minutes (webhook + planificateur).',
        'ameex_api_id_help' => 'Collez uniquement la valeur numérique C-Api-Id (ex. 21820), sans libellé.',
        'ameex_api_key_help' => 'Collez la clé C-Api-Key depuis le tableau de bord Ameex.',
        'ameex_no_hub_warning' => 'Aucun hub détecté (ex. AGADIR HUB PRINCIPAL). Cliquez « Synchronisation Ameex » → « Synchroniser les expéditeurs », puis choisissez le hub dans la liste.',
        'ameex_sync_businesses' => 'Synchroniser les expéditeurs',
        'ameex_sync_group' => 'Synchronisation Ameex',
        'ameex_test_connection' => 'Tester la connexion Ameex',
        'ameex_businesses_sync_success' => ':count expéditeur(s) Ameex synchronisé(s).',
        'ameex_businesses_sync_failed' => 'Impossible de récupérer les expéditeurs Ameex. Saisissez hub=17 manuellement. Endpoints : :paths',
        'ameex_sender_required' => 'Ameex exige un expéditeur valide. Utilisez business=21820 (expéditeur) et hub=17 (AGADIR) en mode STOCK.',
    ];

    /** @param  array<string, string|int>  $replace */
    public static function delivery(string $key, array $replace = []): string
    {
        $fullKey = "codflow.delivery.{$key}";
        $translated = __($fullKey, $replace);

        if ($translated !== $fullKey) {
            return $translated;
        }

        $fallback = static::$deliveryFallbacks[$key] ?? null;

        if ($fallback === null) {
            return $key;
        }

        foreach ($replace as $search => $value) {
            $fallback = str_replace(':'.$search, (string) $value, $fallback);
        }

        return $fallback;
    }

    /** @param  array<string, string>  $options */
    public static function sortBusinessOptions(array $options): array
    {
        $hubs = [];
        $others = [];

        foreach ($options as $id => $name) {
            if (str_contains(mb_strtoupper((string) $name), 'HUB')) {
                $hubs[(string) $id] = (string) $name;
            } else {
                $others[(string) $id] = (string) $name;
            }
        }

        return $hubs + $others;
    }

    /** @param  array<string, string>  $options */
    public static function hasHubOption(array $options): bool
    {
        foreach ($options as $name) {
            if (str_contains(mb_strtoupper((string) $name), 'HUB')) {
                return true;
            }
        }

        return false;
    }
}
