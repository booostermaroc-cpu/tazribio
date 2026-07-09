<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->update(['default_delivery_fee' => 15]);
    }

    public function down(): void
    {
        // Pas de retour arrière : ancienne sémantique « frais de livraison » obsolète.
    }
};
