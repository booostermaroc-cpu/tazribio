<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('use_manual_profit_total')->default(false)->after('profit_include_delivery_fee');
            $table->decimal('manual_profit_total', 12, 2)->nullable()->after('use_manual_profit_total');
        });

        DB::table('orders')
            ->where('payment_method', 'cod')
            ->update([
                'profit_amount' => 0,
                'profit_is_manual' => false,
            ]);
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['use_manual_profit_total', 'manual_profit_total']);
        });
    }
};
