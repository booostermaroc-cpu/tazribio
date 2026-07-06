<?php

namespace App\Http\Controllers;

use App\Models\OrderReview;
use App\Services\OrderReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderReviewController extends Controller
{
    public function show(string $token): View
    {
        $review = $this->findReview($token);

        if ($review->isSubmitted()) {
            return view('reviews.thank-you', [
                'review' => $review,
                'alreadySubmitted' => true,
            ]);
        }

        return view('reviews.form', [
            'review' => $review,
            'order' => $review->order,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse|View
    {
        $review = $this->findReview($token);

        if ($review->isSubmitted()) {
            return view('reviews.thank-you', [
                'review' => $review,
                'alreadySubmitted' => true,
            ]);
        }

        $validated = $request->validate([
            'product_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'service_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        app(OrderReviewService::class)->submit($review, $validated);

        return redirect()->route('reviews.show', ['token' => $token]);
    }

    protected function findReview(string $token): OrderReview
    {
        return OrderReview::query()
            ->with(['order.client', 'order.items.product'])
            ->where('token', $token)
            ->firstOrFail();
    }
}
