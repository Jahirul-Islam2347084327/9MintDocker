<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChainCurrentOwnership extends Model
{
    protected $table = 'chain_current_ownership';

    protected $fillable = [
        'chain_token_id',
        'chain_account_id',
        'acquired_via_event_id',
        'acquired_at',
        'metadata',
    ];

    protected $casts = [
        'acquired_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function chainToken(): BelongsTo
    {
        return $this->belongsTo(ChainToken::class);
    }

    public function chainAccount(): BelongsTo
    {
        return $this->belongsTo(ChainAccount::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(ChainTokenEvent::class, 'acquired_via_event_id');
    }
}
