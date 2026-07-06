<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\PickupRequest;
use App\Models\ReturnBon;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentPdfService
{
    public function deliveryNote(Order $order): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdf.delivery-note', [
            'order' => $order->loadMissing(['client', 'items.product']),
            'settings' => SettingService::get(),
        ]);
    }

    public function returnBon(ReturnBon $returnBon): \Barryvdh\DomPDF\PDF
    {
        $order = $returnBon->loadMissing('order.client')->order;

        return Pdf::loadView('pdf.return-bon', [
            'returnBon' => $returnBon,
            'order' => $order,
            'settings' => SettingService::get(),
            'qrCode' => app(QrCodeService::class)->generateDataUri($returnBon->barcode_token ?? $order?->order_number ?? $returnBon->return_number),
        ]);
    }

    public function invoice(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice->loadMissing(['order.client', 'order.items.product']),
            'settings' => SettingService::get(),
        ]);
    }

    public function pickupRequest(PickupRequest $pickupRequest): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdf.pickup-request', [
            'pickup' => $pickupRequest->loadMissing('deliveryCompany'),
            'settings' => SettingService::get(),
        ]);
    }

    public function download(\Barryvdh\DomPDF\PDF $pdf, string $filename): StreamedResponse
    {
        return $pdf->download($filename);
    }

    public function stream(\Barryvdh\DomPDF\PDF $pdf): Response
    {
        return $pdf->stream();
    }
}
