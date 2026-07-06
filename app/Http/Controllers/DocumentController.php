<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\PickupRequest;
use App\Models\ReturnBon;
use App\Services\DocumentPdfService;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function deliveryNote(Order $order, Request $request)
    {
        $pdf = app(DocumentPdfService::class)->deliveryNote($order);

        return $request->boolean('print')
            ? $pdf->stream('bon-livraison-'.$order->order_number.'.pdf')
            : $pdf->download('bon-livraison-'.$order->order_number.'.pdf');
    }

    public function returnBon(ReturnBon $returnBon, Request $request)
    {
        $pdf = app(DocumentPdfService::class)->returnBon($returnBon);

        return $request->boolean('print')
            ? $pdf->stream('bon-retour-'.$returnBon->return_number.'.pdf')
            : $pdf->download('bon-retour-'.$returnBon->return_number.'.pdf');
    }

    public function invoice(Invoice $invoice, Request $request)
    {
        $pdf = app(DocumentPdfService::class)->invoice($invoice);

        return $request->boolean('print')
            ? $pdf->stream('facture-'.$invoice->invoice_number.'.pdf')
            : $pdf->download('facture-'.$invoice->invoice_number.'.pdf');
    }

    public function pickupRequest(PickupRequest $pickupRequest, Request $request)
    {
        $pdf = app(DocumentPdfService::class)->pickupRequest($pickupRequest);

        return $request->boolean('print')
            ? $pdf->stream('enlevement-'.$pickupRequest->id.'.pdf')
            : $pdf->download('enlevement-'.$pickupRequest->id.'.pdf');
    }
}
