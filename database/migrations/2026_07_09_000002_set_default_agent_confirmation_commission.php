<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->update([
            'agent_commission_default_type' => 'fixed',
            'agent_commission_default_value' => 15,
            'agent_commission_apply_on' => 'confirmed',
        ]);

        DB::table('users')
            ->where('confirmation_commission_type', 'none')
            ->where('role', 'agent')
            ->update([
                'confirmation_commission_type' => 'fixed',
                'confirmation_commission_value' => 15,
                'apply_commission_on' => 'confirmed',
            ]);
    }

    public function down(): void
    {
        // Pas de retour arrière automatique.
    }
};
