<?php

namespace App\Services;

use App\Models\ChainAccount;
use App\Models\User;

class BlockchainAccountService
{
    public function __construct(private WalletAddressService $walletAddressService)
    {
    }

    public function forUser(User|int $user, bool $createIfMissing = false): ?ChainAccount
    {
        $userModel = $user instanceof User ? $user : User::find($user);
        if (! $userModel) {
            return null;
        }

        $account = ChainAccount::where('user_id', $userModel->id)->first();
        if ($account || ! $createIfMissing) {
            return $account;
        }

        return $this->ensureForUser($userModel);
    }

    public function ensureForUser(User|int $user): ChainAccount
    {
        $userModel = $user instanceof User ? $user : User::findOrFail($user);

        $existing = ChainAccount::where('user_id', $userModel->id)->first();
        if ($existing) {
            return $existing;
        }

        $address = $userModel->wallet_address ?: $this->walletAddressService->assignGeneratedAddress($userModel);

        return ChainAccount::create([
            'user_id' => $userModel->id,
            'address' => $this->walletAddressService->normalize($address) ?? $address,
            'network' => 'sim',
            'label' => $userModel->name,
            'metadata' => [
                'source' => 'app_runtime',
            ],
        ]);
    }
}
