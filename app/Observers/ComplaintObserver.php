<?php

namespace App\Observers;

use App\Models\Complaint;
use App\Services\NotificationService;

class ComplaintObserver
{
    public function __construct(
        protected NotificationService $notificationService,
    ) {}

    public function created(Complaint $complaint): void
    {
        $this->notificationService->complaintCreated($complaint);
    }
}
