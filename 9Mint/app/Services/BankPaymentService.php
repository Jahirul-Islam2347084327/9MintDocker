<?php

namespace App\Services;

use App\Models\BankPaymentRequest;
use App\Models\Order;
use App\Models\PaymentIntent;

class BankPaymentService
{
    public function capturePayment(Order $order, array $payload, string $outcome = 'success', array $metadata = []): PaymentIntent
    {
        $intent = PaymentIntent::create([
            'order_id' => $order->id,
            'provider' => 'mock_bank',
            'status' => 'created',
            'metadata' => array_merge([
                'requested_at' => now()->toIso8601String(),
            ], $metadata),
        ]);

        $request = BankPaymentRequest::create([
            'payment_intent_id' => $intent->id,
            'account_name' => (string) ($payload['bank_account_name'] ?? '9Mint Ltd'),
            'sort_code' => (string) ($payload['bank_sort_code'] ?? ''),
            'account_number' => (string) ($payload['bank_account_number'] ?? ''),
            'reference' => (string) ($payload['bank_reference'] ?? ('ORDER-' . $order->id)),
            'status' => $outcome === 'success' ? 'captured' : 'failed',
            'captured_at' => $outcome === 'success' ? now() : null,
            'failed_at' => $outcome === 'success' ? null : now(),
            'metadata' => [
                'pay_currency' => $order->pay_currency,
                'pay_total_amount' => $order->pay_total_amount,
            ],
        ]);

        $intent->update([
            'status' => $outcome === 'success' ? 'captured' : 'failed',
            'metadata' => array_merge((array) ($intent->metadata ?? []), [
                'bank_payment_request_id' => $request->id,
            ]),
        ]);

        return $intent->fresh('bankRequest');
    }
}
