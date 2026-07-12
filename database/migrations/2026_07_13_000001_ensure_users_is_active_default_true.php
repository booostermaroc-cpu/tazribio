<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_active')) {
            return;
        }

        DB::table('users')->whereNull('is_active')->update(['is_active' => true]);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `users` MODIFY `is_active` TINYINT(1) NOT NULL DEFAULT 1');
        }
    }

    public function down(): void
    {
        //
    }
};
