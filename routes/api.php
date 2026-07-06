<?php

use App\Http\Controllers\AmeexWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/ameex', AmeexWebhookController::class)->name('webhooks.ameex');
