<?php

namespace App\Http\Controllers;

use App\Services\AmeexWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AmeexWebhookController extends Controller
{
    public function __invoke(Request $request, AmeexWebhookService $webhookService): Response
    {
        $payload = $request->all();

        try {
            $result = $webhookService->handle($payload);
        } catch (\Throwable $exception) {
            report($exception);

            return response('ERROR', 500);
        }

        return response($result['success'] ? 'OK' : 'IGNORED', $result['success'] ? 200 : 422);
    }
}
