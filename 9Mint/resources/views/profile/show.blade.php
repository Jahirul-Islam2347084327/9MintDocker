@extends('layouts.app')

@section('title', $user->name . "'s Profile")

@push('styles')
<style>
    .profile-show {
        max-width: 800px;
        margin: 60px auto;
        padding: 0 20px 80px;
        text-align: center;
    }

    .profile-show-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: var(--link-hover);
        color: #fff;
        font-size: 40px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }

    .profile-show-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        display: block;
    }

    .profile-show-name {
        font-size: 26px;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .profile-show-account-settings {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
        padding: 10px 18px;
        border-radius: 8px;
        background: var(--link-hover);
        color: #fff;
        text-decoration: none;
        font-weight: 600;
    }

    .profile-show-account-settings:hover {
        background: color-mix(in srgb, var(--link-hover) 85%, #000 15%);
    }

    .profile-show-contact-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
        padding: 10px 18px;
        border-radius: 8px;
        background: var(--link-hover);
        color: #fff;
        text-decoration: none;
        font-weight: 600;
        border: none;
        cursor: pointer;
    }

    .profile-show-contact-btn:hover {
        background: color-mix(in srgb, var(--link-hover) 85%, #000 15%);
    }

    .profile-show-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
        align-items: center;
        margin-bottom: 24px;
    }

    .profile-show-actions-row {
        display: flex;
        gap: 10px;
        width: 100%;
        max-width: 420px;
        justify-content: center;
    }

    .profile-show-actions-row form {
        flex: 1 1 0;
    }

    .profile-show-actions-row .profile-show-contact-btn {
        width: 100%;
    }

    .profile-show-contact-btn--secondary {
        background: transparent;
        color: var(--text-muted);
        border: 1px solid var(--border-soft);
    }

    .profile-show-contact-btn--secondary:hover {
        background: var(--surface-muted);
        color: var(--text-main);
    }

    .profile-show-contact-btn--wide {
        width: 100%;
        max-width: 420px;
    }

    .profile-show-details {
        background: var(--surface-panel);
        color: var(--text-main);
        border: 1px solid var(--border-soft);
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        padding: 24px 28px;
        text-align: left;
    }

    .profile-show-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-soft);
    }

    .profile-show-row:last-child {
        border-bottom: none;
    }

    .profile-show-label {
        font-weight: 600;
        color: var(--text-main);
    }

    .profile-show-value {
        color: var(--link-hover);
    }

    .profile-show-badges {
        margin-top: 20px;
        background: var(--surface-panel);
        color: var(--text-main);
        border: 1px solid var(--border-soft);
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        padding: 16px 20px;
        text-align: left;
    }

    .profile-show-badges h2 {
        margin: 0;
        font-size: 18px;
        color: var(--text-main);
    }

    .profile-show-nfts {
        margin-top: 30px;
        text-align: left;
    }

    .profile-show-nfts h2 {
        color: var(--text-primary);
        font-size: 20px;
        margin-bottom: 16px;
    }

    .profile-show-nfts-title-link {
        color: inherit;
        text-decoration: none;
    }

    .profile-show-nfts-title-link:hover {
        color: var(--link-hover);
    }

    .profile-show-nft-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 16px;
        position: relative;
    }

    .profile-show-nft-card {
        background: var(--surface-panel);
        border: 1px solid var(--border-soft);
        border-radius: 10px;
        overflow: hidden;
        text-decoration: none;
        color: var(--text-main);
        transition: transform 0.2s ease;
    }

    .profile-show-nft-card:hover {
        transform: translateY(-4px);
    }

    .profile-show-nft-card img {
        width: 100%;
        aspect-ratio: 1 / 1.4;
        object-fit: contain;
        background: color-mix(in srgb, var(--surface-input) 70%, #000 30%);
        display: block;
    }

    .profile-show-nft-card span {
        display: block;
        padding: 10px 12px;
        font-size: 14px;
        font-weight: 600;
    }

    .profile-show-nft-token-id {
        padding: 0 12px 12px;
        margin-top: 0;
        font-size: 12px;
        font-weight: 500;
        color: var(--subtext-color);
    }

    .profile-show-nft-card--faded {
        pointer-events: none;
        user-select: none;
        -webkit-mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 1) 8%, rgba(0, 0, 0, 0) 42%);
        mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 1) 8%, rgba(0, 0, 0, 0) 42%);
    }

    .profile-show-nft-card--faded:hover {
        transform: none;
    }

    .profile-show-inventory-cta {
        margin-top: -128px;
        text-align: center;
        position: relative;
        z-index: 2;
    }

    .profile-show-inventory-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 18px;
        border-radius: 8px;
        background: var(--link-hover);
        color: #fff;
        text-decoration: none;
        font-weight: 600;
    }

    .profile-show-inventory-btn:hover {
        background: color-mix(in srgb, var(--link-hover) 85%, #000 15%);
    }

    .profile-show-empty {
        color: #888;
        font-size: 14px;
    }

    .profile-show-rating {
        margin: 0 auto 18px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        width: fit-content;
    }

    .profile-show-rating-stars {
        position: relative;
        display: inline-block;
        font-size: 1.3rem;
        line-height: 1;
        letter-spacing: 0.12em;
    }

    .profile-show-rating-stars-base {
        color: color-mix(in srgb, var(--text-muted) 70%, transparent);
    }

    .profile-show-rating-stars-fill {
        position: absolute;
        inset: 0 auto 0 0;
        width: var(--rating-fill, 0%);
        overflow: hidden;
        white-space: nowrap;
        color: #f5a623;
    }

    .profile-show-rating-summary {
        font-size: 14px;
        font-weight: 600;
        color: var(--subtext-color);
    }

    .profile-show-rating-summary strong {
        color: var(--text-main);
    }

    .profile-show-feedback {
        margin-top: 32px;
        text-align: left;
    }

    .profile-show-feedback-header {
        margin-bottom: 14px;
    }

    .profile-show-feedback-header h2 {
        margin: 0 0 6px;
        font-size: 20px;
        color: var(--text-main);
    }

    .profile-show-feedback-header p {
        margin: 0;
        color: var(--subtext-color);
        text-align: left;
    }

    .profile-show-feedback-note {
        margin: 0 0 14px;
        color: var(--subtext-color);
        font-size: 14px;
    }

    .profile-show-feedback-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-main);
    }

    .profile-show-feedback-toggle input {
        width: 16px;
        height: 16px;
        accent-color: var(--link-hover);
    }

    .profile-show-feedback-list {
        display: grid;
        gap: 12px;
        margin-top: 16px;
    }

    .profile-show-feedback-item {
        background: var(--surface-panel);
        border: 1px solid var(--border-soft);
        border-radius: 12px;
        padding: 16px 18px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.12);
    }

    .profile-show-feedback-meta {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 10px;
    }

    .profile-show-feedback-author {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .profile-show-feedback-author strong {
        color: var(--text-main);
        font-size: 15px;
    }

    .profile-show-feedback-date {
        font-size: 12px;
        color: var(--text-muted);
    }

    .profile-show-feedback-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    .profile-show-feedback-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 10px;
        border-radius: 999px;
        border: 1px solid var(--border-soft);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--subtext-color);
    }

    .profile-show-feedback-stars {
        color: #f5a623;
        letter-spacing: 0.08em;
        font-size: 14px;
    }

    .profile-show-feedback-body {
        margin: 0;
        color: var(--text-main);
        line-height: 1.55;
        text-align: left;
        white-space: pre-wrap;
    }

    .profile-show-feedback-delete {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 12px;
        border-radius: 8px;
        border: 1px solid var(--border-soft);
        background: transparent;
        color: var(--subtext-color);
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }

    .profile-show-feedback-delete:hover {
        color: var(--text-main);
        border-color: var(--link-hover);
    }

    .profile-show-feedback-form {
        margin-top: 18px;
        background: var(--surface-panel);
        border: 1px solid var(--border-soft);
        border-radius: 12px;
        padding: 18px 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.12);
    }

    .profile-show-feedback-form h3 {
        margin: 0 0 14px;
        font-size: 18px;
        color: var(--text-main);
    }

    .profile-show-feedback-grid {
        display: grid;
        gap: 14px;
    }

    .profile-show-feedback-field label {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-main);
    }

    .profile-show-feedback-select,
    .profile-show-feedback-textarea {
        width: 100%;
        border: 1px solid var(--border-soft);
        border-radius: 8px;
        background: var(--surface-input);
        color: var(--text-main);
        padding: 10px 12px;
        font: inherit;
    }

    .profile-show-feedback-textarea {
        min-height: 110px;
        resize: vertical;
    }

    .profile-show-feedback-select:focus,
    .profile-show-feedback-textarea:focus {
        outline: none;
        border-color: var(--link-hover);
    }

    .profile-show-feedback-actions {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }

    .profile-show-feedback-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 18px;
        border-radius: 8px;
        border: none;
        background: var(--link-hover);
        color: #fff;
        font-weight: 600;
        cursor: pointer;
    }

    .profile-show-feedback-submit:hover {
        background: color-mix(in srgb, var(--link-hover) 85%, #000 15%);
    }

    .profile-show-feedback-login {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 18px;
        padding: 10px 18px;
        border-radius: 8px;
        border: 1px solid var(--border-soft);
        color: var(--subtext-color);
        text-decoration: none;
        font-weight: 600;
    }

    .profile-show-feedback-login:hover {
        color: var(--text-main);
        border-color: var(--link-hover);
    }

    .profile-show-feedback-empty {
        color: var(--subtext-color);
        font-size: 14px;
        margin-top: 14px;
    }

    .profile-show-star-rating {
        direction: rtl;
        display: inline-flex;
        gap: 2px;
    }

    .profile-show-star-rating input {
        display: none;
    }

    .profile-show-star-rating label {
        color: color-mix(in srgb, var(--text-muted) 70%, transparent);
        cursor: pointer;
        font-size: 24px;
        line-height: 1;
        transition: color 0.2s ease;
    }

    .profile-show-star-rating input:checked ~ label,
    .profile-show-star-rating label:hover,
    .profile-show-star-rating label:hover ~ label {
        color: #f5a623;
    }

    .profile-show-feedback-rating-group[hidden] {
        display: none !important;
    }

    @media (max-width: 700px) {
        .profile-show-feedback-meta,
        .profile-show-row,
        .profile-show-feedback-actions {
            flex-direction: column;
            align-items: flex-start;
        }

        .profile-show-actions-row {
            flex-direction: column;
        }
    }
</style>
@endpush

@section('content')
<div class="profile-show">
    @php
        $sellerRatingAverageValue = (float) ($sellerRatingAverage ?? 0);
        $sellerRatingCountValue = (int) ($sellerRatingCount ?? 0);
        $sellerRatingFillPercent = max(0, min(100, ($sellerRatingAverageValue / 5) * 100));
        $profileCommentsVisibility = (string) ($profileCommentsVisibility ?? 'public');
        $visibleSellerFeedback = $visibleSellerFeedback ?? collect();
        $canSubmitSellerFeedback = (bool) ($canSubmitSellerFeedback ?? false);
        $canSubmitProfileComment = (bool) ($canSubmitProfileComment ?? false);
        $canViewerSeeOwnedNfts = (bool) ($canViewerSeeOwnedNfts ?? false);
        $isOwnerView = (bool) (($isOwner ?? false) === true);
        $reviewChecked = (bool) old('is_review', false);
    @endphp
    <div class="profile-show-avatar">
        @if (!empty($user->profile_image_url))
            <img src="{{ asset(ltrim($user->profile_image_url, '/')) }}" alt="{{ $user->name }} avatar">
        @else
            {{ strtoupper(substr($user->name, 0, 1)) }}
        @endif
    </div>

    <h1 class="profile-show-name">{{ $user->name }}</h1>
    <div class="profile-show-rating" aria-label="{{ $sellerRatingCountValue > 0 ? number_format($sellerRatingAverageValue, 1) . ' out of 5 from ' . $sellerRatingCountValue . ' ratings' : 'No seller ratings yet' }}">
        <div class="profile-show-rating-stars" style="--rating-fill: {{ $sellerRatingFillPercent }}%;">
            <span class="profile-show-rating-stars-base">★★★★★</span>
            <span class="profile-show-rating-stars-fill">★★★★★</span>
        </div>
        <div class="profile-show-rating-summary">
            @if ($sellerRatingCountValue > 0)
                <strong>{{ number_format($sellerRatingAverageValue, 1) }}</strong>
                <span>({{ $sellerRatingCountValue }} {{ \Illuminate\Support\Str::plural('rating', $sellerRatingCountValue) }})</span>
            @else
                <span>No seller ratings yet</span>
            @endif
        </div>
    </div>
    @if (($isOwner ?? false) === true)
        <a href="{{ route('profile.settings') }}" class="profile-show-account-settings">Account Settings</a>
    @elseif(auth()->check())
        @php
            $friendshipState = $friendshipState ?? 'none';
            $existingConversationId = $existingConversationId ?? null;
        @endphp
        <div class="profile-show-actions">
            @if ($friendshipState === 'friends')
                <div class="profile-show-actions-row">
                    <form method="POST" action="{{ route('friends.unfriend', $user->id) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="profile-show-contact-btn profile-show-contact-btn--secondary">Unfriend</button>
                    </form>
                    @if ($existingConversationId)
                        <a href="{{ route('chat.user', ['user' => auth()->id(), 'conversation' => $existingConversationId]) }}" class="profile-show-contact-btn">
                            Send Message
                        </a>
                    @else
                        <form method="POST" action="{{ route('conversations.start-user', $user->id) }}">
                            @csrf
                            <button type="submit" class="profile-show-contact-btn">Send Message</button>
                        </form>
                    @endif
                </div>
            @elseif ($friendshipState === 'outgoing_pending')
                <form method="POST" action="{{ route('friends.request.cancel', $user->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="profile-show-contact-btn profile-show-contact-btn--wide">Undo Friend Request</button>
                </form>
            @elseif ($friendshipState === 'incoming_pending')
                <form method="POST" action="{{ route('friends.accept', $user->id) }}">
                    @csrf
                    <button type="submit" class="profile-show-contact-btn profile-show-contact-btn--wide">Accept Friend Request</button>
                </form>
            @else
                <form method="POST" action="{{ route('friends.request', $user->id) }}">
                    @csrf
                    <button type="submit" class="profile-show-contact-btn profile-show-contact-btn--wide">Add Friend</button>
                </form>
            @endif
        </div>
    @else
        <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}" class="profile-show-contact-btn">Add Friend</a>
    @endif

    <div class="profile-show-details">
        <div class="profile-show-row">
            <span class="profile-show-label">Username</span>
            <span class="profile-show-value">{{ $user->name }}</span>
        </div>
        <div class="profile-show-row">
            <span class="profile-show-label">NFTs Owned</span>
            <span class="profile-show-value">
                @if ($canViewerSeeOwnedNfts)
                    {{ app(\App\Services\OwnershipService::class)->countOwnedTokensForUser($user->id) }}
                @else
                    {{ $user->nftsVisibility() === 'friends' ? 'Friends only' : 'Private' }}
                @endif
            </span>
        </div>
        <div class="profile-show-row">
            <span class="profile-show-label">Member Since</span>
            <span class="profile-show-value">{{ $user->created_at->format('F j, Y') }}</span>
        </div>
    </div>

    <div class="profile-show-badges">
        <h2>Badges</h2>
        <x-profile-badges :user="$user" />
    </div>

    @if ($canViewerSeeOwnedNfts)
        @php
            $isOwnerView = (($isOwner ?? false) === true);
            $ownedTokens = app(\App\Services\OwnershipService::class)
                ->ownedTokensQueryForUser($user->id)
                ->with(['nft', 'listing'])
                ->whereDoesntHave('listing', function ($q) {
                    $q->whereIn('status', ['active', 'reserved']);
                })
                ->get();
            $previewItems = $ownedTokens->map(function ($token) {
                $nft = $token->nft;
                return [
                    'href' => route('nfts.show', $nft->slug),
                    'image_url' => $nft->thumbnail_url ?? $nft->image_url,
                    'name' => $nft->name,
                    'edition_label' => 'Edition #' . $token->serial_number,
                ];
            })->values();
        @endphp

        <div class="profile-show-nfts">
            <h2>
                @if (($isOwner ?? false))
                    <a href="{{ route('inventory.show', ['username' => $user->name]) }}" class="profile-show-nfts-title-link">Inventory</a>
                @else
                    {{ $user->name . "'s Inventory" }}
                @endif
            </h2>
            @if (!($isOwner ?? false))
                <p>
                    <a href="{{ route('inventory.show', ['username' => $user->name]) }}" class="profile-show-inventory-btn">View inventory</a>
                </p>
            @endif
            <x-profile-nft-preview-grid
                :items="$previewItems"
                empty-text="No NFTs owned yet."
                :cta-label="$isOwnerView ? 'View inventory' : null"
                :cta-href="$isOwnerView ? route('inventory.show', ['username' => $user->name]) : null"
            />
        </div>
    @else
        <div class="profile-show-nfts">
            <h2>{{ $user->name . "'s Inventory" }}</h2>
            <p class="profile-show-empty">
                @if ($user->nftsVisibility() === 'friends')
                    This user's NFT collection is only visible to friends.
                @else
                    This user's NFT collection is private.
                @endif
            </p>
        </div>
    @endif

    <section class="profile-show-feedback">
        <div class="profile-show-feedback-header">
            <h2>Seller Comments</h2>
            <p>General comments and reviews from other users, plus seller ratings that build the score above.</p>
        </div>

        @if ($profileCommentsVisibility === 'friends' && ! $canSubmitProfileComment && ! $isOwnerView)
            <p class="profile-show-feedback-note">Only friends can post comments on this profile. Non-friends can still leave seller ratings.</p>
        @elseif ($profileCommentsVisibility === 'disabled')
            <p class="profile-show-feedback-note">
                @if ($isOwnerView)
                    Comments are disabled on your profile. Existing comments stay visible, but no new comments can be posted.
                @else
                    Comments are disabled on this profile. Existing comments stay visible, but no new comments can be posted. Seller ratings are still allowed.
                @endif
            </p>
        @endif

        @if ($canSubmitSellerFeedback)
            <form method="POST" action="{{ route('profile.feedback.store', ['username' => $user->name]) }}" class="profile-show-feedback-form">
                @csrf
                <h3>Leave seller feedback</h3>
                <div class="profile-show-feedback-grid">
                    @if (! $isOwnerView)
                        <div class="profile-show-feedback-field">
                            <label class="profile-show-feedback-toggle" for="is_review">
                                <input type="hidden" name="is_review" value="0">
                                <input type="checkbox" id="is_review" name="is_review" value="1" @checked($reviewChecked)>
                                <span>Post this as a seller review</span>
                            </label>
                        </div>

                        <div class="profile-show-feedback-field profile-show-feedback-rating-group" id="profileFeedbackRatingGroup" @if (! $reviewChecked) hidden @endif>
                            <label>Seller rating</label>
                            <div class="profile-show-star-rating">
                                @for ($i = 5; $i >= 1; $i--)
                                    <input type="radio" id="seller-rating-{{ $i }}" name="rating" value="{{ $i }}" @checked((int) old('rating', 0) === $i)>
                                    <label for="seller-rating-{{ $i }}">★</label>
                                @endfor
                            </div>
                            @error('rating') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if ($canSubmitProfileComment)
                        <div class="profile-show-feedback-field">
                            <label for="body">Comment</label>
                            <textarea id="body" name="body" class="profile-show-feedback-textarea" placeholder="Share your experience with this seller...">{{ old('body') }}</textarea>
                            @error('body') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="profile-show-feedback-actions">
                        <p class="profile-show-feedback-note">
                            @if ($isOwnerView)
                                You can comment on your own profile, but you cannot rate yourself.
                            @elseif ($canSubmitProfileComment)
                                By default this posts a general comment. Tick the box above to turn it into a rated seller review.
                            @elseif ($profileCommentsVisibility === 'friends')
                                Only friends can post comments here, but you can still leave a seller review rating.
                            @else
                                Comments are disabled on this profile, but you can still leave a seller review rating.
                            @endif
                        </p>
                        <button type="submit" class="profile-show-feedback-submit">Post feedback</button>
                    </div>
                </div>
            </form>
        @elseif (! auth()->check())
            <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}" class="profile-show-feedback-login">Login to leave seller feedback</a>
        @endif

        @if ($visibleSellerFeedback->isNotEmpty())
            <div class="profile-show-feedback-list">
                @foreach ($visibleSellerFeedback as $feedback)
                    <article class="profile-show-feedback-item">
                        <div class="profile-show-feedback-meta">
                            <div class="profile-show-feedback-author">
                                <strong>{{ $feedback->author?->name ?? 'Unknown user' }}</strong>
                                <span class="profile-show-feedback-date">{{ $feedback->created_at?->format('d M Y') }}</span>
                            </div>
                            <div class="profile-show-feedback-badges">
                                @if (! empty($feedback->comment_type))
                                    <span class="profile-show-feedback-badge">{{ ucfirst($feedback->comment_type) }}</span>
                                @endif
                                @if ($feedback->rating !== null)
                                    <span class="profile-show-feedback-stars">{{ str_repeat('★', (int) $feedback->rating) }}{{ str_repeat('☆', max(0, 5 - (int) $feedback->rating)) }}</span>
                                @endif
                                @if (($isOwner ?? false) === true || (auth()->check() && auth()->user()->canAccessAdminFeatures()))
                                    <form method="POST" action="{{ route('profile.feedback.destroy', ['username' => $user->name, 'feedback' => $feedback->id]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="profile-show-feedback-delete">
                                            {{ ($isOwner ?? false) === true ? 'Delete comment' : 'Admin delete' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        <p class="profile-show-feedback-body">{{ $feedback->body }}</p>
                    </article>
                @endforeach
            </div>
        @else
            <p class="profile-show-feedback-empty">No public seller comments yet.</p>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const reviewToggle = document.getElementById('is_review');
        const ratingGroup = document.getElementById('profileFeedbackRatingGroup');

        if (!reviewToggle || !ratingGroup) {
            return;
        }

        const ratingInputs = ratingGroup.querySelectorAll('input[name="rating"]');

        const syncReviewState = () => {
            const isReview = reviewToggle.checked;
            ratingGroup.hidden = !isReview;

            ratingInputs.forEach((input) => {
                input.disabled = !isReview;
                if (!isReview) {
                    input.checked = false;
                }
            });
        };

        syncReviewState();
        reviewToggle.addEventListener('change', syncReviewState);
    });
</script>
@endpush
