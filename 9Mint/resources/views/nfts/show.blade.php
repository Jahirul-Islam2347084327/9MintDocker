@extends('layouts.app')

@section('title', $nft->name)

@push('styles')
    @vite('resources/css/pages/app-pages.css')
    @vite('resources/css/pages/collections-legacy.css')
@endpush

@push('scripts')
    @vite('resources/js/nft-marketplace/marketplace-entry.jsx')
    @vite('resources/js/page-scripts/quote-refresh.js')
@endpush

@section('content')
    <section class="nft-detail">
        <div class="nft-detail__media">
            <img src="{{ asset(ltrim($nft->thumbnail_url ?? $nft->image_url, '/')) }}" alt="{{ $nft->name }}">
        </div>
        <div class="nft-detail__info nft-detail__info-panel">
            @php
                $averageRatingValue = (float) ($averageRating ?? 0);
                $reviewCountValue = (int) ($reviewCount ?? 0);
                $ratingFillPercent = max(0, min(100, ($averageRatingValue / 5) * 100));
            @endphp
            <div class="nft-detail__header">
                <div class="nft-detail__header-main">
                    <h1>{{ $nft->name }}</h1>
                    @if ($collection)
                        <a href="{{ route('collections.show', ['slug' => $collection->slug]) }}" class="nft-detail__collection-link">{{ $collection->name }}</a>
                    @endif
                </div>
                <div class="nft-detail__rating" aria-label="{{ $reviewCountValue > 0 ? number_format($averageRatingValue, 1) . ' out of 5 from ' . $reviewCountValue . ' reviews' : 'No reviews yet' }}">
                    <div class="nft-detail__rating-stars" style="--rating-fill: {{ $ratingFillPercent }}%;">
                        <span class="nft-detail__rating-stars-base">★★★★★</span>
                        <span class="nft-detail__rating-stars-fill">★★★★★</span>
                    </div>
                    <span class="nft-detail__rating-text">
                        @if ($reviewCountValue > 0)
                            {{ number_format($averageRatingValue, 1) }} <span>({{ $reviewCountValue }})</span>
                        @else
                            No reviews
                        @endif
                    </span>
                </div>
            </div>

            <p class="nft-detail__description">{{ $nft->description }}</p>

            @if ($listing)
                @php
                    $sellerName = $listing->seller?->email === 'platform@9mint.local'
                        ? '9Mint'
                        : ($listing->seller?->name ?? 'Unknown');
                @endphp
                <div class="nft-detail__meta-row">
                    <div class="nft-detail__meta-item">
                        <span class="nft-detail__meta-label">Seller</span>
                        <span class="nft-detail__meta-value">
                            @if ($listing->seller?->name)
                                <a href="{{ route('profile.show', ['username' => $listing->seller->name]) }}">{{ $sellerName }}</a>
                            @else
                                {{ $sellerName }}
                            @endif
                        </span>
                    </div>
                    <div class="nft-detail__meta-item">
                        <span class="nft-detail__meta-label">Listing</span>
                        <span class="nft-detail__meta-value">#{{ $listing->id }}</span>
                    </div>
                    <div class="nft-detail__meta-item">
                        <span class="nft-detail__meta-label">Editions</span>
                        <span class="nft-detail__meta-value">{{ (int) ($listedEditionsCount ?? 0) }} / {{ $nft->editions_total }}</span>
                    </div>
                    <div class="nft-detail__meta-item nft-detail__meta-contact">
                        @auth
                            @if ($listing->seller_user_id !== auth()->id())
                                <form method="POST" action="{{ route('conversations.start', $listing->id) }}" class="nft-detail__inline-contact-form">
                                    @csrf
                                    <button type="submit" class="nft-detail__contact-btn">Contact seller</button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}" class="nft-detail__contact-btn">Contact seller</a>
                        @endauth
                    </div>
                </div>
            @endif

            @if ($listing)
                @php $refSymbol = $currencySymbols[$listing->ref_currency ?? 'GBP'] ?? null; @endphp

                <div class="nft-detail__price-card">
                    <div class="nft-detail__price-main">
                        <span class="nft-detail__price-label">Price</span>
                        <span class="nft-detail__price-value">
                            {{ $refSymbol
                                ? $refSymbol . number_format($listing->ref_amount, 2)
                                : number_format($listing->ref_amount, 2) . ' ' . $listing->ref_currency }}
                        </span>
                    </div>

                    <div class="nft-detail__cta">
                        @auth
                            @if ($listing->seller_user_id === auth()->id())
                                <button type="button" class="nft-detail__buy-btn nft-detail__buy-btn--disabled" disabled>You own this NFT</button>
                            @else
                                <form method="POST" action="{{ route('cart.store') }}" class="nft-detail__buy-form">
                                    @csrf
                                    <input type="hidden" name="listing_id" value="{{ $listing->id }}">
                                    <button type="submit" class="nft-detail__buy-btn">Buy now</button>
                                </form>
                            @endif
                        @else
                            <a class="nft-detail__buy-btn" href="{{ route('login', ['redirect' => request()->fullUrl()]) }}">
                                Login to buy
                            </a>
                        @endauth
                    </div>
                </div>

                @php
                    $convertedQuotes = collect($quotes ?? [])->reject(function ($quote, $currency) use ($listing) {
                        return strtoupper((string) $currency) === strtoupper((string) ($listing->ref_currency ?? ''));
                    });
                @endphp

                @if ($convertedQuotes->isNotEmpty())
                    <div class="nft-detail__currencies">
                        <h3 class="nft-detail__currencies-title">Live exchange rates</h3>
                        <div class="nft-detail__currency-grid">
                            @foreach ($convertedQuotes as $currency => $quote)
                                @php $quoteSymbol = $currencySymbols[$quote['pay_currency'] ?? $currency] ?? null; @endphp
                                <div class="nft-detail__currency-chip" data-quote-listing="{{ $listing->id }}" data-currency="{{ $currency }}">
                                    <span class="nft-detail__currency-code">{{ $currency }}</span>
                                    <span class="nft-detail__currency-amount">
                                        {{ $quoteSymbol
                                            ? $quoteSymbol . number_format($quote['pay_amount'], ($currency === 'ETH' || $currency === 'BTC') ? 8 : 2)
                                            : number_format($quote['pay_amount'], ($currency === 'ETH' || $currency === 'BTC') ? 8 : 2) . ' ' . $quote['pay_currency'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            @else
                <p class="nft-out-of-stock-message">No active listings for this NFT.</p>
            @endif
        </div>
    </section>

    @auth
        @if (!empty($ownedTokens) && $ownedTokens->isNotEmpty())
            <section class="nft-detail__owner-panel">
                <h2>Your Tokens</h2>
                <p class="nft-detail__owner-hint">List your owned tokens for resale. Pending NFTs are unable to be traded or listed for sale.</p>
                <div class="token-strip-list">
                    @foreach ($ownedTokens as $token)
                        @php
                            $tokenListing = $token->listing;
                            $isEligible = in_array($token->id, $eligibleTokenIds ?? [], true);
                        @endphp
                        <div class="token-strip">
                            <div class="token-strip__meta">
                                <h3>{{ $nft->name }}</h3>
                                <p class="token-strip__sub">Token #{{ $token->serial_number }}</p>

                                @if ($tokenListing && in_array($tokenListing->status, ['active', 'reserved'], true))
                                    <p class="token-strip__price">
                                        Listed for {{ $tokenListing->ref_currency }} {{ number_format($tokenListing->ref_amount, 2) }}
                                    </p>
                                @elseif (! $isEligible)
                                    <p class="token-strip__price">Pending NFTs are unable to be traded or listed for sale.</p>
                                @endif
                            </div>

                            <div class="token-strip__actions">
                                @if ($tokenListing && in_array($tokenListing->status, ['active', 'reserved'], true))
                                    <form method="POST" action="{{ route('inventory.listing.destroy', $tokenListing->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">Unlist</button>
                                    </form>
                                @elseif ($isEligible)
                                    <form method="POST" action="{{ route('inventory.listing.store') }}" class="token-strip__form">
                                        @csrf
                                        <input type="hidden" name="token_id" value="{{ $token->id }}">
                                        <input type="number" step="0.01" min="0" name="ref_amount" placeholder="Price" required>
                                        <select name="ref_currency">
                                            @foreach ($currencies as $currency)
                                                <option value="{{ $currency }}">{{ $currency }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit">List</button>
                                    </form>
                                    <p class="token-strip__hint">9Mint fee: 2.5% (you receive 97.5%).</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    @endauth

    <div
        id="nft-market-root"
        data-nft-slug="{{ $nft->slug }}"
        data-default-currency="{{ $currencies[0] ?? 'GBP' }}"
        data-currencies='@json($currencies)'
        data-csrf="{{ csrf_token() }}"
        data-auth="{{ auth()->check() ? '1' : '0' }}"
        data-viewer-id="{{ auth()->id() }}"
    ></div>

    <hr class="review-divider">

    <section class="nft-reviews-section">
        <h3>Reviews</h3>

        @if($reviewCount > 0)
            <div class="review-summary">
                <strong>{{ number_format($averageRating, 1) }}</strong>
                <span>out of 5 &mdash; {{ $reviewCount }} {{ Str::plural('review', $reviewCount) }}</span>
            </div>

            @foreach($nft->reviews as $review)
                <div class="review-card">
                    <div class="review-stars">
                        {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}
                    </div>
                    <div class="review-author">{{ $review->user->name }}</div>
                    <div class="review-text">{{ $review->review_text }}</div>
                    <div class="review-date">{{ $review->created_at->format('d M Y') }}</div>
                    @if(auth()->check() && (auth()->id() === $review->user_id || auth()->user()->canAccessAdminFeatures()))
                        <div class="review-card-actions">
                            @if(auth()->id() === $review->user_id)
                                <button type="button" onclick="toggleEditForm()" class="edit-review-btn">Edit review</button>
                            @endif
                            @if(auth()->user()->canAccessAdminFeatures())
                                <form method="POST" action="{{ route('nfts.review.destroy', ['nft' => $nft, 'review' => $review]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="edit-review-btn">Delete review</button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        @else
            <p class="no-reviews">No reviews yet. Be the first to share your thoughts.</p>
        @endif

        @if(auth()->check())
            <div id="review-edit-form" class="review-form-wrapper" style="display: {{ $userReview ? 'none' : 'block' }};">
                <h4>{{ $userReview ? 'Edit Your Review' : 'Write a Review' }}</h4>
                <form method="POST"
                      action="{{ $userReview ? route('nfts.review.update', $nft) : route('nfts.review.store', $nft) }}">
                    @csrf
                    @if($userReview)
                        @method('PUT')
                    @endif
                    <div class="star-rating">
                        @for($i = 5; $i >= 1; $i--)
                            <input type="radio" id="star{{ $i }}" name="rating" value="{{ $i }}"
                                   {{ $userReview && $userReview->rating == $i ? 'checked' : '' }}>
                            <label for="star{{ $i }}">★</label>
                        @endfor
                    </div>
                    <textarea name="review_text" placeholder="What did you think of this NFT?" required>{{ $userReview->review_text ?? '' }}</textarea>
                    <button type="submit" class="review-submit-btn">
                        {{ $userReview ? 'Update Review' : 'Submit Review' }}
                    </button>
                </form>
            </div>
        @else
            <div class="login-to-review">
                <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}">Login to write a review</a>
            </div>
        @endif
    </section>

</section>
<script>
function toggleEditForm() {
    const form = document.getElementById('review-edit-form');

    if (form.style.display === "none") {
        form.style.display = "block";
        form.scrollIntoView({ behavior: 'smooth' });
    } else {
        form.style.display = "none";
    }
}
</script>

@endsection