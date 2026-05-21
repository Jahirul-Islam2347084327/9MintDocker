@extends('layouts.app')

@section('title', 'Trending')

@push('styles')
    @vite('resources/css/pages/trending.css')
    @vite('resources/css/pages/collections-legacy.css')
@endpush

@push('scripts')
    @vite('resources/js/page-scripts/quote-refresh.js')
@endpush

@php
    $currencySymbols = [
        'GBP' => '£',
        'USD' => '$',
        'EUR' => '€',
        'BTC' => '₿',
        'ETH' => 'Ξ',
    ];
    $favouriteIds = $favouriteIds ?? collect();
@endphp

@section('content')
    <section id="trending-section">
        <h2>Trending NFTs</h2>
        <p class="trending-subtitle">Most sold in the last 7 days</p>

        @if ($trendingNfts->isEmpty())
            <p class="trending-empty">No NFTs have been sold in the past 7 days. Check back soon!</p>
        @else
            <div class="nft-collection-grid">
                @foreach ($trendingNfts as $index => $nft)
                    @php
                        $listing = $nft->active_listing ?? null;
                        $price = $listing?->ref_amount;
                        $currency = $listing?->ref_currency ?? ($nft->primary_ref_currency ?? 'GBP');
                        $currencySymbol = $currencySymbols[$currency] ?? null;
                        $isLiked = $favouriteIds->contains($nft->id);
                        $thumbUrl = asset(ltrim($nft->thumbnail_url ?? $nft->image_url, '/'));
                    @endphp
                    <div class="nft-collection-card">
                        <div class="trending-rank-badge" aria-hidden="true">🔥</div>
                        <button
                            type="button"
                            class="nft-collection-heart"
                            onclick="toggleLike({{ $nft->id }}, this)"
                            aria-label="Toggle favourite"
                            data-liked="{{ $isLiked ? '1' : '0' }}"
                        >
                            {{ $isLiked ? '♥' : '♡' }}
                        </button>
                        <a href="{{ route('nfts.show', ['slug' => $nft->slug]) }}">
                            <div class="nft-collection-thumb" style="--thumb-bg-image: url('{{ $thumbUrl }}');">
                                <img src="{{ $thumbUrl }}" alt="{{ $nft->name }}" />
                            </div>
                            <div class="nft-collection-meta">
                                <h3>{{ $nft->name }}</h3>
                                <p class="nft-collection-price" data-quote-listing="{{ $listing?->id }}" data-currency="{{ $currency }}">
                                    {{ $price !== null
                                        ? ($currencySymbol ? $currencySymbol . number_format($price, 2) : number_format($price, 2) . ' ' . $currency)
                                        : 'Unavailable' }}
                                </p>
                                <div class="trending-meta-row">
                                    <span class="trending-sales-badge">{{ $nft->sales_count }} {{ \Illuminate\Support\Str::plural('sale', $nft->sales_count) }}</span>
                                    @if ($nft->collection)
                                        <span class="trending-collection-name">{{ $nft->collection->name }}</span>
                                    @endif
                                </div>
                                <p class="nft-collection-stock">
                                    Editions listed: {{ (int) ($nft->listed_editions_count ?? 0) }} / {{ $nft->editions_total }}
                                </p>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection

@push('scripts')
<script>
    async function toggleLike(nftId, btn) {
        @guest
            window.location.href = "{{ route('login') }}";
            return;
        @endguest

        const isLiked = btn.innerText.trim() === '♥';
        btn.innerText = isLiked ? '♡' : '♥';
        btn.dataset.liked = isLiked ? '0' : '1';
        btn.style.color = isLiked ? 'white' : '#ff4d4d';

        try {
            const response = await fetch(`/nfts/${nftId}/toggle-like`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({})
            });
            if (!response.ok) throw new Error('Failed');
        } catch {
            btn.innerText = isLiked ? '♥' : '♡';
            btn.dataset.liked = isLiked ? '1' : '0';
            btn.style.color = isLiked ? '#ff4d4d' : 'white';
        }
    }
</script>
@endpush
