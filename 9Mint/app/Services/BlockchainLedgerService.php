<?php

namespace App\Services;

use App\Models\ChainCurrentOwnership;
use App\Models\ChainToken;
use App\Models\ChainTokenEvent;
use App\Models\ChainTransaction;
use App\Models\NftToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BlockchainLedgerService
{
    public function __construct(private BlockchainAccountService $accounts)
    {
    }

    public function ensureChainTokenForToken(NftToken|int $token): ChainToken
    {
        $tokenModel = $token instanceof NftToken
            ? $token->loadMissing('nft')
            : NftToken::with('nft')->findOrFail($token);

        return ChainToken::firstOrCreate(
            ['nft_token_id' => $tokenModel->id],
            [
                'nft_id' => $tokenModel->nft_id,
                'serial_number' => $tokenModel->serial_number,
                'first_sale_order_id' => $tokenModel->first_sale_order_id,
                'minted_at' => $tokenModel->created_at,
                'metadata' => [
                    'source' => 'app_runtime',
                ],
            ]
        );
    }

    public function backfillLegacyOwnershipState(): int
    {
        $processed = 0;

        NftToken::query()
            ->with('nft')
            ->orderBy('id')
            ->chunk(100, function (Collection $tokens) use (&$processed) {
                foreach ($tokens as $token) {
                    $chainToken = $this->ensureChainTokenForToken($token);
                    $hasOwnership = ChainCurrentOwnership::where('chain_token_id', $chainToken->id)->exists();

                    if ($hasOwnership) {
                        continue;
                    }

                    $this->recordOwnershipEvent($token, $token->owner_user_id, [
                        'transaction_type' => 'snapshot',
                        'event_type' => 'snapshot_owner',
                        'provider' => 'runtime_backfill',
                        'metadata' => [
                            'source' => 'runtime_backfill',
                        ],
                    ]);

                    $processed++;
                }
            });

        return $processed;
    }

    public function transferToken(NftToken|int $token, ?int $toUserId, array $context = []): ChainTokenEvent
    {
        return $this->recordOwnershipEvent($token, $toUserId, array_merge([
            'transaction_type' => 'transfer',
            'event_type' => 'transfer',
        ], $context));
    }

    public function recordOwnershipEvent(NftToken|int $token, ?int $toUserId, array $context = []): ChainTokenEvent
    {
        return DB::transaction(function () use ($token, $toUserId, $context) {
            $tokenModel = $token instanceof NftToken
                ? $token->loadMissing('nft')
                : NftToken::with('nft')->findOrFail($token);

            $chainToken = $this->ensureChainTokenForToken($tokenModel);
            $currentOwnership = ChainCurrentOwnership::query()
                ->where('chain_token_id', $chainToken->id)
                ->lockForUpdate()
                ->first();

            $fromAccountId = $currentOwnership?->chain_account_id;
            $toAccountId = $toUserId ? $this->accounts->ensureForUser($toUserId)->id : null;
            $occurredAt = $context['occurred_at'] ?? now();

            $transaction = ChainTransaction::create([
                'type' => $context['transaction_type'] ?? 'transfer',
                'status' => $context['status'] ?? 'confirmed',
                'initiated_by_user_id' => $context['initiated_by_user_id'] ?? $toUserId,
                'order_id' => $context['order_id'] ?? null,
                'listing_id' => $context['listing_id'] ?? null,
                'provider' => $context['provider'] ?? null,
                'occurred_at' => $occurredAt,
                'metadata' => array_merge([
                    'nft_token_id' => $tokenModel->id,
                ], (array) ($context['metadata'] ?? [])),
            ]);

            $event = ChainTokenEvent::create([
                'chain_transaction_id' => $transaction->id,
                'chain_token_id' => $chainToken->id,
                'event_type' => $context['event_type'] ?? 'transfer',
                'from_chain_account_id' => $fromAccountId,
                'to_chain_account_id' => $toAccountId,
                'occurred_at' => $occurredAt,
                'metadata' => array_merge([
                    'legacy_owner_user_id_before' => $tokenModel->owner_user_id,
                    'legacy_owner_user_id_after' => $toUserId,
                ], (array) ($context['event_metadata'] ?? [])),
            ]);

            ChainCurrentOwnership::updateOrCreate(
                ['chain_token_id' => $chainToken->id],
                [
                    'chain_account_id' => $toAccountId,
                    'acquired_via_event_id' => $event->id,
                    'acquired_at' => $occurredAt,
                    'metadata' => [
                        'transaction_id' => $transaction->id,
                    ],
                ]
            );

            $legacyUpdate = [
                'owner_user_id' => $toUserId,
            ];

            if ($toUserId) {
                $legacyUpdate['status'] = 'owned';
            }

            if (empty($tokenModel->first_sale_order_id) && ! empty($context['order_id'])) {
                $legacyUpdate['first_sale_order_id'] = $context['order_id'];
                $chainToken->forceFill(['first_sale_order_id' => $context['order_id']])->save();
            }

            $tokenModel->forceFill($legacyUpdate)->save();

            return $event->fresh(['chainToken', 'transaction', 'toAccount', 'fromAccount']);
        });
    }
}
