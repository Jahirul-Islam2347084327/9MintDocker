@extends('layouts.app')

@section('title', 'Homepage')

@section('content')
    {{-- NFT Discovery Board (React-powered) --}}
    <section id="nft-discovery-section" aria-label="NFT Discovery Board">
        <div
            id="nft-discovery-board"
            data-nfts='@json($boardNfts ?? [])'
            data-currencies='@json(config("pricing.enabled_currencies", ["GBP"]))'
            data-csrf="{{ csrf_token() }}"
            data-auth="{{ auth()->check() ? '1' : '0' }}"
            data-login-url="{{ route('login', ['redirect' => request()->fullUrl()]) }}"
        ></div>
    </section>

    {{-- ═══ Browse by Currency Shelf ═══ --}}
    @if (!empty($currencyMeta))
        <section class="home-shelf" aria-label="Browse by Currency">
            <div class="home-shelf__inner">
                <h2 class="home-shelf__title">Browse by Reference Price</h2>
                <p class="home-shelf__subtitle">Find NFTs priced in your preferred currency</p>

                <div class="home-shelf__carousel-wrap">
                    <button type="button" class="home-shelf__arrow home-shelf__arrow--left" data-carousel-arrow="left" aria-label="Scroll left">&#8249;</button>
                    <div class="home-shelf__carousel" data-carousel-track>
                        @foreach ($currencyMeta as $cm)
                            <a href="{{ $cm['url'] }}" class="currency-card" style="--card-accent: {{ $cm['color'] }};">
                                <span class="currency-card__symbol">{{ $cm['symbol'] }}</span>
                                <span class="currency-card__code">{{ $cm['code'] }}</span>
                                <span class="currency-card__label">{{ $cm['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                    <button type="button" class="home-shelf__arrow home-shelf__arrow--right" data-carousel-arrow="right" aria-label="Scroll right">&#8250;</button>
                </div>
            </div>
        </section>
    @endif

    {{-- ═══ Trending NFTs Shelf ═══ --}}
    @if (($homeTrendingItems ?? collect())->isNotEmpty())
        <section class="home-shelf" aria-label="Trending NFTs">
            <div class="home-shelf__inner">
                <div class="home-shelf__header-row">
                    <div>
                        <h2 class="home-shelf__title">Trending Right Now</h2>
                        <p class="home-shelf__subtitle">Most sold in the last 7 days</p>
                    </div>
                    <a href="{{ route('trending.index') }}" class="home-shelf__browse-link">Browse Trending NFTs &rarr;</a>
                </div>

                <div class="home-shelf__carousel-wrap">
                    <button type="button" class="home-shelf__arrow home-shelf__arrow--left" data-carousel-arrow="left" aria-label="Scroll left">&#8249;</button>
                    <div class="home-shelf__carousel home-shelf__carousel--nfts" data-carousel-track>
                        @foreach ($homeTrendingItems as $item)
                            <div class="nft-collection-card home-shelf__trending-card">
                                <div class="trending-rank-badge" aria-hidden="true">🔥</div>
                                <button
                                    type="button"
                                    class="nft-collection-heart"
                                    onclick="toggleLike({{ $item['id'] }}, this)"
                                    aria-label="Toggle favourite"
                                    data-liked="{{ $item['is_liked'] ? '1' : '0' }}"
                                >
                                    {{ $item['is_liked'] ? '♥' : '♡' }}
                                </button>
                                <a href="{{ $item['url'] }}">
                                    <div class="nft-collection-thumb" style="--thumb-bg-image: url('{{ $item['thumb'] }}');">
                                        <img src="{{ $item['thumb'] }}" alt="{{ $item['name'] }}" loading="lazy" />
                                    </div>
                                    <div class="nft-collection-meta">
                                        <h3>{{ $item['name'] }}</h3>
                                        <p class="nft-collection-price" data-quote-listing="{{ $item['listing_id'] }}" data-currency="{{ $item['currency'] }}">
                                            {{ $item['price'] ?? 'Unavailable' }}
                                        </p>
                                        <div class="trending-meta-row">
                                            <span class="trending-sales-badge">{{ $item['sales_count'] }} {{ \Illuminate\Support\Str::plural('sale', $item['sales_count']) }}</span>
                                            @if ($item['collection_name'])
                                                <span class="trending-collection-name">{{ $item['collection_name'] }}</span>
                                            @endif
                                        </div>
                                        <p class="nft-collection-stock">
                                            Editions listed: {{ $item['listed_editions_count'] }} / {{ $item['editions_total'] }}
                                        </p>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="home-shelf__arrow home-shelf__arrow--right" data-carousel-arrow="right" aria-label="Scroll right">&#8250;</button>
                </div>
            </div>
        </section>
    @endif
@endsection

@push('styles')
<style>
    .home-shelf {
        --edge-gap: clamp(18px, 5vw, 120px);
        width: calc(100dvw - (2 * var(--edge-gap)));
        max-width: 1600px;
        margin: 0 auto;
        padding: 40px 0 0;
        position: relative;
        left: 50%;
        transform: translateX(-50%);
    }

    .home-shelf__inner {
        width: 100%;
        padding: 22px;
        border-radius: 14px;
        background: var(--surface-panel);
        border: 1px solid var(--border-soft);
        box-shadow: var(--shadow-elevated);
    }

    .home-shelf__title {
        font-size: 1.45rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 0 0 4px;
    }

    .home-shelf__subtitle {
        font-size: 0.92rem;
        color: var(--subtext-color);
        margin: 0 0 20px;
    }

    .home-shelf__header-row {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 8px;
    }

    .home-shelf__browse-link {
        font-size: 0.92rem;
        font-weight: 600;
        color: var(--link-hover);
        text-decoration: none;
        white-space: nowrap;
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid var(--border-soft);
        background: color-mix(in srgb, var(--surface-main) 82%, transparent);
    }

    .home-shelf__browse-link:hover {
        text-decoration: none;
        border-color: color-mix(in srgb, var(--link-hover) 35%, var(--border-soft));
        background: color-mix(in srgb, var(--link-hover) 10%, var(--surface-main) 90%);
    }

    /* ── Carousel ── */
    .home-shelf__carousel-wrap {
        position: relative;
    }

    .home-shelf__carousel {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        scroll-behavior: smooth;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding: 4px 2px 6px;
    }

    .home-shelf__carousel::-webkit-scrollbar {
        display: none;
    }

    .home-shelf__arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 3;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 1px solid var(--border-soft);
        background: var(--surface-panel);
        color: var(--text-main);
        font-size: 22px;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.2s ease, border-color 0.2s ease, background 0.2s ease;
        box-shadow: var(--shadow-elevated);
    }

    .home-shelf__carousel-wrap:hover .home-shelf__arrow {
        opacity: 1;
    }

    .home-shelf__arrow--left {
        left: -12px;
    }

    .home-shelf__arrow--right {
        right: -12px;
    }

    .home-shelf__arrow:hover {
        background: var(--link-hover);
        color: #fff;
        border-color: var(--link-hover);
    }

    /* ── Currency Cards ── */
    .currency-card {
        flex: 0 0 170px;
        height: 170px;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
        color: var(--text-main);
        background:
            linear-gradient(180deg, color-mix(in srgb, var(--card-accent) 12%, transparent), transparent 44%),
            color-mix(in srgb, var(--surface-main) 86%, #000 14%);
        border: 1px solid var(--border-soft);
        box-shadow: var(--shadow-elevated);
        transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .currency-card::before {
        content: "";
        position: absolute;
        inset: 0 auto auto 0;
        width: 100%;
        height: 3px;
        background: var(--card-accent);
    }

    .currency-card:hover {
        transform: translateY(-4px);
        border-color: color-mix(in srgb, var(--card-accent) 35%, var(--border-soft));
        box-shadow: 0 12px 26px color-mix(in srgb, var(--card-accent) 12%, transparent);
    }

    .currency-card__symbol {
        width: 62px;
        height: 62px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
        color: #fff;
        background: color-mix(in srgb, var(--card-accent) 72%, #111 28%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.16);
    }

    .currency-card__code {
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: 0.06em;
    }

    .currency-card__label {
        font-size: 0.78rem;
        color: var(--subtext-color);
        text-align: center;
        padding: 0 10px;
    }

    /* ── Trending NFT Cards ── */
    .home-shelf__carousel--nfts {
        gap: 16px;
    }

    .home-shelf__trending-card {
        flex: 0 0 220px;
        margin: 0;
    }

    @media (max-width: 700px) {
        .home-shelf {
            --edge-gap: clamp(12px, 4vw, 22px);
            width: calc(100dvw - (2 * var(--edge-gap)));
            padding-top: 28px;
        }

        .home-shelf__inner {
            width: 100%;
            padding: 16px;
        }

        .home-shelf__header-row {
            flex-direction: column;
            align-items: flex-start;
        }

        .currency-card {
            flex: 0 0 140px;
            height: 140px;
        }

        .home-shelf__trending-card {
            flex: 0 0 175px;
        }

        .home-shelf__arrow {
            display: none;
        }
    }

    @supports not (width: 100dvw) {
        .home-shelf {
            width: calc(100vw - (2 * var(--edge-gap)));
        }
    }
</style>
@endpush

@push('scripts')
    {{-- Page JS --}}
    @vite('resources/js/nft-board/homepage-entry.jsx')
    <script>
        async function toggleLike(nftId, btn) {
            @guest
                window.location.href = "{{ route('login', ['redirect' => request()->fullUrl()]) }}";
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

                if (!response.ok) {
                    throw new Error('Failed');
                }
            } catch {
                btn.innerText = isLiked ? '♥' : '♡';
                btn.dataset.liked = isLiked ? '1' : '0';
                btn.style.color = isLiked ? '#ff4d4d' : 'white';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-carousel-arrow]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var wrap = btn.closest('.home-shelf__carousel-wrap');
                    var track = wrap ? wrap.querySelector('[data-carousel-track]') : null;
                    if (!track) return;
                    var scrollAmount = track.clientWidth * 0.6;
                    var direction = btn.dataset.carouselArrow === 'left' ? -1 : 1;
                    track.scrollBy({ left: scrollAmount * direction, behavior: 'smooth' });
                });
            });
        });
    </script>
@endpush

