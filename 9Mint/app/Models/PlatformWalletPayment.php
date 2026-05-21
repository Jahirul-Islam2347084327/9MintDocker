<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformWalletPayment extends Model
{
    protected $fillable = [
        'payment_intent_id',
        'wallet_currency',
        'pay_currency',
        'pay_amount',
        'wallet_amount',
        'status',
        'hold_reference',
        'wallet_transaction_id',
        'fx_provider',
        'fx_rate',
        'fx_rated_at',
        'captured_at',
        'failed_at',
        'metadata',
    ];

    protected $casts = [
        'pay_amount' => 'decimal:18',
        'wallet_amount' => 'decimal:18',
        'fx_rate' => 'array',
        'fx_rated_at' => 'datetime',
        'captured_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }
}
