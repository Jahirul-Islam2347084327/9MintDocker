<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerProfileFeedback extends Model
{
    public const TYPE_COMMENT = 'comment';
    public const TYPE_REVIEW = 'review';

    protected $table = 'seller_profile_feedback';

    protected $fillable = [
        'seller_user_id',
        'author_user_id',
        'rating',
        'comment_type',
        'body',
        'deleted_by_owner_at',
        'deleted_by_owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'deleted_by_owner_at' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function deletedByOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_owner_user_id');
    }

    public function scopeVisibleComments($query)
    {
        return $query
            ->whereNull('deleted_by_owner_at')
            ->whereNotNull('body')
            ->where('body', '!=', '');
    }

    public function scopeWithRating($query)
    {
        return $query->whereNotNull('rating');
    }
}
