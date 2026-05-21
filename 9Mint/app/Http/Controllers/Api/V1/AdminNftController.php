<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNftRequest;
use App\Models\Listing;
use App\Models\Nft;
use App\Models\NftToken;
use App\Services\BlockchainLedgerService;
use App\Services\ThumbnailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminNftController extends Controller
{
    public function store(StoreNftRequest $request)
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessAdminFeatures()) {
            abort(403, 'Forbidden');
        }

        $data = $request->validated();

        $path = $request->file('image')->store('nfts', 'public');
        $imageUrl = Storage::url($path);

        // Generate 720px WebP thumbnail
        $thumbnailUrl = ThumbnailService::generate(
            storage_path('app/public/' . $path),
            'storage/nfts/thumbs',
            'thumb'
        );

        $baseSlug = Str::slug($data['name']);
        $slug = $baseSlug;
        $counter = 1;

        while (Nft::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        $nft = DB::transaction(function () use ($data, $slug, $imageUrl, $thumbnailUrl, $user) {
            $nft = Nft::create([
                'name' => $data['name'],
                'slug' => $slug,
                'collection_id' => $data['collection_id'],
                'editions_total' => $data['editions_total'],
                'editions_remaining' => $data['editions_total'],
                'image_url' => $imageUrl,
                'thumbnail_url' => $thumbnailUrl,
                'is_active' => true,
            ]);

            for ($i = 1; $i <= $data['editions_total']; $i++) {
                $token = NftToken::create([
                    'nft_id' => $nft->id,
                    'serial_number' => $i,
                    'owner_user_id' => $user->id,
                    'status' => 'listed',
                ]);

                app(BlockchainLedgerService::class)->ensureChainTokenForToken($token);

                $listing = Listing::create([
                    'token_id' => $token->id,
                    'seller_user_id' => $user->id,
                    'status' => 'active',
                    'ref_amount' => $data['listing_ref_amount'],
                    'ref_currency' => $data['listing_ref_currency'],
                ]);

            }

            return $nft;
        });

        return response()->json(['data' => $nft], 201);
    }
}
