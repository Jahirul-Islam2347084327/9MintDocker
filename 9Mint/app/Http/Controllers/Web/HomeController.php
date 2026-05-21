<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Nft;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $likedIds = auth()->check()
            ? auth()->user()->favourites()->pluck('nfts.id')->all()
            : [];
        $nfts = Nft::marketVisible()->get();

        // Board NFTs: in-stock, active, not soft-deleted
        $baseQuery = Nft::marketVisible()
            ->where('editions_remaining', '>', 0)
            ->whereNull('deleted_at')
            ->with('collection');

        // Trending: most favourited (top 20)
        $trending = (clone $baseQuery)
            ->withCount('favouritedBy')
            ->orderByDesc('favourited_by_count')
            ->limit(20)
            ->get();

        // Random fill (up to 40 more, excluding trending IDs)
        $trendingIds = $trending->pluck('id')->toArray();
        $random = (clone $baseQuery)
            ->whereNotIn('id', $trendingIds)
            ->inRandomOrder()
            ->limit(40)
            ->get();

        $nftIds = $trending->merge($random)->pluck('id')->unique()->values();
        $activeListings = Listing::query()
            ->join('nft_tokens', 'listings.token_id', '=', 'nft_tokens.id')
            ->whereIn('nft_tokens.nft_id', $nftIds)
            ->where('listings.status', 'active')
            ->where(function ($q) {
                $q->whereNull('listings.reserved_until')
                    ->orWhere('listings.reserved_until', '<', now());
            })
            ->orderBy('listings.ref_amount', 'asc')
            ->get(['listings.*', 'nft_tokens.nft_id']);

        $listingByNftId = $activeListings->groupBy('nft_id')->map->first();

        // Merge, shuffle, and format for the React board
        $boardNfts = $trending->merge($random)->shuffle()->map(function ($nft) use ($listingByNftId, $likedIds) {
            $listing = $listingByNftId->get($nft->id);

            return [
                'id' => $nft->id,
                'name' => $nft->name,
                'image_url' => $nft->thumbnail_url ?? $nft->image_url,
                'editions_remaining' => $nft->editions_remaining,
                'editions_total' => $nft->editions_total,
                'listing_id' => $listing?->id,
                'price' => $listing?->ref_amount,
                'currency' => $listing?->ref_currency ?? 'GBP',
                'collection_slug' => $nft->collection?->slug,
                'collection_name' => $nft->collection?->name,
                'nft_url' => route('nfts.show', ['slug' => $nft->slug]),
                'collection_url' => $nft->collection
                    ? route('collections.show', ['slug' => $nft->collection->slug])
                    : null,
                'is_liked' => in_array($nft->id, $likedIds, true),
            ];
        })->values();

        $sevenDaysAgo = now()->subDays(7);
        $homeTrending = Nft::query()
            ->select([
                'nfts.id', 'nfts.name', 'nfts.slug',
                'nfts.image_url', 'nfts.thumbnail_url',
                'nfts.editions_total', 'nfts.collection_id',
                DB::raw('COUNT(sales_histories.id) as sales_count'),
            ])
            ->join('nft_tokens', 'nft_tokens.nft_id', '=', 'nfts.id')
            ->join('sales_histories', 'sales_histories.token_id', '=', 'nft_tokens.id')
            ->where('sales_histories.sold_at', '>=', $sevenDaysAgo)
            ->where('nfts.is_active', true)
            ->groupBy('nfts.id', 'nfts.name', 'nfts.slug', 'nfts.image_url', 'nfts.thumbnail_url', 'nfts.editions_total', 'nfts.collection_id')
            ->orderByDesc('sales_count')
            ->limit(20)
            ->with('collection')
            ->get();

        $trendingNftIds = $homeTrending->pluck('id')->all();
        $trendingListings = empty($trendingNftIds)
            ? collect()
            : Listing::query()
                ->join('nft_tokens', 'listings.token_id', '=', 'nft_tokens.id')
                ->whereIn('nft_tokens.nft_id', $trendingNftIds)
                ->where('listings.status', 'active')
                ->where(fn ($q) => $q->whereNull('listings.reserved_until')->orWhere('listings.reserved_until', '<', now()))
                ->orderBy('listings.ref_amount', 'asc')
                ->get(['listings.*', 'nft_tokens.nft_id']);

        $trendingListingByNft = $trendingListings->groupBy('nft_id')->map->first();

        $homeTrendingListedCountByNft = $trendingListings->groupBy('nft_id')->map(fn ($rows) => $rows->count());

        $homeTrendingItems = $homeTrending->map(function ($nft) use ($trendingListingByNft, $homeTrendingListedCountByNft, $likedIds) {
            $listing = $trendingListingByNft->get($nft->id);
            $symbols = config('pricing.currency_symbols', []);
            $currency = $listing?->ref_currency ?? 'GBP';
            $symbol = $symbols[$currency] ?? '';
            $priceFormatted = $listing
                ? ($symbol ? $symbol . number_format($listing->ref_amount, 2) : number_format($listing->ref_amount, 2) . ' ' . $currency)
                : null;

            return [
                'id' => $nft->id,
                'name' => $nft->name,
                'slug' => $nft->slug,
                'url' => route('nfts.show', ['slug' => $nft->slug]),
                'thumb' => asset(ltrim($nft->thumbnail_url ?? $nft->image_url, '/')),
                'price' => $priceFormatted,
                'listing_id' => $listing?->id,
                'currency' => $currency,
                'sales_count' => $nft->sales_count,
                'collection_name' => $nft->collection?->name,
                'listed_editions_count' => (int) ($homeTrendingListedCountByNft->get($nft->id, 0)),
                'editions_total' => (int) $nft->editions_total,
                'is_liked' => in_array($nft->id, $likedIds, true),
            ];
        })->values();

        $enabledCurrencies = config('pricing.enabled_currencies', ['GBP']);
        $currencyMeta = [];
        $currencyLabels = [
            'GBP' => 'British Pound',
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'BTC' => 'Bitcoin',
            'ETH' => 'Ethereum',
        ];
        $currencyColors = [
            'GBP' => '#8b5cf6',
            'USD' => '#22c55e',
            'EUR' => '#3b82f6',
            'BTC' => '#f59e0b',
            'ETH' => '#6366f1',
        ];
        foreach ($enabledCurrencies as $cur) {
            $symbol = config("pricing.currency_symbols.{$cur}", $cur);
            $currencyMeta[] = [
                'code' => $cur,
                'symbol' => $symbol,
                'label' => $currencyLabels[$cur] ?? $cur,
                'color' => $currencyColors[$cur] ?? '#6b7280',
                'url' => route('search.nfts', ['currency' => $cur, 'in_stock' => 1]),
            ];
        }

        return view('homepage', compact('nfts', 'boardNfts', 'homeTrendingItems', 'currencyMeta'));
    }
}
