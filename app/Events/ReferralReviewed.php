<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\EmployeeReferral;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when an employer approves or rejects a referral.
 */
class ReferralReviewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public EmployeeReferral $referral,
        public string $decision // 'approved' or 'rejected'
    ) {}
}
