<?php

namespace App\Services;

use App\Models\NftToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OwnershipService
{
    public function ownedTokensQueryForUser(int $userId): Builder
    {
        return NftToken::query()->where(function (Builder $query) use ($userId) {
            $query->whereIn('id', $this->ownedTokenIdsSubquery($userId))
                ->orWhere(function (Builder $legacyQuery) use ($userId) {
                    $legacyQuery->where('owner_user_id', $userId)
                        ->whereNotIn('id', DB::table('chain_tokens')->select('nft_token_id'));
                });
        });
    }

    public function ownedTokensQueryForNft(int $userId, int $nftId): Builder
    {
        return $this->ownedTokensQueryForUser($userId)->where('nft_id', $nftId);
    }

    public function ownedTokenIdsForUser(int $userId): array
    {
        return $this->ownedTokensQueryForUser($userId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function countOwnedTokensForUser(int $userId): int
    {
        return (int) $this->ownedTokensQueryForUser($userId)->count();
    }

    public function userOwnsToken(int $userId, int $tokenId): bool
    {
        return $this->ownedTokensQueryForUser($userId)
            ->whereKey($tokenId)
            ->exists();
    }

    public function ownerUserIdForToken(int $tokenId): ?int
    {
        $userId = DB::table('chain_current_ownership as ownership')
            ->join('chain_tokens as chain_tokens', 'chain_tokens.id', '=', 'ownership.chain_token_id')
            ->join('chain_accounts as accounts', 'accounts.id', '=', 'ownership.chain_account_id')
            ->where('chain_tokens.nft_token_id', $tokenId)
            ->value('accounts.user_id');

        if ($userId) {
            return (int) $userId;
        }

        $legacyOwnerId = NftToken::query()
            ->whereKey($tokenId)
            ->whereNotIn('id', DB::table('chain_tokens')->select('nft_token_id'))
            ->value('owner_user_id');

        return $legacyOwnerId ? (int) $legacyOwnerId : null;
    }

    private function ownedTokenIdsSubquery(int $userId)
    {
        return DB::table('chain_current_ownership as ownership')
            ->select('chain_tokens.nft_token_id')
            ->join('chain_accounts as accounts', 'accounts.id', '=', 'ownership.chain_account_id')
            ->join('chain_tokens as chain_tokens', 'chain_tokens.id', '=', 'ownership.chain_token_id')
            ->where('accounts.user_id', $userId);
    }
}
