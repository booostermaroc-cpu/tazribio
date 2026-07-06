<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->json('carrier_fee_rules')->nullable()->after('default_delivery_fee');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('carrier_fee_amount', 12, 2)->default(0)->after('delivery_fee');
            $table->string('carrier_fee_rule_key', 64)->nullable()->after('carrier_fee_amount');
        });

        Schema::table('return_bons', function (Blueprint $table) {
            $table->boolean('with_packaging')->default(false)->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('carrier_fee_rules');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['carrier_fee_amount', 'carrier_fee_rule_key']);
        });

        Schema::table('return_bons', function (Blueprint $table) {
            $table->dropColumn('with_packaging');
        });
    }
};
