<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('profit_amount', 12, 2)->nullable()->after('final_amount');
            $table->boolean('profit_is_manual')->default(false)->after('profit_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['profit_amount', 'profit_is_manual']);
        });
    }
};
