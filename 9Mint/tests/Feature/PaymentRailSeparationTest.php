<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutVite;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\PaymentTestHelper;

class PaymentRailSeparationTest extends TestCase
{
    use PaymentTestHelper;
    use RefreshDatabase;
    use WithoutVite;

    public function test_bank_checkout_creates_bank_payment_record_and_finalizes_order(): void
    {
        [$buyer, , $order] = $this->seedPendingSaleOrder();

        $response = $this->actingAs($buyer)
            ->withSession(['checkout_order_id' => $order->id])
            ->post(route('orders.store'), $this->checkoutPayload([
                'provider' => 'mock_bank',
                'bank_account_name' => '9Mint Ltd',
                'bank_sort_code' => '12-34-56',
                'bank_account_number' => '12345678',
                'bank_reference' => 'ORDER-' . $order->id,
            ]));

        $response->assertRedirect('/cart');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('payment_intents', [
            'order_id' => $order->id,
            'provider' => 'mock_bank',
            'status' => 'captured',
        ]);
        $this->assertDatabaseHas('bank_payment_requests', [
            'reference' => 'ORDER-' . $order->id,
            'status' => 'captured',
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid',
        ]);
    }

    public function test_crypto_checkout_creates_crypto_payment_request(): void
    {
        [$buyer, , $order] = $this->seedPendingSaleOrder();

        $response = $this->actingAs($buyer)
            ->withSession(['checkout_order_id' => $order->id])
            ->post(route('orders.store'), $this->checkoutPayload([
                'provider' => 'mock_crypto',
                'wallet_address' => '0xabcdefabcdefabcdefabcdefabcdefabcdefabcd',
                'wallet_tag' => 'buyer-tag',
                'wallet_network' => 'ETH',
            ]));

        $response->assertRedirect('/cart');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('payment_intents', [
            'order_id' => $order->id,
            'provider' => 'mock_crypto',
            'status' => 'captured',
        ]);
        $this->assertDatabaseHas('crypto_payment_requests', [
            'payer_address' => '0xabcdefabcdefabcdefabcdefabcdefabcdefabcd',
            'payer_tag' => 'buyer-tag',
            'network' => 'ETH',
            'status' => 'captured',
        ]);
    }

    public function test_platform_wallet_checkout_uses_separate_wallet_payment_entity_and_debits_balance(): void
    {
        [$buyer, , $order] = $this->seedPendingSaleOrder();
        Wallet::create([
            'user_id' => $buyer->id,
            'currency' => 'GBP',
            'balance' => 250,
        ]);

        $response = $this->actingAs($buyer)
            ->withSession(['checkout_order_id' => $order->id])
            ->post(route('orders.store'), $this->checkoutPayload([
                'provider' => 'mock_wallet',
                'wallet_currency' => 'GBP',
            ]));

        $response->assertRedirect('/cart');
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('payment_intents', [
            'order_id' => $order->id,
            'provider' => 'mock_wallet',
            'status' => 'captured',
        ]);
        $this->assertDatabaseHas('platform_wallet_payments', [
            'wallet_currency' => 'GBP',
            'pay_currency' => 'GBP',
            'status' => 'captured',
        ]);
        $this->assertEquals(
            150.0,
            (float) Wallet::where('user_id', $buyer->id)->where('currency', 'GBP')->value('balance')
        );
    }

    public function test_failed_payment_does_not_finalize_order_or_transfer_token(): void
    {
        [$buyer, $seller, $order, $tokenId, $listingId] = $this->seedPendingSaleOrder();

        $intent = $this->simulatePayment($order, $buyer, 'mock_bank', 'fail', [
            'bank_account_name' => '9Mint Ltd',
            'bank_sort_code' => '12-34-56',
            'bank_account_number' => '12345678',
            'bank_reference' => 'ORDER-' . $order->id,
        ]);

        $this->assertSame('failed', $intent->status);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'failed',
        ]);
        $this->assertDatabaseHas('listings', [
            'id' => $listingId,
            'status' => 'active',
        ]);
        $this->assertSame(
            $seller->id,
            app(\App\Services\OwnershipService::class)->ownerUserIdForToken($tokenId)
        );
        $this->assertDatabaseMissing('sales_histories', [
            'order_id' => $order->id,
        ]);
    }

    public function test_creator_fee_wallet_checkout_uses_payment_entity_and_preserves_hold_state(): void
    {
        $creator = User::factory()->create();
        Wallet::create([
            'user_id' => $creator->id,
            'currency' => 'GBP',
            'balance' => 100,
        ]);

        $collection = Collection::create([
            'slug' => 'creator-fee-wallet-checkout',
            'name' => 'Creator Fee Wallet Checkout',
            'submitted_by_user_id' => $creator->id,
            'creator_name' => $creator->name,
            'approval_status' => Collection::APPROVAL_PENDING,
            'is_public' => false,
            'creation_fee_payment_state' => 'unpaid',
            'creation_fee_refund_state' => 'none',
        ]);

        $response = $this->actingAs($creator)
            ->withSession(['creator_fee_collection_id' => $collection->id])
            ->post(route('orders.store'), $this->checkoutPayload([
                'checkout_context' => 'creator_fee',
                'provider' => 'mock_wallet',
                'wallet_currency' => 'GBP',
            ]));

        $response->assertRedirect(route('creator.collections.create'));
        $response->assertSessionHas('status');

        $collection->refresh();
        $this->assertSame('held_wallet', $collection->creation_fee_payment_state);
        $this->assertSame('mock_wallet', $collection->creation_fee_provider);
        $this->assertNotNull($collection->creation_fee_payment_intent_id);
        $this->assertNotNull($collection->creation_fee_hold_reference);
        $this->assertDatabaseHas('platform_wallet_payments', [
            'hold_reference' => $collection->creation_fee_hold_reference,
            'status' => 'held',
        ]);
    }

    private function seedPendingSaleOrder(): array
    {
        User::create([
            'name' => '9Mint',
            'email' => 'platform@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'wallet_address' => '0xaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ]);

        $buyer = User::create([
            'name' => 'buyer-user',
            'email' => 'buyer@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'wallet_address' => '0xbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
        ]);

        $seller = User::create([
            'name' => 'seller-user',
            'email' => 'seller@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'wallet_address' => '0xcccccccccccccccccccccccccccccccccccccccc',
        ]);

        DB::table('collections')->insert([
            'slug' => 'payment-rail-collection',
            'name' => 'Payment Rail Collection',
            'description' => 'Test',
            'cover_image_url' => '/images/test.png',
            'creator_name' => $seller->name,
            'submitted_by_user_id' => $seller->id,
            'approval_status' => 'approved',
            'is_public' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $collectionId = (int) DB::table('collections')->where('slug', 'payment-rail-collection')->value('id');

        DB::table('nfts')->insert([
            'collection_id' => $collectionId,
            'slug' => 'payment-rail-nft',
            'name' => 'Payment Rail NFT',
            'description' => 'NFT for payment tests',
            'image_url' => '/images/test-nft.png',
            'thumbnail_url' => '/images/test-nft.png',
            'currency_code' => 'ETH',
            'price_crypto' => 1,
            'primary_ref_amount' => 100,
            'primary_ref_currency' => 'GBP',
            'editions_total' => 1,
            'editions_remaining' => 1,
            'is_active' => true,
            'approval_status' => 'approved',
            'submitted_by_user_id' => $seller->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $nftId = (int) DB::table('nfts')->where('slug', 'payment-rail-nft')->value('id');

        DB::table('orders')->insert([
            'user_id' => $buyer->id,
            'status' => 'pending',
            'pay_currency' => 'GBP',
            'pay_total_amount' => 100,
            'ref_currency' => 'GBP',
            'ref_total_amount' => 100,
            'placed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $orderId = (int) DB::table('orders')->max('id');

        DB::table('nft_tokens')->insert([
            'nft_id' => $nftId,
            'serial_number' => 1,
            'owner_user_id' => $seller->id,
            'first_sale_order_id' => null,
            'status' => 'owned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tokenId = (int) DB::table('nft_tokens')->max('id');

        DB::table('listings')->insert([
            'token_id' => $tokenId,
            'seller_user_id' => $seller->id,
            'status' => 'active',
            'ref_amount' => 100,
            'ref_currency' => 'GBP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $listingId = (int) DB::table('listings')->max('id');

        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'listing_id' => $listingId,
            'token_id' => $tokenId,
            'quantity' => 1,
            'ref_unit_amount' => 100,
            'ref_currency' => 'GBP',
            'pay_unit_amount' => 100,
            'pay_currency' => 'GBP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = Order::with('items.listing.token')->findOrFail($orderId);

        return [$buyer, $seller, $order, $tokenId, $listingId];
    }

    private function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'checkout_context' => 'cart',
            'full_name' => 'Test User',
            'address' => '123 Test Street',
            'city' => 'Testville',
            'postal_code' => 'TE57 1NG',
        ], $overrides);
    }
}
