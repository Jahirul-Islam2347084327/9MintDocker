<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChainAccount extends Model
{
    protected $fillable = [
        'user_id',
        'address',
        'network',
        'label',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ownerships(): HasMany
    {
        return $this->hasMany(ChainCurrentOwnership::class);
    }

    public function incomingEvents(): HasMany
    {
        return $this->hasMany(ChainTokenEvent::class, 'to_chain_account_id');
    }

    public function outgoingEvents(): HasMany
    {
        return $this->hasMany(ChainTokenEvent::class, 'from_chain_account_id');
    }
}
