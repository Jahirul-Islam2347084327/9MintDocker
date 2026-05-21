<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $accountIdsByUser = [];

        DB::table('nft_tokens')
            ->select(['id', 'nft_id', 'serial_number', 'owner_user_id', 'first_sale_order_id', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(100, function ($tokens) use (&$accountIdsByUser) {
                foreach ($tokens as $token) {
                    $chainTokenId = DB::table('chain_tokens')
                        ->where('nft_token_id', $token->id)
                        ->value('id');

                    if (! $chainTokenId) {
                        $chainTokenId = DB::table('chain_tokens')->insertGetId([
                            'nft_token_id' => $token->id,
                            'nft_id' => $token->nft_id,
                            'serial_number' => $token->serial_number,
                            'first_sale_order_id' => $token->first_sale_order_id,
                            'minted_at' => $token->created_at,
                            'metadata' => json_encode(['source' => 'migration_backfill']),
                            'created_at' => $token->created_at ?? now(),
                            'updated_at' => $token->updated_at ?? now(),
                        ]);
                    }

                    $hasEvents = DB::table('chain_token_events')
                        ->where('chain_token_id', $chainTokenId)
                        ->exists();

                    if ($hasEvents) {
                        continue;
                    }

                    $ownerAccountId = $token->owner_user_id
                        ? $this->ensureChainAccountId((int) $token->owner_user_id, $accountIdsByUser)
                        : null;

                    $txId = DB::table('chain_transactions')->insertGetId([
                        'type' => 'snapshot',
                        'status' => 'confirmed',
                        'initiated_by_user_id' => $token->owner_user_id,
                        'order_id' => $token->first_sale_order_id,
                        'listing_id' => null,
                        'provider' => 'legacy_backfill',
                        'occurred_at' => $token->updated_at ?? $token->created_at ?? now(),
                        'metadata' => json_encode([
                            'source' => 'migration_backfill',
                            'legacy_nft_token_id' => $token->id,
                        ]),
                        'created_at' => $token->updated_at ?? $token->created_at ?? now(),
                        'updated_at' => $token->updated_at ?? $token->created_at ?? now(),
                    ]);

                    $eventId = DB::table('chain_token_events')->insertGetId([
                        'chain_transaction_id' => $txId,
                        'chain_token_id' => $chainTokenId,
                        'event_type' => 'snapshot_owner',
                        'from_chain_account_id' => null,
                        'to_chain_account_id' => $ownerAccountId,
                        'occurred_at' => $token->updated_at ?? $token->created_at ?? now(),
                        'metadata' => json_encode([
                            'source' => 'migration_backfill',
                            'legacy_owner_user_id' => $token->owner_user_id,
                        ]),
                        'created_at' => $token->updated_at ?? $token->created_at ?? now(),
                        'updated_at' => $token->updated_at ?? $token->created_at ?? now(),
                    ]);

                    DB::table('chain_current_ownership')->updateOrInsert(
                        ['chain_token_id' => $chainTokenId],
                        [
                            'chain_account_id' => $ownerAccountId,
                            'acquired_via_event_id' => $eventId,
                            'acquired_at' => $token->updated_at ?? $token->created_at ?? now(),
                            'metadata' => json_encode(['source' => 'migration_backfill']),
                            'updated_at' => $token->updated_at ?? $token->created_at ?? now(),
                            'created_at' => $token->created_at ?? now(),
                        ]
                    );
                }
            });
    }

    public function down(): void
    {
        // The schema migration handles teardown of chain simulation tables.
    }

    private function ensureChainAccountId(int $userId, array &$accountIdsByUser): int
    {
        if (isset($accountIdsByUser[$userId])) {
            return $accountIdsByUser[$userId];
        }

        $existingId = DB::table('chain_accounts')
            ->where('user_id', $userId)
            ->value('id');

        if ($existingId) {
            return $accountIdsByUser[$userId] = (int) $existingId;
        }

        $user = DB::table('users')
            ->select(['name', 'wallet_address'])
            ->where('id', $userId)
            ->first();

        $address = $this->normalizeAddress($user?->wallet_address);
        if (! $address) {
            $address = $this->generateUniqueAddress();
        }

        $accountId = DB::table('chain_accounts')->insertGetId([
            'user_id' => $userId,
            'address' => $address,
            'network' => 'sim',
            'label' => $user?->name ?: ('user-' . $userId),
            'metadata' => json_encode(['source' => 'migration_backfill']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $accountIdsByUser[$userId] = (int) $accountId;
    }

    private function normalizeAddress(?string $address): ?string
    {
        $value = trim((string) $address);
        if ($value === '') {
            return null;
        }

        return str_starts_with(strtolower($value), '0x')
            ? strtolower($value)
            : $value;
    }

    private function generateUniqueAddress(): string
    {
        do {
            $candidate = '0x' . bin2hex(random_bytes(20));
            $exists = DB::table('chain_accounts')->where('address', $candidate)->exists();
        } while ($exists);

        return $candidate;
    }
};
