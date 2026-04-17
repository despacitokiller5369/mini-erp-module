<?php

namespace App\Domain\WorkforcePlanning\Actions;

use App\Domain\IdentityAndAccess\Models\User;
use App\Domain\WorkforcePlanning\Enums\HolidayStatus;
use App\Domain\WorkforcePlanning\Models\Holiday;
use App\Domain\WorkforcePlanning\Models\Shift;
use App\Domain\WorkforcePlanning\Models\ShiftAssignment;
use App\Domain\WorkforcePlanning\Services\OverlapDetectionService;
use Illuminate\Support\Facades\DB;
use Exception;

class AssignShiftAction
{
    public function __construct(
        private OverlapDetectionService $overlapDetectionService
    ) {}

    public function execute(Shift $shift, User $employee, User $manager): ShiftAssignment
    {
        if (!$manager->isManager()) {
            throw new Exception('Only managers can assign shifts.');
        }

        $hasApprovedLeave = Holiday::query()
            ->where('user_id', $employee->id)
            ->where('status', HolidayStatus::Approved->value)
            ->whereDate('start_date', '<=', $shift->date)
            ->whereDate('end_date', '>=', $shift->date)
            ->exists();

        if ($hasApprovedLeave) {
            throw new Exception('Employee is on approved leave for this date.');
        }

        $hasOverlap = ShiftAssignment::query()
            ->where('user_id', $employee->id)
            ->whereHas('shift', function ($query) use ($shift) {
                $query->whereDate('date', $shift->date)
                    ->where('start_time', '<', $shift->end_time)
                    ->where('end_time', '>', $shift->start_time);
            })
            ->exists();

        if ($hasOverlap) {
            throw new Exception('Employee already has an overlapping shift.');
        }

        return DB::transaction(function () use ($shift, $employee) {
            return ShiftAssignment::create([
                'shift_id' => $shift->id,
                'user_id' => $employee->id,
            ]);
        });
    }
}