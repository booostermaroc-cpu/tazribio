<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CodflowSyncTranslations extends Command
{
    protected $signature = 'codflow:lang:sync-ar';

    protected $description = 'Complète lang/ar/codflow.php avec les clés manquantes (repli FR)';

    public function handle(): int
    {
        $frPath = lang_path('fr/codflow.php');
        $arPath = lang_path('ar/codflow.php');

        /** @var array<string, mixed> $fr */
        $fr = require $frPath;
        /** @var array<string, mixed> $ar */
        $ar = file_exists($arPath) ? require $arPath : [];

        $before = $this->countLeaves($ar);
        $merged = $this->mergeRecursive($fr, $ar);
        $after = $this->countLeaves($merged);

        file_put_contents($arPath, "<?php\n\nreturn ".var_export($merged, true).";\n");

        $this->info('Clés AR : '.$before.' → '.$after.' (+'.($after - $before).')');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<string, mixed>  $target
     * @return array<string, mixed>
     */
    protected function mergeRecursive(array $source, array $target): array
    {
        foreach ($source as $key => $value) {
            if (is_array($value)) {
                $target[$key] = $this->mergeRecursive(
                    $value,
                    is_array($target[$key] ?? null) ? $target[$key] : [],
                );

                continue;
            }

            if (! array_key_exists($key, $target) || blank($target[$key])) {
                $target[$key] = $value;
            }
        }

        return $target;
    }

    /** @param  array<string, mixed>  $array */
    protected function countLeaves(array $array): int
    {
        $count = 0;

        foreach ($array as $value) {
            $count += is_array($value) ? $this->countLeaves($value) : 1;
        }

        return $count;
    }
}
