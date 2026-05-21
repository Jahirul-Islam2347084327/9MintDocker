<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('payment_intent_id');
            $table->string('account_name', 255);
            $table->string('sort_code', 50);
            $table->string('account_number', 50);
            $table->string('reference', 255);
            $table->string('status', 32)->default('created');
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('payment_intent_id');
            $table->index(['status', 'captured_at']);
            $table->foreign('payment_intent_id')->references('id')->on('payment_intents')->cascadeOnDelete();
        });

        Schema::create('crypto_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('payment_intent_id');
            $table->string('payer_address', 255);
            $table->string('payer_tag', 255)->nullable();
            $table->string('network', 32);
            $table->string('pay_currency', 10);
            $table->decimal('pay_amount', 36, 18);
            $table->string('destination_address', 255);
            $table->string('transaction_reference', 255)->nullable();
            $table->string('status', 32)->default('created');
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('payment_intent_id');
            $table->index(['status', 'captured_at']);
            $table->foreign('payment_intent_id')->references('id')->on('payment_intents')->cascadeOnDelete();
        });

        Schema::create('platform_wallet_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('payment_intent_id');
            $table->string('wallet_currency', 10);
            $table->string('pay_currency', 10);
            $table->decimal('pay_amount', 36, 18);
            $table->decimal('wallet_amount', 36, 18);
            $table->string('status', 32)->default('created');
            $table->string('hold_reference', 64)->nullable();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->nullOnDelete();
            $table->string('fx_provider', 64)->nullable();
            $table->json('fx_rate')->nullable();
            $table->timestamp('fx_rated_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('payment_intent_id');
            $table->index(['status', 'captured_at']);
            $table->foreign('payment_intent_id')->references('id')->on('payment_intents')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_wallet_payments');
        Schema::dropIfExists('crypto_payment_requests');
        Schema::dropIfExists('bank_payment_requests');
    }
};
