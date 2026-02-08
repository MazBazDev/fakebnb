<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReplyReviewRequest;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Review;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

#[Group('Reviews', 'Avis sur les annonces')]
class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Listing $listing)
    {
        $perPage = (int) $request->integer('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        $reviews = Review::query()
            ->where('listing_id', $listing->id)
            ->with(['guest', 'repliedBy'])
            ->latest()
            ->paginate($perPage);

        return ReviewResource::collection($reviews);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReviewRequest $request, Booking $booking)
    {
        Gate::authorize('create', [Review::class, $booking]);

        $review = Review::create([
            'booking_id' => $booking->id,
            'listing_id' => $booking->listing_id,
            'guest_user_id' => $booking->guest_user_id,
            'rating' => $request->validated()['rating'],
            'comment' => $request->validated()['comment'],
        ]);

        return ReviewResource::make($review->load(['guest', 'repliedBy']))
            ->response()
            ->setStatusCode(201);
    }

    public function reply(ReplyReviewRequest $request, Review $review)
    {
        Gate::authorize('reply', $review);

        $review->reply_body = $request->validated()['reply_body'];
        $review->replied_by_user_id = $request->user()->id;
        $review->replied_at = now();
        $review->save();

        return ReviewResource::make($review->load(['guest', 'repliedBy']));
    }
}
