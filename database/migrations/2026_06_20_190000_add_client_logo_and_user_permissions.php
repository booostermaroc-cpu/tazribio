<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('full_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('allowed_resources')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('logo');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('allowed_resources');
        });
    }
};
