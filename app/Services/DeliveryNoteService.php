<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class DeliveryNoteService
{
    public function generate(Order $order): \Barryvdh\DomPDF\PDF
    {
        $order->loadMissing(['client', 'items.product']);

        return Pdf::loadView('pdf.delivery-note', [
            'order' => $order,
            'settings' => SettingService::get(),
        ])->setPaper('a4');
    }

    public function download(Order $order): Response
    {
        $filename = 'bon-livraison-'.$order->order_number.'.pdf';

        return $this->generate($order)->download($filename);
    }

    public function stream(Order $order): Response
    {
        $filename = 'bon-livraison-'.$order->order_number.'.pdf';

        return $this->generate($order)->stream($filename);
    }
}
