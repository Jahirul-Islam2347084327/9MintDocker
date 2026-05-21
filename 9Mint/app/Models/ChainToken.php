<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChainToken extends Model
{
    protected $fillable = [
        'nft_token_id',
        'nft_id',
        'serial_number',
        'first_sale_order_id',
        'minted_at',
        'metadata',
    ];

    protected $casts = [
        'minted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function nftToken(): BelongsTo
    {
        return $this->belongsTo(NftToken::class);
    }

    public function nft(): BelongsTo
    {
        return $this->belongsTo(Nft::class);
    }

    public function firstSaleOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'first_sale_order_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ChainTokenEvent::class);
    }

    public function currentOwnership(): HasOne
    {
        return $this->hasOne(ChainCurrentOwnership::class);
    }
}
