<?php

namespace App\Services;

use App\Models\CryptoPaymentRequest;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\User;
use Illuminate\Support\Str;

class CryptoPaymentService
{
    public function __construct(private BlockchainAccountService $accounts)
    {
    }

    public function capturePayment(Order $order, User $payer, array $payload, string $outcome = 'success', array $metadata = []): PaymentIntent
    {
        $intent = PaymentIntent::create([
            'order_id' => $order->id,
            'provider' => 'mock_crypto',
            'status' => 'created',
            'metadata' => array_merge([
                'requested_at' => now()->toIso8601String(),
            ], $metadata),
        ]);

        $platformUser = User::where('name', '9Mint')->first();
        $destinationAddress = $platformUser
            ? $this->accounts->ensureForUser($platformUser)->address
            : '0x' . str_repeat('0', 40);

        $request = CryptoPaymentRequest::create([
            'payment_intent_id' => $intent->id,
            'payer_address' => (string) ($payload['wallet_address'] ?? ''),
            'payer_tag' => (string) ($payload['wallet_tag'] ?? ''),
            'network' => strtoupper((string) ($payload['wallet_network'] ?? $order->pay_currency ?? 'ETH')),
            'pay_currency' => strtoupper((string) ($order->pay_currency ?? 'GBP')),
            'pay_amount' => (float) ($order->pay_total_amount ?? 0),
            'destination_address' => $destinationAddress,
            'transaction_reference' => $outcome === 'success' ? ('mock-tx-' . Str::uuid()) : null,
            'status' => $outcome === 'success' ? 'captured' : 'failed',
            'captured_at' => $outcome === 'success' ? now() : null,
            'failed_at' => $outcome === 'success' ? null : now(),
            'metadata' => [
                'payer_user_id' => $payer->id,
            ],
        ]);

        $intent->update([
            'status' => $outcome === 'success' ? 'captured' : 'failed',
            'metadata' => array_merge((array) ($intent->metadata ?? []), [
                'crypto_payment_request_id' => $request->id,
            ]),
        ]);

        return $intent->fresh('cryptoRequest');
    }
}
