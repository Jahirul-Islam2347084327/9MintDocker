<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentOrchestratorService
{
    public function __construct(
        private BankPaymentService $bankPayments,
        private CryptoPaymentService $cryptoPayments,
        private PlatformWalletPaymentService $platformWalletPayments,
        private OrderFinalizationService $orderFinalization,
    ) {
    }

    public function processOrderPayment(Order $order, User $user, string $provider, array $payload = [], string $outcome = 'success'): PaymentIntent
    {
        return DB::transaction(function () use ($order, $user, $provider, $payload, $outcome) {
            $intent = match ($provider) {
                'mock_bank' => $this->bankPayments->capturePayment($order, $payload, $outcome, [
                    'context' => 'order_checkout',
                    'amount' => (float) ($order->pay_total_amount ?? 0),
                    'currency' => (string) ($order->pay_currency ?? 'GBP'),
                ]),
                'mock_crypto' => $this->cryptoPayments->capturePayment($order, $user, $payload, $outcome, [
                    'context' => 'order_checkout',
                    'amount' => (float) ($order->pay_total_amount ?? 0),
                    'currency' => (string) ($order->pay_currency ?? 'GBP'),
                ]),
                'mock_wallet' => $this->platformWalletPayments->captureOrderPayment($order, $user, $payload, $outcome, [
                    'context' => 'order_checkout',
                    'amount' => (float) ($order->pay_total_amount ?? 0),
                    'currency' => (string) ($order->pay_currency ?? 'GBP'),
                ]),
                default => throw new \InvalidArgumentException('Unsupported payment provider.'),
            };

            if ($intent->status === 'captured') {
                $this->orderFinalization->finalizeCapturedOrder($order, $user, $provider, $intent);
            } else {
                $this->orderFinalization->markFailedOrderPayment($order, $intent);
            }

            return $intent->fresh(['bankRequest', 'cryptoRequest', 'platformWalletPayment']);
        });
    }

    public function processCreatorFeePayment(Order $order, User $user, string $provider, array $payload = [], string $outcome = 'success', array $context = []): array
    {
        return DB::transaction(function () use ($order, $user, $provider, $payload, $outcome, $context) {
            $commonMetadata = array_merge([
                'context' => 'creator_fee',
                'collection_id' => $context['collection_id'] ?? null,
                'creator_fee_draft_id' => $context['creator_fee_draft_id'] ?? null,
                'collection_name' => $context['collection_name'] ?? null,
                'nft_count' => $context['nft_count'] ?? null,
                'amount' => (float) ($order->pay_total_amount ?? 0),
                'currency' => (string) ($order->pay_currency ?? 'GBP'),
            ], $context['metadata'] ?? []);

            if ($provider === 'mock_wallet') {
                $hold = $this->platformWalletPayments->placeCreatorFeeHold($order, $user, $payload, $commonMetadata);

                return [
                    'intent' => $hold['intent'],
                    'creation_fee_payment_state' => 'held_wallet',
                    'creation_fee_provider' => $provider,
                    'creation_fee_hold_currency' => $hold['hold_currency'],
                    'creation_fee_hold_amount' => $hold['hold_amount'],
                    'creation_fee_hold_reference' => $hold['hold_reference'],
                ];
            }

            $intent = match ($provider) {
                'mock_bank' => $this->bankPayments->capturePayment($order, $payload, $outcome, $commonMetadata),
                'mock_crypto' => $this->cryptoPayments->capturePayment($order, $user, $payload, $outcome, $commonMetadata),
                default => throw new \InvalidArgumentException('Unsupported payment provider.'),
            };

            return [
                'intent' => $intent,
                'creation_fee_payment_state' => $intent->status === 'captured' ? 'paid_unheld' : 'unpaid',
                'creation_fee_provider' => $provider,
                'creation_fee_hold_currency' => null,
                'creation_fee_hold_amount' => null,
                'creation_fee_hold_reference' => null,
            ];
        });
    }
}
