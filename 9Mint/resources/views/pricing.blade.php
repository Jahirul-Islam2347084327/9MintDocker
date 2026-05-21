@extends('layouts.app')

@section('title', 'Pricing')

@push('styles')
  <style>
    /* ── Layout shell ── */
    .pricing-page {
        min-height: auto;
        padding: 0;
    }

    .pricing-page p {
        text-align: left;
        margin-top: 0;
    }

    /* ── Hero ── */
    .pricing-hero {
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
        .pricing-hero { width: calc(100vw - (2 * var(--edge-gap))); }
    }

    .pricing-hero__title {
        font-size: clamp(2.2rem, 4.5vw, 3.4rem);
        font-weight: 800;
        letter-spacing: -0.02em;
        color: var(--text-main);
        margin: 0 0 12px;
        line-height: 1.1;
    }

    .pricing-hero__accent {
        color: var(--link-hover);
    }

    .pricing-hero__tagline {
        font-size: clamp(1rem, 1.8vw, 1.2rem);
        color: var(--subtext-color);
        max-width: 580px;
        margin: 0 auto;
        line-height: 1.55;
    }

    /* ── Content wrapper ── */
    .pricing-content {
        --edge-gap: clamp(18px, 5vw, 120px);
        width: calc(100dvw - (2 * var(--edge-gap)));
        max-width: 1600px;
        margin: 0 auto;
        padding: 40px 0 56px;
        position: relative;
        left: 50%;
        transform: translateX(-50%);
    }

    @supports not (width: 100dvw) {
        .pricing-content { width: calc(100vw - (2 * var(--edge-gap))); }
    }

    /* ── Principle cards ── */
    .pricing-cards {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }

    .pricing-card {
        background: var(--surface-panel);
        border: 1px solid var(--border-soft);
        border-radius: 14px;
        padding: 32px 28px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .pricing-card__title {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 0;
    }

    .pricing-card__text {
        font-size: 0.92rem;
        line-height: 1.6;
        color: var(--subtext-color);
        margin: 0;
    }

    /* ── How it works panel ── */
    .pricing-panel {
        background: var(--surface-panel);
        border: 1px solid var(--border-soft);
        border-radius: 16px;
        padding: 36px 32px;
        box-shadow: var(--shadow-elevated);
    }

    .pricing-panel__heading {
        font-size: 1.45rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 0 0 24px;
    }

    .pricing-steps {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        counter-reset: step;
    }

    .pricing-step {
        position: relative;
        padding: 24px 20px 24px 20px;
        border-radius: 12px;
        background: color-mix(in srgb, var(--surface-main) 88%, #000 12%);
        border: 1px solid var(--border-soft);
        counter-increment: step;
    }

    .pricing-step::before {
        content: counter(step);
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--link-hover);
        color: #fff;
        font-weight: 700;
        font-size: 0.88rem;
        margin-bottom: 12px;
    }

    .pricing-step__title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-main);
        margin: 0 0 6px;
    }

    .pricing-step__text {
        font-size: 0.88rem;
        line-height: 1.55;
        color: var(--subtext-color);
        margin: 0;
    }

    /* ── Light mode ── */
    html.light-mode .pricing-card {
        background: var(--surface-chrome);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
    }

    html.light-mode .pricing-panel {
        background: var(--surface-chrome);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
    }

    html.light-mode .pricing-step {
        background: var(--surface-muted);
        border-color: rgba(15, 23, 42, 0.1);
    }

    /* ── Mobile ── */
    @media (max-width: 768px) {
        .pricing-hero {
            --edge-gap: clamp(12px, 4vw, 22px);
            width: calc(100dvw - (2 * var(--edge-gap)));
            padding: 40px 20px 32px;
            border-radius: 18px;
        }

        .pricing-content {
            --edge-gap: clamp(12px, 4vw, 22px);
            width: calc(100dvw - (2 * var(--edge-gap)));
            padding: 28px 0 40px;
        }

        .pricing-cards {
            grid-template-columns: 1fr;
            gap: 14px;
        }

        .pricing-card {
            padding: 24px 20px;
        }

        .pricing-panel {
            padding: 28px 20px;
        }

        .pricing-steps {
            grid-template-columns: 1fr;
            gap: 14px;
        }
    }
  </style>
@endpush

@section('content')
<div class="pricing-page">

    {{-- ══ Hero ══ --}}
    <section class="pricing-hero">
        <h1 class="pricing-hero__title">
            Simple, <span class="pricing-hero__accent">Fair</span> Pricing
        </h1>
        <p class="pricing-hero__tagline">
            We value the hard work and creativity of our NFT designers. Prices are kept affordable while honouring each creator's effort.
        </p>
    </section>

    {{-- ══ Principle cards ══ --}}
    <div class="pricing-content">
        <div class="pricing-cards">
            <div class="pricing-card">
                <h3 class="pricing-card__title">One Reference Price</h3>
                <p class="pricing-card__text">
                    Every listing has a single reference price set by the seller. This is the price that all currency conversions are calculated from.
                </p>
            </div>

            <div class="pricing-card">
                <h3 class="pricing-card__title">Pay in Any Currency</h3>
                <p class="pricing-card__text">
                    Choose from multiple supported currencies at checkout. Live conversion rates ensure you always see a fair, up-to-date price.
                </p>
            </div>

            <div class="pricing-card">
                <h3 class="pricing-card__title">No Hidden Fees</h3>
                <p class="pricing-card__text">
                    What you see is what you pay. There are no service charges, listing fees, or surprise costs added at checkout.
                </p>
            </div>
        </div>

        {{-- ══ How it works panel ══ --}}
        <section class="pricing-panel">
            <h2 class="pricing-panel__heading">How It Works</h2>
            <div class="pricing-steps">
                <div class="pricing-step">
                    <h4 class="pricing-step__title">Browse & Discover</h4>
                    <p class="pricing-step__text">
                        Explore collections and find NFTs you love. Each listing displays its reference price and live conversions.
                    </p>
                </div>
                <div class="pricing-step">
                    <h4 class="pricing-step__title">Pick Your Currency</h4>
                    <p class="pricing-step__text">
                        Select the currency you'd like to pay in. The price updates in real time using the latest exchange rates.
                    </p>
                </div>
                <div class="pricing-step">
                    <h4 class="pricing-step__title">Checkout & Own</h4>
                    <p class="pricing-step__text">
                        Complete your purchase and the NFT is yours. Ownership is recorded and visible on your profile immediately.
                    </p>
                </div>
            </div>
        </section>
    </div>

</div>
@endsection
