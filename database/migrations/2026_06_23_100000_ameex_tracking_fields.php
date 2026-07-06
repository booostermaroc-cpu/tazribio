<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('ameex_parcel_code')->nullable()->after('ameex_delivery_note_ref');
            $table->string('ameex_last_status')->nullable()->after('ameex_parcel_code');
            $table->string('ameex_last_status_name')->nullable()->after('ameex_last_status');
            $table->string('ameex_last_sub_status')->nullable()->after('ameex_last_status_name');
            $table->json('ameex_raw_response')->nullable()->after('ameex_last_sub_status');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'ameex_parcel_code',
                'ameex_last_status',
                'ameex_last_status_name',
                'ameex_last_sub_status',
                'ameex_raw_response',
            ]);
        });
    }
};
