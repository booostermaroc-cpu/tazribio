<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderReview;
use Illuminate\Support\Str;

class OrderReviewService
{
    public function forOrder(Order $order): OrderReview
    {
        return OrderReview::query()->firstOrCreate(
            ['order_id' => $order->id],
            ['token' => Str::random(48)],
        );
    }

    public function publicUrl(OrderReview $review): string
    {
        return route('reviews.show', ['token' => $review->token]);
    }

    public function whatsAppMessage(Order $order, string $url): string
    {
        $name = $order->client?->full_name ?? __('codflow.order.default_client_name');

        return __('codflow.review.whatsapp_message', [
            'name' => $name,
            'order' => $order->order_number,
            'url' => $url,
        ]);
    }

    public function markLinkSent(Order $order): OrderReview
    {
        $review = $this->forOrder($order);

        $review->update([
            'link_sent_at' => now(),
            'link_sent_by' => auth()->id(),
        ]);

        return $review->fresh();
    }

    /** @param  array{product_rating: int, service_rating: int, comment?: string|null}  $data */
    public function submit(OrderReview $review, array $data): OrderReview
    {
        $review->update([
            'product_rating' => $data['product_rating'],
            'service_rating' => $data['service_rating'],
            'comment' => $data['comment'] ?? null,
            'submitted_at' => now(),
        ]);

        return $review->fresh();
    }
}
