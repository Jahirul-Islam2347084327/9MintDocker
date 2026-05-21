<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankPaymentRequest extends Model
{
    protected $fillable = [
        'payment_intent_id',
        'account_name',
        'sort_code',
        'account_number',
        'reference',
        'status',
        'captured_at',
        'failed_at',
        'metadata',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function paymentIntent(): BelongsTo
    {
        return $this->belongsTo(PaymentIntent::class);
    }
}
