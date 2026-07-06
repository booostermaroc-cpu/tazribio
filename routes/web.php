<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/orders/{order}/delivery-note', [DocumentController::class, 'deliveryNote'])
        ->name('documents.delivery-note');
    Route::get('/admin/orders/{order}/delivery-note/legacy', \App\Http\Controllers\DeliveryNoteController::class)
        ->name('orders.delivery-note');
    Route::get('/admin/return-bons/{returnBon}/pdf', [DocumentController::class, 'returnBon'])
        ->name('documents.return-bon');
    Route::get('/admin/invoices/{invoice}/pdf', [DocumentController::class, 'invoice'])
        ->name('documents.invoice');
    Route::get('/admin/shipments/{shipment}/ameex-delivery-note', [\App\Http\Controllers\AmeexController::class, 'deliveryNote'])
        ->name('ameex.delivery-note');
    Route::get('/admin/pickup-requests/{pickupRequest}/pdf', [DocumentController::class, 'pickupRequest'])
        ->name('documents.pickup-request');
});

Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['fr', 'ar'], true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('locale.switch');

Route::get('/avis/{token}', [\App\Http\Controllers\OrderReviewController::class, 'show'])
    ->name('reviews.show');
Route::post('/avis/{token}', [\App\Http\Controllers\OrderReviewController::class, 'store'])
    ->name('reviews.store');
