<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\PlatformWalletPayment;
use App\Models\User;

class PlatformWalletPaymentService
{
    public function __construct(private WalletService $wallets)
    {
    }

    public function captureOrderPayment(Order $order, User $user, array $payload, string $outcome = 'success', array $metadata = []): PaymentIntent
    {
        $intent = PaymentIntent::create([
            'order_id' => $order->id,
            'provider' => 'mock_wallet',
            'status' => 'created',
            'metadata' => array_merge([
                'requested_at' => now()->toIso8601String(),
            ], $metadata),
        ]);

        $walletCurrency = strtoupper((string) ($payload['wallet_currency'] ?? $order->pay_currency ?? 'GBP'));
        $payAmount = (float) ($order->pay_total_amount ?? 0);
        $payCurrency = strtoupper((string) ($order->pay_currency ?? 'GBP'));
        $conversion = $this->wallets->convertAmount($payAmount, $payCurrency, $walletCurrency);
        $walletAmount = (float) $conversion['amount'];

        $payment = PlatformWalletPayment::create([
            'payment_intent_id' => $intent->id,
            'wallet_currency' => $walletCurrency,
            'pay_currency' => $payCurrency,
            'pay_amount' => $payAmount,
            'wallet_amount' => $walletAmount,
            'status' => 'created',
            'fx_provider' => $conversion['fx_provider'],
            'fx_rate' => [
                'from' => $payCurrency,
                'to' => $walletCurrency,
                'rate' => $conversion['fx_rate'],
            ],
            'fx_rated_at' => $conversion['fx_rated_at'],
            'metadata' => [
                'mode' => 'order_checkout',
            ],
        ]);

        if ($outcome === 'success') {
            $walletTransaction = $this->wallets->debit($user->id, $walletCurrency, $walletAmount, [
                'order_id' => $order->id,
                'fx_provider' => $conversion['fx_provider'],
                'fx_rate' => [
                    'from' => $payCurrency,
                    'to' => $walletCurrency,
                    'rate' => $conversion['fx_rate'],
                ],
                'fx_rated_at' => $conversion['fx_rated_at'],
                'metadata' => [
                    'source' => 'wallet_checkout',
                    'pay_currency' => $payCurrency,
                    'pay_amount' => $payAmount,
                    'payment_intent_id' => $intent->id,
                ],
            ]);

            $payment->update([
                'status' => 'captured',
                'wallet_transaction_id' => $walletTransaction->id,
                'captured_at' => now(),
            ]);

            $intent->update([
                'status' => 'captured',
                'metadata' => array_merge((array) ($intent->metadata ?? []), [
                    'platform_wallet_payment_id' => $payment->id,
                ]),
            ]);
        } else {
            $payment->update([
                'status' => 'failed',
                'failed_at' => now(),
            ]);

            $intent->update([
                'status' => 'failed',
                'metadata' => array_merge((array) ($intent->metadata ?? []), [
                    'platform_wallet_payment_id' => $payment->id,
                ]),
            ]);
        }

        return $intent->fresh('platformWalletPayment');
    }

    public function placeCreatorFeeHold(Order $order, User $user, array $payload, array $metadata = []): array
    {
        $intent = PaymentIntent::create([
            'order_id' => $order->id,
            'provider' => 'mock_wallet',
            'status' => 'captured',
            'metadata' => array_merge([
                'requested_at' => now()->toIso8601String(),
            ], $metadata),
        ]);

        $walletCurrency = strtoupper((string) ($payload['wallet_currency'] ?? 'GBP'));
        $payAmount = (float) ($order->pay_total_amount ?? 0);
        $payCurrency = strtoupper((string) ($order->pay_currency ?? 'GBP'));
        $conversion = $this->wallets->convertAmount($payAmount, $payCurrency, $walletCurrency);

        $hold = $this->wallets->placeHold(
            $user->id,
            $walletCurrency,
            (float) $conversion['amount'],
            [
                'order_id' => $order->id,
                'collection_id' => $metadata['collection_id'] ?? null,
                'creator_fee_draft_id' => $metadata['creator_fee_draft_id'] ?? null,
                'fx_provider' => $conversion['fx_provider'],
                'fx_rate' => [
                    'from' => $payCurrency,
                    'to' => $walletCurrency,
                    'rate' => $conversion['fx_rate'],
                ],
                'fx_rated_at' => $conversion['fx_rated_at'],
                'pay_currency' => $payCurrency,
                'pay_amount' => $payAmount,
            ]
        );

        $payment = PlatformWalletPayment::create([
            'payment_intent_id' => $intent->id,
            'wallet_currency' => $walletCurrency,
            'pay_currency' => $payCurrency,
            'pay_amount' => $payAmount,
            'wallet_amount' => (float) $conversion['amount'],
            'status' => 'held',
            'hold_reference' => $hold['hold_reference'],
            'wallet_transaction_id' => $hold['transaction']->id,
            'fx_provider' => $conversion['fx_provider'],
            'fx_rate' => [
                'from' => $payCurrency,
                'to' => $walletCurrency,
                'rate' => $conversion['fx_rate'],
            ],
            'fx_rated_at' => $conversion['fx_rated_at'],
            'captured_at' => now(),
            'metadata' => [
                'mode' => 'creator_fee_hold',
            ],
        ]);

        $intent->update([
            'metadata' => array_merge((array) ($intent->metadata ?? []), [
                'platform_wallet_payment_id' => $payment->id,
            ]),
        ]);

        return [
            'intent' => $intent->fresh('platformWalletPayment'),
            'hold_reference' => $hold['hold_reference'],
            'hold_currency' => $walletCurrency,
            'hold_amount' => (float) $conversion['amount'],
        ];
    }
}
