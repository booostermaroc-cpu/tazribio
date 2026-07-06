<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentPlanningStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\PaymentPlanning;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected NotificationService $notificationService,
    ) {}

    public function markInvoicePaid(Invoice $invoice): Invoice
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            return $invoice;
        }

        return DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status' => InvoiceStatus::Paid,
                'paid_at' => now(),
            ]);

            $invoice->order?->update([
                'payment_status' => PaymentStatus::Paid,
            ]);

            $this->notificationService->paymentReceived($invoice->fresh());

            return $invoice->fresh();
        });
    }

    public function markPaymentPlanningReceived(PaymentPlanning $planning): PaymentPlanning
    {
        $planning->update([
            'status' => PaymentPlanningStatus::Received,
            'received_at' => now(),
        ]);

        return $planning->fresh();
    }
}
