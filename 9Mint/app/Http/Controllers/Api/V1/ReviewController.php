<?php

namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Controller;  
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        DB::table('reviews')->insert([
            'name' => $request->name,
            'review' => $request->review,
            'rating' => $request->rating,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Review added successfully'
        ]);
    }

    public function highRated()
    {
        $baseQuery = DB::table('reviews');

        $hasPreferredReviews = (clone $baseQuery)
            ->where('rating', '>=', 4)
            ->exists();

        $reviews = $baseQuery
            ->when($hasPreferredReviews, function ($query) {
                $query->where('rating', '>=', 4);
            })
            ->orderByDesc('rating')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($reviews);
    }

    public function destroy(Request $request, int $reviewId)
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessAdminFeatures()) {
            abort(403);
        }

        $deleted = DB::table('reviews')->where('id', $reviewId)->delete();

        if (! $deleted) {
            abort(404);
        }

        return back()->with('status', 'Website review deleted.');
    }
}
