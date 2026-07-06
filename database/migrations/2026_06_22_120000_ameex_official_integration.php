<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('ameex_delivery_note_ref')->nullable()->after('tracking_number');
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->string('pickup_address')->nullable()->after('delivery_company_id');
            $table->string('pickup_phone')->nullable()->after('pickup_address');
            $table->string('ameex_city_id')->nullable()->after('pickup_phone');
            $table->string('ameex_request_ref')->nullable()->after('notes');
            $table->string('ameex_status')->nullable()->after('ameex_request_ref');
            $table->json('ameex_raw_response')->nullable()->after('ameex_status');
        });
    }

    public function down(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropColumn([
                'pickup_address',
                'pickup_phone',
                'ameex_city_id',
                'ameex_request_ref',
                'ameex_status',
                'ameex_raw_response',
            ]);
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('ameex_delivery_note_ref');
        });
    }
};
