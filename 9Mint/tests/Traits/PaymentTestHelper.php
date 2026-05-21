<?php

namespace Tests\Traits;

use App\Models\Order;
use App\Models\PaymentIntent;
use App\Services\PaymentOrchestratorService;
use App\Services\WalletService;

trait PaymentTestHelper
{
    protected function simulatePayment(Order $order, $user, string $provider, string $outcome = 'success', array $payload = []): PaymentIntent
    {
        if ($provider === 'mock_wallet' && $outcome === 'success' && empty($payload)) {
            $walletCurrency = strtoupper((string) ($order->pay_currency ?? 'GBP'));
            $requiredAmount = (float) ($order->pay_total_amount ?? 0);
            $walletService = app(WalletService::class);
            $currentBalance = $walletService->getBalance((int) $user->id, $walletCurrency);

            if ($requiredAmount > $currentBalance) {
                $walletService->credit((int) $user->id, $walletCurrency, $requiredAmount - $currentBalance, [
                    'order_id' => $order->id,
                    'metadata' => [
                        'source' => 'compatibility_simulation_topup',
                    ],
                ]);
            }

            $payload = [
                'wallet_currency' => $walletCurrency,
            ];
        }

        return app(PaymentOrchestratorService::class)->processOrderPayment($order, $user, $provider, $payload, $outcome);
    }
}
