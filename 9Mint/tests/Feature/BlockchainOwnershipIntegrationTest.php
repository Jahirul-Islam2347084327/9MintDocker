<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\BlockchainLedgerService;
use App\Services\OwnershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutVite;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\PaymentTestHelper;

class BlockchainOwnershipIntegrationTest extends TestCase
{
    use PaymentTestHelper;
    use RefreshDatabase;
    use WithoutVite;

    public function test_backfill_service_preserves_legacy_owner_as_effective_owner(): void
    {
        [, $seller, , $tokenId] = $this->seedSaleData();

        app(BlockchainLedgerService::class)->backfillLegacyOwnershipState();

        $ownerId = app(OwnershipService::class)->ownerUserIdForToken($tokenId);

        $this->assertSame($seller->id, $ownerId);
        $this->assertDatabaseHas('chain_tokens', [
            'nft_token_id' => $tokenId,
        ]);
        $this->assertDatabaseHas('chain_transactions', [
            'type' => 'snapshot',
            'provider' => 'runtime_backfill',
        ]);
    }

    public function test_payment_success_records_chain_transfer_and_updates_effective_owner(): void
    {
        [$buyer, , $order, $tokenId] = $this->seedSaleData();

        $this->simulatePayment($order, $buyer, 'mock_wallet', 'success');

        $ownerId = app(OwnershipService::class)->ownerUserIdForToken($tokenId);

        $this->assertSame($buyer->id, $ownerId);
        $this->assertDatabaseHas('chain_transactions', [
            'order_id' => $order->id,
            'type' => 'primary_sale',
            'provider' => 'mock_wallet',
        ]);
        $this->assertDatabaseHas('chain_token_events', [
            'event_type' => 'sale_transfer',
        ]);
    }

    public function test_listing_creation_uses_chain_ownership_when_legacy_owner_is_stale(): void
    {
        [$buyer, $seller, $order, $tokenId] = $this->seedSaleData();

        $this->simulatePayment($order, $buyer, 'mock_wallet', 'success');

        DB::table('order_items')
            ->where('order_id', $order->id)
            ->where('token_id', $tokenId)
            ->update([
                'lifecycle_status' => OrderItem::LIFECYCLE_FINALIZED,
                'hold_expires_at' => now()->subDay(),
                'hold_extended_until' => null,
                'finalized_at' => now(),
            ]);

        DB::table('nft_tokens')
            ->where('id', $tokenId)
            ->update([
                'owner_user_id' => $seller->id,
            ]);

        $this->actingAs($buyer)
            ->post(route('inventory.listing.store'), [
                'token_id' => $tokenId,
                'ref_amount' => 149.99,
                'ref_currency' => 'GBP',
            ])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('listings', [
            'token_id' => $tokenId,
            'seller_user_id' => $buyer->id,
            'status' => 'active',
        ]);
    }

    public function test_inventory_page_uses_chain_ownership_when_legacy_owner_is_stale(): void
    {
        [$buyer, $seller, $order, $tokenId] = $this->seedSaleData();

        $this->simulatePayment($order, $buyer, 'mock_wallet', 'success');

        DB::table('nft_tokens')
            ->where('id', $tokenId)
            ->update([
                'owner_user_id' => $seller->id,
            ]);

        $this->actingAs($buyer)
            ->get(route('inventory.show', ['username' => $buyer->name]))
            ->assertOk()
            ->assertSee('Test NFT');

        $this->actingAs($seller)
            ->get(route('inventory.show', ['username' => $seller->name]))
            ->assertOk()
            ->assertDontSee('Test NFT')
            ->assertSee('You do not own any tokens yet.');
    }

    private function seedSaleData(): array
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
            'nfts_public' => true,
        ]);

        DB::table('collections')->insert([
            'slug' => 'test-collection',
            'name' => 'Test Collection',
            'description' => 'Test',
            'cover_image_url' => '/images/test.png',
            'creator_name' => $seller->name,
            'submitted_by_user_id' => $seller->id,
            'approval_status' => 'approved',
            'is_public' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $collectionId = (int) DB::table('collections')->where('slug', 'test-collection')->value('id');

        DB::table('nfts')->insert([
            'collection_id' => $collectionId,
            'slug' => 'test-nft',
            'name' => 'Test NFT',
            'description' => 'NFT for tests',
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
        $nftId = (int) DB::table('nfts')->where('slug', 'test-nft')->value('id');

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

        return [$buyer, $seller, $order, $tokenId];
    }
}
