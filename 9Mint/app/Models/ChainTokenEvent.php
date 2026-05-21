<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChainTokenEvent extends Model
{
    protected $fillable = [
        'chain_transaction_id',
        'chain_token_id',
        'event_type',
        'from_chain_account_id',
        'to_chain_account_id',
        'occurred_at',
        'metadata',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(ChainTransaction::class, 'chain_transaction_id');
    }

    public function chainToken(): BelongsTo
    {
        return $this->belongsTo(ChainToken::class);
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(ChainAccount::class, 'from_chain_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(ChainAccount::class, 'to_chain_account_id');
    }
}
