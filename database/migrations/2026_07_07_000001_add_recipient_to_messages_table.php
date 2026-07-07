<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('recipient_id')
                ->nullable()
                ->after('sender_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('read_at')->nullable()->after('attachment');

            $table->index(['recipient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['recipient_id', 'created_at']);
            $table->dropConstrainedForeignId('recipient_id');
            $table->dropColumn('read_at');
        });
    }
};
