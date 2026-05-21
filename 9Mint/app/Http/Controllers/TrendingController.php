<?php

namespace App\Http\Controllers;

use App\Models\Nft;
use App\Models\Listing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TrendingController extends Controller
{
    public function index()
    {
        $sevenDaysAgo = now()->subDays(7);

        $trendingNfts = Nft::query()
            ->select([
                'nfts.id',
                'nfts.name',
                'nfts.slug',
                'nfts.image_url',
                'nfts.thumbnail_url',
                'nfts.collection_id',
                'nfts.editions_total',
                'nfts.primary_ref_amount',
                'nfts.primary_ref_currency',
                'nfts.created_at',
                DB::raw('COUNT(sales_histories.id) as sales_count'),
            ])
            ->join('nft_tokens', 'nft_tokens.nft_id', '=', 'nfts.id')
            ->join('sales_histories', 'sales_histories.token_id', '=', 'nft_tokens.id')
            ->where('sales_histories.sold_at', '>=', $sevenDaysAgo)
            ->where('nfts.is_active', true)
            ->groupBy(
                'nfts.id', 'nfts.name', 'nfts.slug', 'nfts.image_url',
                'nfts.thumbnail_url', 'nfts.collection_id', 'nfts.editions_total',
                'nfts.primary_ref_amount', 'nfts.primary_ref_currency', 'nfts.created_at'
            )
            ->orderByDesc('sales_count')
            ->limit(50)
            ->with('collection')
            ->withAvg('reviews', 'rating')
            ->get();

        $nftIds = $trendingNfts->pluck('id')->all();

        $activeListings = empty($nftIds)
            ? collect()
            : Listing::query()
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
        $listedCountByNftId = $activeListings->groupBy('nft_id')->map(fn ($rows) => $rows->count());

        $favouriteIds = collect();
        if (Auth::check()) {
            $favouriteIds = Auth::user()->favourites()->pluck('nfts.id');
        }

        foreach ($trendingNfts as $nft) {
            $nft->active_listing = $listingByNftId->get($nft->id);
            $nft->listed_editions_count = (int) ($listedCountByNftId->get($nft->id, 0));
        }

        return view('trending', [
            'trendingNfts' => $trendingNfts,
            'favouriteIds' => $favouriteIds,
        ]);
    }
}
