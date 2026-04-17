<?php
namespace App\Domain\WorkforcePlanning\Actions;

use App\Domain\IdentityAndAccess\Models\User;
use App\Domain\WorkforcePlanning\Models\Holiday;
use App\Domain\WorkforcePlanning\Enums\HolidayStatus;
use App\Domain\WorkforcePlanning\Services\OverlapDetectionService;
use Exception;

class RequestHolidayAction
{
    public function __construct(private OverlapDetectionService $overlapService) {}

    public function execute(User $user, string $startDate, string $endDate, string $type, ?string $reason): Holiday
    {
        $hasOverlap = $this->overlapService->hasOverlap(
            modelClass: Holiday::class,
            userColumn: 'user_id',
            userId: $user->id,
            startColumn: 'start_date',
            endColumn: 'end_date',
            newStart: $startDate,
            newEnd: $endDate,
            additionalConditions: ['status' => HolidayStatus::Approved->value]
        );

        if ($hasOverlap) {
            throw new Exception('This request overlaps with an already approved holiday.');
        }

        return Holiday::create([
            'user_id' => $user->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'type' => $type,
            'reason' => $reason,
        ]);
    }
}