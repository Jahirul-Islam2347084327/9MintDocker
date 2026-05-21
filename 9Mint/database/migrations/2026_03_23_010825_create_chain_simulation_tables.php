<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chain_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('address', 64)->unique();
            $table->string('network', 32)->default('sim');
            $table->string('label', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('chain_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nft_token_id')->unique()->constrained('nft_tokens')->cascadeOnDelete();
            $table->foreignId('nft_id')->constrained('nfts')->cascadeOnDelete();
            $table->unsignedInteger('serial_number');
            $table->foreignId('first_sale_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('minted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['nft_id', 'serial_number']);
        });

        Schema::create('chain_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            $table->string('status', 32)->default('confirmed');
            $table->foreignId('initiated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained('listings')->nullOnDelete();
            $table->string('provider', 32)->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
        });

        Schema::create('chain_token_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chain_transaction_id')->constrained('chain_transactions')->cascadeOnDelete();
            $table->foreignId('chain_token_id')->constrained('chain_tokens')->cascadeOnDelete();
            $table->string('event_type', 32);
            $table->foreignId('from_chain_account_id')->nullable()->constrained('chain_accounts')->nullOnDelete();
            $table->foreignId('to_chain_account_id')->nullable()->constrained('chain_accounts')->nullOnDelete();
            $table->timestamp('occurred_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['chain_token_id', 'occurred_at']);
        });

        Schema::create('chain_current_ownership', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chain_token_id')->unique()->constrained('chain_tokens')->cascadeOnDelete();
            $table->foreignId('chain_account_id')->nullable()->constrained('chain_accounts')->nullOnDelete();
            $table->foreignId('acquired_via_event_id')->nullable()->constrained('chain_token_events')->nullOnDelete();
            $table->timestamp('acquired_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('chain_account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chain_current_ownership');
        Schema::dropIfExists('chain_token_events');
        Schema::dropIfExists('chain_transactions');
        Schema::dropIfExists('chain_tokens');
        Schema::dropIfExists('chain_accounts');
    }
};
