<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Friendship;

class User extends Authenticatable
{
    public const ADMIN_VIEW_MODE_ADMIN = 'admin';
    public const ADMIN_VIEW_MODE_CUSTOMER = 'customer';

    public const NFTS_VISIBILITY_PUBLIC = 'public';
    public const NFTS_VISIBILITY_FRIENDS = 'friends';
    public const NFTS_VISIBILITY_PRIVATE = 'private';

    public const PROFILE_COMMENTS_VISIBILITY_PUBLIC = 'public';
    public const PROFILE_COMMENTS_VISIBILITY_FRIENDS = 'friends';
    public const PROFILE_COMMENTS_VISIBILITY_DISABLED = 'disabled';

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasApiTokens;
    use SoftDeletes;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'banned_at',
        'banned_by',
        'badges',
        'profile_image_url',
        'description',
        'wallet_address',
        'nfts_public',
        'nfts_visibility',
        'search_public',
        'profile_comments_public',
        'profile_comments_visibility',
        'google_id',
        'receives_email_notifications',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'nfts_public' => 'boolean',
            'search_public' => 'boolean',
            'profile_comments_public' => 'boolean',
            'banned_at' => 'datetime',
            'deleted_at' => 'datetime',
            'badges' => 'array',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->name === '9Mint';
    }

    public function hasAdminRole(): bool
    {
        return $this->isSuperAdmin() || strtolower((string) $this->role) === self::ADMIN_VIEW_MODE_ADMIN;
    }

    public function adminViewMode(): string
    {
        if (! $this->hasAdminRole()) {
            return self::ADMIN_VIEW_MODE_CUSTOMER;
        }

        $request = request();
        $mode = self::ADMIN_VIEW_MODE_ADMIN;

        if ($request && $request->hasSession()) {
            $mode = strtolower(trim((string) $request->session()->get('admin_view_mode', self::ADMIN_VIEW_MODE_ADMIN)));
        }

        return in_array($mode, [self::ADMIN_VIEW_MODE_ADMIN, self::ADMIN_VIEW_MODE_CUSTOMER], true)
            ? $mode
            : self::ADMIN_VIEW_MODE_ADMIN;
    }

    public function isInAdminView(): bool
    {
        return $this->hasAdminRole() && $this->adminViewMode() === self::ADMIN_VIEW_MODE_ADMIN;
    }

    public function isInCustomerView(): bool
    {
        return $this->hasAdminRole() && $this->adminViewMode() === self::ADMIN_VIEW_MODE_CUSTOMER;
    }

    public function canAccessAdminFeatures(): bool
    {
        return $this->isInAdminView();
    }

    public function isBanned(): bool
    {
        return ! is_null($this->banned_at);
    }

    /**
     * @return array<int, array{key:string,label:string,description:string}>
     */
    public function profileBadges(): array
    {
        $result = [];

        $pushBadge = function (string $key, string $label, string $description) use (&$result): void {
            if (isset($result[$key])) {
                return;
            }

            $result[$key] = [
                'key' => $key,
                'label' => $label,
                'description' => $description,
            ];
        };

        if ($this->isSuperAdmin()) {
            $pushBadge('superadmin', 'Superadmin', 'Full platform control, including assigning other admins.');
        } elseif (strtolower((string) $this->role) === 'admin') {
            $pushBadge('admin', 'Admin', 'Can moderate submissions and manage platform operations.');
        }

        if ($this->isBanned()) {
            $pushBadge('banned', 'Banned', 'Account is restricted from trading, purchases, and wallet actions.');
        }

        foreach ((array) ($this->badges ?? []) as $index => $badge) {
            if (is_string($badge)) {
                $label = trim($badge);
                if ($label === '') {
                    continue;
                }

                $key = 'custom_' . str($label)->slug('_')->toString();
                $pushBadge($key, $label, 'Community badge.');
                continue;
            }

            if (is_array($badge)) {
                $label = trim((string) ($badge['label'] ?? $badge['name'] ?? ''));
                if ($label === '') {
                    continue;
                }

                $key = trim((string) ($badge['key'] ?? ''));
                if ($key === '') {
                    $key = 'custom_' . str($label)->slug('_')->toString() . '_' . $index;
                }

                $description = trim((string) ($badge['description'] ?? 'Community badge.'));
                $pushBadge($key, $label, $description === '' ? 'Community badge.' : $description);
            }
        }

        return array_values($result);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function favourites(): BelongsToMany
    {
        return $this->belongsToMany(Nft::class, 'favourites');

    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function sellerFeedbackReceived(): HasMany
    {
        return $this->hasMany(SellerProfileFeedback::class, 'seller_user_id');
    }

    public function sellerFeedbackAuthored(): HasMany
    {
        return $this->hasMany(SellerProfileFeedback::class, 'author_user_id');
    }

    public function chainAccount(): HasOne
    {
        return $this->hasOne(ChainAccount::class);
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function conversations(): HasMany
{
    return $this->hasMany(Conversation::class, 'sender_id')
        ->where(function($query) {
            $query->where('sender_id', $this->id)
                  ->orWhere('receiver_id', $this->id);
        });
}

    /**
     * The channels the user receives notification broadcasts on.
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'users.' . $this->id;
    }

           public function getOtherUsers()
{
    return User::where('id', '!=', auth()->id())
        ->where(function ($query) {
            $query->where('search_public', true)
                ->orWhereNull('search_public');
        })
        ->select('id', 'name', 'email') 
        ->get();
}

    public function friendshipStateWith(int $otherUserId): string
    {
        return Friendship::stateForViewer((int) $this->id, (int) $otherUserId);
    }

    public function nftsVisibility(): string
    {
        $value = strtolower(trim((string) ($this->nfts_visibility ?? '')));

        if (in_array($value, [
            self::NFTS_VISIBILITY_PUBLIC,
            self::NFTS_VISIBILITY_FRIENDS,
            self::NFTS_VISIBILITY_PRIVATE,
        ], true)) {
            return $value;
        }

        return (bool) ($this->nfts_public ?? false)
            ? self::NFTS_VISIBILITY_PUBLIC
            : self::NFTS_VISIBILITY_PRIVATE;
    }

    public function profileCommentsVisibility(): string
    {
        $value = strtolower(trim((string) ($this->profile_comments_visibility ?? '')));

        if (in_array($value, [
            self::PROFILE_COMMENTS_VISIBILITY_PUBLIC,
            self::PROFILE_COMMENTS_VISIBILITY_FRIENDS,
            self::PROFILE_COMMENTS_VISIBILITY_DISABLED,
        ], true)) {
            return $value;
        }

        return (bool) ($this->profile_comments_public ?? true)
            ? self::PROFILE_COMMENTS_VISIBILITY_PUBLIC
            : self::PROFILE_COMMENTS_VISIBILITY_DISABLED;
    }

    public function canViewerSeeOwnedNfts(?User $viewer): bool
    {
        if ($viewer && (int) $viewer->id === (int) $this->id) {
            return true;
        }

        return match ($this->nftsVisibility()) {
            self::NFTS_VISIBILITY_PUBLIC => true,
            self::NFTS_VISIBILITY_FRIENDS => $viewer ? Friendship::areFriends((int) $viewer->id, (int) $this->id) : false,
            default => false,
        };
    }

    public function canViewerPostProfileComment(?User $viewer): bool
    {
        if (! $viewer) {
            return false;
        }

        if ((int) $viewer->id === (int) $this->id) {
            return $this->profileCommentsVisibility() !== self::PROFILE_COMMENTS_VISIBILITY_DISABLED;
        }

        return match ($this->profileCommentsVisibility()) {
            self::PROFILE_COMMENTS_VISIBILITY_PUBLIC => true,
            self::PROFILE_COMMENTS_VISIBILITY_FRIENDS => Friendship::areFriends((int) $viewer->id, (int) $this->id),
            default => false,
        };
    }
}
