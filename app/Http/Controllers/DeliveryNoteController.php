<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\DeliveryNoteService;
use Illuminate\Http\Request;

class DeliveryNoteController extends Controller
{
    public function __invoke(Request $request, Order $order, DeliveryNoteService $deliveryNoteService)
    {
        $this->authorize('view', $order);

        if ($request->boolean('print')) {
            return $deliveryNoteService->stream($order);
        }

        return $deliveryNoteService->download($order);
    }
}
