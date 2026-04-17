<?php
namespace App\Domain\WorkforcePlanning\Actions;

use App\Domain\IdentityAndAccess\Models\User;
use App\Domain\WorkforcePlanning\Models\Holiday;
use App\Domain\WorkforcePlanning\Enums\HolidayStatus;
use Exception;

class RejectHolidayAction
{
    public function execute(Holiday $holiday, User $manager, ?string $comment = null): Holiday
    {
        if (!$manager->isManager()) {
            throw new Exception('Only managers can reject holidays.');
        }

        if ($holiday->status !== HolidayStatus::Pending) {
            throw new Exception('Only pending requests can be rejected.');
        }

        $holiday->update([
            'status' => HolidayStatus::Rejected,
            'approver_id' => $manager->id,
            'manager_comment' => $comment,
        ]);

        return $holiday;
    }
}