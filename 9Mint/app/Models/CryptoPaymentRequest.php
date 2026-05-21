<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoPaymentRequest extends Model
{
    protected $fillable = [
        'payment_intent_id',
        'payer_address',
        'payer_tag',
        'network',
        'pay_currency',
        'pay_amount',
        'destination_address',
        'transaction_reference',
        'status',
        'captured_at',
        'failed_at',
        'metadata',
    ];

    protected $casts = [
        'pay_amount' => 'decimal:18',
        'captured_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class);
    }
}
