<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_bons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('return_number')->unique();
            $table->text('reason');
            $table->string('status')->default('requested')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_bons');
    }
};
