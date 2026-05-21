<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentOrchestratorService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function pay(Request $request, string $orderId)
    {
        $user = $request->user();
        $data = $request->validate([
            'provider' => ['required', 'in:mock_bank,mock_crypto,mock_wallet'],
            'outcome' => ['nullable', 'in:success,fail'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_sort_code' => ['nullable', 'string', 'max:50'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_reference' => ['nullable', 'string', 'max:255'],
            'wallet_address' => ['nullable', 'string', 'max:255'],
            'wallet_tag' => ['nullable', 'string', 'max:255'],
            'wallet_network' => ['nullable', 'string', 'max:20'],
            'wallet_currency' => ['nullable', 'string', 'max:10'],
        ]);

        $order = Order::with('items.listing.token')
            ->where('id', $orderId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($order->status !== 'pending') {
            abort(422, 'Order is not payable');
        }

        if ($order->expires_at && $order->expires_at->isPast()) {
            $order->update(['status' => 'expired']);
            abort(422, 'Checkout expired');
        }

        $outcome = $data['outcome'] ?? 'success';

        $intent = app(PaymentOrchestratorService::class)->processOrderPayment(
            $order,
            $user,
            $data['provider'],
            $data,
            $outcome
        );

        return response()->json(['data' => $intent]);
    }
}
