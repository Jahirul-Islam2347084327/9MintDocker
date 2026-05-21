<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentIntent extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'order_id',
        'provider',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($intent) {
            if (empty($intent->id)) {
                $intent->id = (string) Str::uuid();
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function bankRequest(): HasOne
    {
        return $this->hasOne(BankPaymentRequest::class);
    }

    public function cryptoRequest(): HasOne
    {
        return $this->hasOne(CryptoPaymentRequest::class);
    }

    public function platformWalletPayment(): HasOne
    {
        return $this->hasOne(PlatformWalletPayment::class);
    }
}
