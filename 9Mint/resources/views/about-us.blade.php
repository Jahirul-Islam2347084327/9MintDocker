@extends('layouts.app')

@section('title', 'About Us')

@push('styles')
  @vite('resources/css/pages/about-contact.css')
  <style>
    /* ── Reset inherited about-page defaults ── */
    .about-page-redesign {
        min-height: auto;
        padding: 0;
    }

    .about-page-redesign p {
        text-align: left;
        margin-top: 0;
    }

    /* ── Hero ── */
    .about-hero {
        --edge-gap: clamp(18px, 5vw, 120px);
        width: calc(100dvw - (2 * var(--edge-gap)));
        max-width: 1600px;
        margin: 0 auto;
        padding: 64px 40px 56px;
        position: relative;
        left: 50%;
        transform: translateX(-50%);
        text-align: center;
        border-radius: 24px;
    }

    @supports not (width: 100dvw) {
        .about-hero { width: calc(100vw - (2 * var(--edge-gap))); }
    }

    .about-hero__title {
        font-size: clamp(2.6rem, 5vw, 4rem);
        font-weight: 800;
        letter-spacing: -0.02em;
        color: var(--text-main);
        margin: 0 0 12px;
        line-height: 1.1;
    }

    .about-hero__accent {
        color: var(--link-hover);
    }

    .about-hero__tagline {
        font-size: clamp(1.05rem, 2vw, 1.3rem);
        color: var(--subtext-color);
        max-width: 540px;
        margin: 0 auto;
        line-height: 1.5;
    }

    /* ── Content wrapper (mirrors discovery board width) ── */
    .about-content {
        --edge-gap: clamp(18px, 5vw, 120px);
        width: calc(100dvw - (2 * var(--edge-gap)));
        max-width: 1600px;
        margin: 0 auto;
        padding: 0 0 56px;
        position: relative;
        left: 50%;
        transform: translateX(-50%);
    }

    @supports not (width: 100dvw) {
        .about-content { width: calc(100vw - (2 * var(--edge-gap))); }
    }

    /* ── Info cards grid ── */
    .about-cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }

    .about-card {
        background: var(--surface-panel);
        border: 1px solid var(--border-soft);
        border-radius: 14px;
        padding: 32px 28px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .about-card__title {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
    }

    .about-card__text {
        font-size: 0.92rem;
        line-height: 1.6;
        color: var(--subtext-color);
        margin: 0;
    }

    /* ── Team section ── */
    .about-team {
        margin-top: 0;
    }

    .about-team__heading {
        font-size: 1.45rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 0 0 16px;
    }

    .about-team .team-table-wrap {
        margin-top: 0;
    }

    .about-team .team-table {
        width: 100%;
    }

    .about-team .team-table th,
    .about-team .team-table td {
        padding: 13px 16px;
        font-size: 0.94rem;
    }

    .about-team .team-table th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.78rem;
        letter-spacing: 0.06em;
        color: var(--subtext-color);
    }

    .about-team .team-table td b {
        font-weight: 600;
        color: var(--text-main);
    }

    .about-team .team-table tbody tr {
        transition: background 0.15s ease;
    }

    .about-team .team-table tbody tr:hover {
        background: color-mix(in srgb, var(--link-hover) 5%, transparent);
    }

    /* ── Review board (kept from previous) ── */
    .review-board {
        --edge-gap: clamp(18px, 5vw, 120px);
        width: calc(100dvw - (2 * var(--edge-gap)));
        max-width: 1600px;
        margin: 0 auto;
        padding: 28px 0;
        border-radius: 16px;
        position: relative;
        left: 50%;
        transform: translateX(-50%);
        overflow: hidden;
        background: var(--surface-panel);
        border: 1px solid var(--border-soft);
        box-shadow: var(--shadow-elevated);
    }

    @supports not (width: 100dvw) {
        .review-board { width: calc(100vw - (2 * var(--edge-gap))); }
    }

    .review-board__header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        padding: 0 28px 18px;
    }

    .review-board__title {
        font-size: 1.45rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 0 0 2px;
    }

    .review-board__subtitle {
        font-size: 0.92rem;
        color: var(--subtext-color);
        margin: 0;
    }

    .review-board__cta {
        font-size: 0.92rem;
        font-weight: 600;
        color: var(--link-hover);
        text-decoration: none;
        padding: 8px 14px;
        border-radius: 8px;
        border: 1px solid var(--border-soft);
        background: color-mix(in srgb, var(--surface-main) 82%, transparent);
        white-space: nowrap;
    }

    .review-board__cta:hover {
        border-color: color-mix(in srgb, var(--link-hover) 35%, var(--border-soft));
        background: color-mix(in srgb, var(--link-hover) 10%, var(--surface-main) 90%);
    }

    .review-board__track-wrap {
        position: relative;
        overflow: hidden;
        mask-image: linear-gradient(to right, transparent 0%, #000 4%, #000 96%, transparent 100%);
        -webkit-mask-image: linear-gradient(to right, transparent 0%, #000 4%, #000 96%, transparent 100%);
    }

    .review-board__track {
        display: flex;
        gap: 16px;
        width: max-content;
        animation: review-scroll var(--scroll-duration, 40s) linear infinite;
        padding: 4px 28px;
    }

    .review-board__track:hover {
        animation-play-state: paused;
    }

    @keyframes review-scroll {
        from { transform: translateX(0); }
        to   { transform: translateX(-50%); }
    }

    .review-board__card {
        flex: 0 0 320px;
        min-height: 160px;
        padding: 20px 22px;
        border-radius: 12px;
        background: color-mix(in srgb, var(--surface-main) 88%, #000 12%);
        border: 1px solid var(--border-soft);
        display: flex;
        flex-direction: column;
        gap: 10px;
        cursor: default;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .review-board__card-actions {
        display: flex;
        justify-content: flex-end;
        flex: 0 0 auto;
        min-width: 112px;
    }

    .review-board__footer {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 12px;
        min-height: 36px;
    }

    .review-board__delete {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 10px;
        border-radius: 8px;
        border: 1px solid var(--border-soft);
        background: transparent;
        color: var(--subtext-color);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        cursor: pointer;
    }

    .review-board__delete:hover {
        color: var(--text-main);
        border-color: var(--link-hover);
    }

    .review-board__card:hover {
        border-color: color-mix(in srgb, var(--link-hover) 30%, var(--border-soft));
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.18);
    }

    .review-board__stars {
        font-size: 1.15rem;
        color: #f5a623;
        letter-spacing: 0.05em;
        line-height: 1;
    }

    .review-board__text {
        flex: 1;
        font-size: 0.92rem;
        line-height: 1.5;
        color: var(--text-main);
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .review-board__author {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--subtext-color);
        margin: 0;
        flex: 1 1 auto;
    }

    .review-board__empty {
        text-align: center;
        padding: 40px 20px;
        color: var(--subtext-color);
        font-size: 1rem;
    }

    html.light-mode .about-card {
        background: var(--surface-chrome);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
    }

    html.light-mode .review-board {
        background: var(--surface-chrome);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
    }

    html.light-mode .review-board__cta {
        background: color-mix(in srgb, var(--surface-muted) 88%, white 12%);
    }

    html.light-mode .review-board__card {
        background: var(--surface-muted);
        border-color: rgba(15, 23, 42, 0.1);
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
    }

    html.light-mode .review-board__card:hover {
        border-color: color-mix(in srgb, var(--link-hover) 28%, rgba(15, 23, 42, 0.1));
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.1);
    }

    html.light-mode .about-team .team-table th {
        background: color-mix(in srgb, var(--surface-muted) 88%, white 12%);
    }

    /* ── Mobile ── */
    @media (max-width: 768px) {
        .about-hero {
            --edge-gap: clamp(12px, 4vw, 22px);
            width: calc(100dvw - (2 * var(--edge-gap)));
            padding: 40px 20px 32px;
            border-radius: 18px;
        }

        .about-content {
            --edge-gap: clamp(12px, 4vw, 22px);
            width: calc(100dvw - (2 * var(--edge-gap)));
            padding-bottom: 40px;
        }

        .about-cards {
            grid-template-columns: 1fr;
            gap: 14px;
        }

        .about-card {
            padding: 24px 20px;
        }

        .review-board {
            --edge-gap: clamp(12px, 4vw, 22px);
            width: calc(100dvw - (2 * var(--edge-gap)));
            padding: 20px 0;
        }

        .review-board__header {
            padding: 0 18px 14px;
            flex-direction: column;
            align-items: flex-start;
        }

        .review-board__card {
            flex: 0 0 270px;
            min-height: 140px;
            padding: 16px 18px;
        }

        .review-board__track {
            padding-inline: 18px;
        }
    }
  </style>
@endpush

@section('content')
<div class="about-page-redesign">

    {{-- ══ Hero ══ --}}
    <section class="about-hero">
        <h1 class="about-hero__title">
            Welcome to <span class="about-hero__accent">9Mint</span>
        </h1>
        <p class="about-hero__tagline">
            A vibrant marketplace where digital art meets community. We connect creators and collectors through unique NFTs.
        </p>
    </section>

    {{-- ══ Review Scroll Board ══ --}}
    <section class="review-board" aria-label="User Reviews">
        <div class="review-board__header">
            <div>
                <h2 class="review-board__title">What Our Users Say</h2>
                <p class="review-board__subtitle">Real feedback from the 9Mint community</p>
            </div>
            <a href="/reviewUs" class="review-board__cta">Leave a Review &rarr;</a>
        </div>

        @if (($reviews ?? collect())->isEmpty())
            <p class="review-board__empty">No reviews yet. Be the first to share your experience!</p>
        @else
            @php
                $baseReviewCards = $reviews->values();
                $baseCardCount = max(1, $baseReviewCards->count());
                $minimumCardsPerLoop = 6;
                $loopRepeats = max(1, (int) ceil($minimumCardsPerLoop / $baseCardCount));
                $reviewCards = collect();

                for ($i = 0; $i < $loopRepeats; $i++) {
                    $reviewCards = $reviewCards->concat($baseReviewCards);
                }

                $cardCount = $reviewCards->count();
                $scrollDuration = max(54, $cardCount * 9);
            @endphp
            <div class="review-board__track-wrap">
                <div class="review-board__track" style="--scroll-duration: {{ $scrollDuration }}s;">
                    @for ($pass = 0; $pass < 2; $pass++)
                        @foreach ($reviewCards as $r)
                            <div class="review-board__card">
                                <div class="review-board__stars">{!! str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) !!}</div>
                                <p class="review-board__text">"{{ $r['review'] }}"</p>
                                <div class="review-board__footer">
                                    <p class="review-board__author">— {{ $r['name'] }}</p>
                                    <div class="review-board__card-actions">
                                        @if ($pass === 0 && auth()->check() && auth()->user()->canAccessAdminFeatures())
                                            <form method="POST" action="{{ route('website.reviews.destroy', ['reviewId' => $r['id']]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="review-board__delete">Delete review</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endfor
                </div>
            </div>
        @endif
    </section>

    {{-- ══ Info Cards ══ --}}
    <div class="about-content">
        <div class="about-cards" style="margin-top: 40px;">
            <div class="about-card">
                <h3 class="about-card__title">Who Are We?</h3>
                <p class="about-card__text">
                    9Mint is a simulated e-commerce platform designed to sell and manage
                    Non-Fungible Tokens (NFTs). Our mission is to foster a vibrant community
                    of art enthusiasts and creators.
                </p>
            </div>

            <div class="about-card">
                <h3 class="about-card__title">Our Journey</h3>
                <p class="about-card__text">
                    Founded in 2025, 9Mint began as a small group of artists and tech
                    enthusiasts passionate about digital art and NFTs. We've been growing
                    ever since.
                </p>
            </div>

            <div class="about-card">
                <h3 class="about-card__title">Our Community</h3>
                <p class="about-card__text">
                    We believe art is for everyone. Our platform connects artists and
                    collectors worldwide, building bridges through creativity and shared passion.
                </p>
            </div>
        </div>

        {{-- ══ Team Table ══ --}}
        <section class="about-team">
            <h2 class="about-team__heading">Meet the Team</h2>
            <div class="team-table-wrap">
                <table class="team-table">
                    <thead>
                        <tr>
                            <th align="left">Name</th>
                            <th align="left">Role</th>
                            <th align="left">ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><b>Arjan Singh</b></td><td>Project Lead &amp; Backend</td><td><code>240209768</code></td></tr>
                        <tr><td><b>Dariusz Dabrowski</b></td><td>Backend Lead</td><td><code>240353669</code></td></tr>
                        <tr><td><b>Jahirul Islam</b></td><td>Backend</td><td><code>240219893</code></td></tr>
                        <tr><td><b>Khalil Suleiman</b></td><td>Backend</td><td><code>240248572</code></td></tr>
                        <tr><td><b>Maliyka Liaqat</b></td><td>Frontend Lead</td><td><code>240119641</code></td></tr>
                        <tr><td><b>Hamza Heybe</b></td><td>Frontend</td><td><code>240158042</code></td></tr>
                        <tr><td><b>Naomi Olowu</b></td><td>Frontend</td><td><code>240229043</code></td></tr>
                        <tr><td><b>Vlas Yermachenko</b></td><td>Backend &amp; NFT Artist</td><td><code>240180928</code></td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

</div>
@endsection

@push('scripts')
    @vite('resources/js/page-scripts/about-us-nft-grid-rotator.js')
@endpush
