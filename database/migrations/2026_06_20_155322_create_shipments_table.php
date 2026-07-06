<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_company_id')->constrained()->restrictOnDelete();
            $table->string('tracking_number')->unique();
            $table->string('delivery_status')->default('pending')->index();
            $table->date('delivery_date')->nullable();
            $table->text('return_reason')->nullable();
            $table->timestamp('last_tracking_update')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
