<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('invoice_prefix')->default('INV')->after('default_delivery_fee');
            $table->string('order_prefix')->default('ORD')->after('invoice_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['invoice_prefix', 'order_prefix']);
        });
    }
};
