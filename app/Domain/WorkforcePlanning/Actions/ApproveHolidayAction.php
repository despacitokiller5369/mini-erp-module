<?php
namespace App\Domain\WorkforcePlanning\Actions;

use App\Domain\IdentityAndAccess\Models\User;
use App\Domain\WorkforcePlanning\Models\Holiday;
use App\Domain\WorkforcePlanning\Enums\HolidayStatus;
use App\Domain\WorkforcePlanning\Enums\LeaveType;
use App\Domain\WorkforcePlanning\Services\LeaveBalanceService;
use App\Domain\WorkforcePlanning\Services\OverlapDetectionService;
use Illuminate\Support\Facades\DB;
use Exception;

class ApproveHolidayAction
{
    public function __construct(
        private LeaveBalanceService $balanceService,
        private OverlapDetectionService $overlapService
    ) {}

    public function execute(Holiday $holiday, User $manager, ?string $comment = null): Holiday
    {
        if (!$manager->isManager()) {
            throw new Exception('Only managers can approve holidays.');
        }

        if ($holiday->user_id === $manager->id) {
            throw new Exception('Managers cannot approve their own holiday requests.');
        }

        if ($holiday->status !== HolidayStatus::Pending) {
            throw new Exception('Only pending requests can be approved.');
        }

        $hasOverlap = $this->overlapService->hasOverlap(
            Holiday::class, 'user_id', $holiday->user_id, 'start_date', 'end_date', 
            $holiday->start_date, $holiday->end_date, $holiday->id, ['status' => HolidayStatus::Approved->value]
        );

        if ($hasOverlap) {
            throw new Exception('Employee already has an approved holiday during this period.');
        }

        return DB::transaction(function () use ($holiday, $manager, $comment) {
            $daysToDeduct = 0;

            if ($holiday->type === LeaveType::Annual) {
                $daysToDeduct = $this->balanceService->calculateBusinessDays($holiday->start_date, $holiday->end_date);

                if (!$this->balanceService->hasEnoughBalance($holiday->user, $daysToDeduct)) {
                    throw new Exception("Insufficient leave balance. Requires {$daysToDeduct} days.");
                }

                $holiday->user->decrement('annual_leave_allowance', $daysToDeduct);
            }

            $holiday->update([
                'status' => HolidayStatus::Approved,
                'approver_id' => $manager->id,
                'manager_comment' => $comment,
            ]);

            return $holiday;
        });
    }
}