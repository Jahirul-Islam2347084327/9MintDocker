@extends('layouts.app')

@section('title', 'Products')

@push('styles')
    @vite('resources/css/pages/products.css')
@endpush

@section('content')
    {{-- Collections --}}
    <section id="NFT_collections">
        <h2>All collections</h2>

        {{-- Empty --}}
        @if ($collections->isEmpty())
            <p class="no-collections">
                No collections have been added yet. Please check back later.
            </p>
        @else
            <div class="nft-filter-wrapper">
                <div class="nft-filter-bar">
                    <div class="nft-filter-group">
                        <label for="collection-filter-name">Name</label>
                        <input type="text" id="collection-filter-name" placeholder="Search by collection name">
                    </div>
                    <div class="nft-filter-group">
                        <label for="collection-filter-sort">Sort</label>
                        <select id="collection-filter-sort">
                            <option value="default">Default</option>
                            <option value="newest">Newest</option>
                            <option value="price-asc">Price: Low to High</option>
                            <option value="price-desc">Price: High to Low</option>
                            <option value="rating-desc">Rating: High to Low</option>
                        </select>
                    </div>
                    <div class="nft-filter-group">
                        <label for="collection-filter-currency">Currency</label>
                        <select id="collection-filter-currency">
                            <option value="">All</option>
                        </select>
                    </div>
                    <div class="nft-filter-group">
                        <label for="collection-filter-min-price">Min Price</label>
                        <input type="number" id="collection-filter-min-price" min="0" placeholder="0">
                    </div>
                    <div class="nft-filter-group">
                        <label for="collection-filter-max-price">Max Price</label>
                        <input type="number" id="collection-filter-max-price" min="0" placeholder="Any">
                    </div>
                    <div class="nft-filter-group nft-filter-check">
                        <label><input type="checkbox" id="collection-filter-in-stock"> In Stock Only</label>
                    </div>
                    <div class="nft-filter-group nft-filter-check">
                        <label><input type="checkbox" id="collection-filter-one-of-one"> 1-of-1 Only</label>
                    </div>
                    <button type="button" class="filter-apply-btn" onclick="applyCollectionFilters()">Apply</button>
                    <button type="button" class="filter-reset-btn" onclick="resetCollectionFilters()">Reset</button>
                </div>

                <p id="collection-filter-no-results" style="display:none; text-align:center; color:var(--text-muted); margin-top:2rem;">
                    No collections match your filters.
                </p>

                <div class="products-collection-list" data-collection-list>
                    @foreach ($collections as $collection)
                        @php
                            $nftPreviewImages = $collection->nfts
                                ->flatMap(fn ($nft) => [$nft->thumbnail_url, $nft->image_url])
                                ->filter()
                                ->unique()
                                ->values();
                            $rawCollectionCoverImageUrl = $collection->cover_image_url;
                            $hasDedicatedCollectionCover = $rawCollectionCoverImageUrl
                                && !$nftPreviewImages->contains($rawCollectionCoverImageUrl);
                            $collectionCoverImageUrl = $hasDedicatedCollectionCover ? $rawCollectionCoverImageUrl : null;
                            $previewImageUrl = $collectionCoverImageUrl ?: $nftPreviewImages->first();
                            $resolvedPreviewImageUrl = $previewImageUrl ? asset(ltrim($previewImageUrl, '/')) : null;
                            $rotationImageUrls = $collectionCoverImageUrl
                                ? collect()
                                : $nftPreviewImages->map(fn ($url) => asset(ltrim($url, '/')))->values();
                            $totalEditions = (int) $collection->nfts->sum('editions_total');
                            $listedEditions = (int) ($collection->listed_editions_count ?? 0);
                            $lowestPricedNft = $collection->nfts
                                ->filter(fn ($nft) => $nft->primary_ref_amount !== null && $nft->primary_ref_currency)
                                ->sortBy(fn ($nft) => (float) $nft->primary_ref_amount)
                                ->first();
                            $collectionPrice = $lowestPricedNft ? (float) $lowestPricedNft->primary_ref_amount : null;
                            $collectionCurrency = $lowestPricedNft?->primary_ref_currency ?? '';
                            $collectionRating = (float) $collection->nfts
                                ->map(fn ($nft) => $nft->reviews_avg_rating ? (float) $nft->reviews_avg_rating : null)
                                ->filter(fn ($rating) => $rating !== null)
                                ->avg();
                            $isOneOfOneCollection = $collection->nfts->isNotEmpty()
                                && $collection->nfts->every(fn ($nft) => (int) $nft->editions_total === 1);
                        @endphp
                        <a
                            class="collection-card"
                            href="{{ route('collections.show', ['slug' => $collection->slug]) }}"
                            data-collection-card
                            data-name="{{ strtolower($collection->name) }}"
                            data-price="{{ $collectionPrice !== null ? number_format($collectionPrice, 8, '.', '') : '' }}"
                            data-currency="{{ $collectionCurrency }}"
                            data-in-stock="{{ $listedEditions > 0 ? '1' : '0' }}"
                            data-one-of-one="{{ $isOneOfOneCollection ? '1' : '0' }}"
                            data-created="{{ $collection->created_at?->timestamp ?? 0 }}"
                            data-rating="{{ number_format($collectionRating ?: 0, 2, '.', '') }}"
                        >
                            @if ($resolvedPreviewImageUrl)
                                <div class="collection-image-wrapper">
                                    <div class="collection-image-frame" style="--collection-preview-bg-image: url('{{ $resolvedPreviewImageUrl }}');">
                                        @if ($collectionCoverImageUrl)
                                            <img
                                                src="{{ $resolvedPreviewImageUrl }}"
                                                alt="{{ $collection->name }} Preview"
                                                class="collection-preview"
                                            >
                                        @else
                                            <img
                                                src="{{ $resolvedPreviewImageUrl }}"
                                                alt="{{ $collection->name }} NFT Preview"
                                                class="collection-preview"
                                                @if ($rotationImageUrls->count() > 1)
                                                    data-images='@json($rotationImageUrls)'
                                                @endif
                                            >
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div class="collection-content">
                                <h3>{{ $collection->name }}</h3>

                                @if ($collection->description)
                                    <p>{{ $collection->description }}</p>
                                @endif

                                @if($totalEditions > 0)
                                    <p class="collection-stock">
                                        Stock: {{ $listedEditions }} NFTs listed (out of {{ $totalEditions }})
                                    </p>
                                @endif

                                <p>Click to find more about each individual NFT.</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
@endsection

@push('scripts')
    @vite('resources/js/page-scripts/products-collection-preview-rotator.js')
    @vite('resources/js/page-scripts/collection-filters.js')
@endpush


