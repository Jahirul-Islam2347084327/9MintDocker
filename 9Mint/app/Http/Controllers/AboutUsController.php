<?php

namespace App\Http\Controllers;

use App\Models\Nft;
use Illuminate\Support\Facades\DB;

class AboutUsController extends Controller
{
    public function index()
    {
        $nfts = Nft::marketVisible()->get();

        $baseQuery = DB::table('reviews');

        $hasPreferred = (clone $baseQuery)->where('rating', '>=', 4)->exists();

        $reviews = $baseQuery
            ->when($hasPreferred, fn ($q) => $q->where('rating', '>=', 4))
            ->orderByDesc('rating')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'name' => $r->name ?: 'Anonymous',
                'rating' => (int) $r->rating,
                'review' => $r->review,
            ])
            ->values();

        return view('about-us', compact('nfts', 'reviews'));
    }
}
