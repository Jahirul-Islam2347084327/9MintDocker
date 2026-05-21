<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SellerProfileFeedback;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProfileFeedbackController extends Controller
{
    public function store(Request $request, string $username)
    {
        if (! Schema::hasTable('seller_profile_feedback')) {
            return back()->with('error', 'Seller feedback is not available yet.');
        }

        $seller = User::where('name', $username)->firstOrFail();
        $author = $request->user();

        if (! $author) {
            return back()->with('error', 'You must be logged in to leave feedback.');
        }

        $isSelfComment = (int) $author->id === (int) $seller->id;

        $data = $request->validate([
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'is_review' => ['sometimes', 'boolean'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $rating = array_key_exists('rating', $data) && $data['rating'] !== null ? (int) $data['rating'] : null;
        $body = trim((string) ($data['body'] ?? ''));
        $body = $body !== '' ? $body : null;
        $isReview = (bool) ($data['is_review'] ?? false);
        $commentsVisibility = $seller->profileCommentsVisibility();
        $canPostBody = $seller->canViewerPostProfileComment($author);

        if ($rating === null && $body === null) {
            return back()->withErrors([
                'body' => 'Add a comment or a rating before submitting.',
            ])->withInput();
        }

        if ($body !== null && ! $canPostBody) {
            return back()->withErrors([
                'body' => $commentsVisibility === User::PROFILE_COMMENTS_VISIBILITY_FRIENDS
                    ? 'Only friends can post comments on this profile. You can still leave a seller rating.'
                    : 'Comments are disabled on this profile. You can still leave a seller rating.',
            ])->withInput();
        }

        if ($isSelfComment && ($isReview || $rating !== null)) {
            return back()->withErrors([
                'rating' => 'You can comment on your own profile, but you cannot rate yourself.',
            ])->withInput();
        }

        if ($isReview && $rating === null) {
            return back()->withErrors([
                'rating' => 'Choose a star rating when posting a review.',
            ])->withInput();
        }

        if (! $isReview) {
            $rating = null;
        }

        $commentType = $body !== null
            ? ($isReview ? SellerProfileFeedback::TYPE_REVIEW : SellerProfileFeedback::TYPE_COMMENT)
            : ($isReview ? SellerProfileFeedback::TYPE_REVIEW : null);

        SellerProfileFeedback::create([
            'seller_user_id' => $seller->id,
            'author_user_id' => $author->id,
            'rating' => $rating,
            'comment_type' => $commentType,
            'body' => $body,
        ]);

        return back()->with('status', $body !== null
            ? 'Your feedback has been posted.'
            : 'Your seller rating has been submitted.');
    }

    public function destroy(Request $request, string $username, SellerProfileFeedback $feedback)
    {
        if (! Schema::hasTable('seller_profile_feedback')) {
            return back()->with('error', 'Seller feedback is not available yet.');
        }

        $seller = User::where('name', $username)->firstOrFail();
        $viewer = $request->user();

        if ((int) $seller->id !== (int) $feedback->seller_user_id) {
            abort(404);
        }

        $canModerate = $viewer
            && (
                (int) $viewer->id === (int) $seller->id
                || $viewer->canAccessAdminFeatures()
            );

        if (! $canModerate) {
            abort(403);
        }

        if ($feedback->deleted_by_owner_at === null) {
            $feedback->update([
                'deleted_by_owner_at' => now(),
                'deleted_by_owner_user_id' => $viewer->id,
            ]);
        }

        return back()->with('status', (int) $viewer->id === (int) $seller->id
            ? 'Comment removed from your profile.'
            : 'Comment removed by admin.');
    }
}
