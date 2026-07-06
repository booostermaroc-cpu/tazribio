<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_plannings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_company_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_amount', 12, 2);
            $table->date('expected_payment_date');
            $table->string('status')->default('planned')->index();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_plannings');
    }
};
