<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryProvider;
use App\Models\Shipment;
use App\Services\Delivery\AmeexDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AmeexController extends Controller
{
    public function deliveryNote(Shipment $shipment, Request $request): Response|StreamedResponse
    {
        $company = $shipment->deliveryCompany;

        if (! $company || $company->provider !== DeliveryProvider::Ameex) {
            abort(404, __('codflow.delivery.ameex_provider_required'));
        }

        if (blank($shipment->ameex_delivery_note_ref)) {
            abort(404, __('codflow.delivery.ameex_ref_missing'));
        }

        $result = app(AmeexDeliveryService::class)->printDeliveryNoteHtml(
            $company,
            $shipment->ameex_delivery_note_ref,
        );

        if (! $result['success']) {
            abort(502, $result['message']);
        }

        $filename = 'bl-ameex-'.$shipment->tracking_number.'.html';

        if ($request->boolean('download')) {
            return response()->streamDownload(
                fn () => print ($result['html'] ?? ''),
                $filename,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            );
        }

        return response($result['html'] ?? '', 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
