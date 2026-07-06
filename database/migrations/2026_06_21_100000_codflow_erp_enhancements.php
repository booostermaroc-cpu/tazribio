<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method')->default('cod')->after('payment_status');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->string('payment_receiver_name')->nullable()->after('payment_reference');
            $table->string('payment_receiver_rib')->nullable()->after('payment_receiver_name');
            $table->string('payment_receipt')->nullable()->after('payment_receiver_rib');
            $table->timestamp('payment_received_at')->nullable()->after('payment_receipt');
            $table->text('payment_notes')->nullable()->after('payment_received_at');
            $table->foreignId('confirmed_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('confirmation_commission_type')->default('none')->after('allowed_resources');
            $table->decimal('confirmation_commission_value', 10, 2)->default(0)->after('confirmation_commission_type');
            $table->string('apply_commission_on')->default('delivered')->after('confirmation_commission_value');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->string('default_payment_method')->default('cod')->after('invoice_prefix');
            $table->foreignId('default_delivery_company_id')->nullable()->after('default_payment_method')->constrained('delivery_companies')->nullOnDelete();
            $table->string('return_bon_prefix')->default('RET')->after('default_delivery_company_id');
            $table->string('agent_commission_default_type')->default('none')->after('return_bon_prefix');
            $table->decimal('agent_commission_default_value', 10, 2)->default(0)->after('agent_commission_default_type');
            $table->string('agent_commission_apply_on')->default('delivered')->after('agent_commission_default_value');
            $table->boolean('profit_include_delivery_fee')->default(true)->after('agent_commission_apply_on');
        });

        Schema::table('delivery_companies', function (Blueprint $table) {
            $table->string('provider')->default('manual')->after('name');
            $table->string('api_base_url')->nullable()->after('api_url');
            $table->string('api_username')->nullable()->after('api_token');
            $table->string('api_password')->nullable()->after('api_username');
            $table->json('api_settings')->nullable()->after('api_password');
        });

        Schema::table('return_bons', function (Blueprint $table) {
            $table->string('barcode_token')->nullable()->unique()->after('return_number');
        });

        Schema::create('agent_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_commissions');

        Schema::table('return_bons', function (Blueprint $table) {
            $table->dropColumn('barcode_token');
        });

        Schema::table('delivery_companies', function (Blueprint $table) {
            $table->dropColumn(['provider', 'api_base_url', 'api_username', 'api_password', 'api_settings']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_delivery_company_id');
            $table->dropColumn([
                'default_payment_method',
                'return_bon_prefix',
                'agent_commission_default_type',
                'agent_commission_default_value',
                'agent_commission_apply_on',
                'profit_include_delivery_fee',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['confirmation_commission_type', 'confirmation_commission_value', 'apply_commission_on']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn([
                'payment_method',
                'payment_reference',
                'payment_receiver_name',
                'payment_receiver_rib',
                'payment_receipt',
                'payment_received_at',
                'payment_notes',
            ]);
        });
    }
};
