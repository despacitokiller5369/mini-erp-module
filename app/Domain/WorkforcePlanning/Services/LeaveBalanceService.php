<?php
namespace App\Domain\WorkforcePlanning\Services;

use Carbon\Carbon;
use App\Domain\IdentityAndAccess\Models\User;

class LeaveBalanceService
{
    public function calculateBusinessDays(Carbon $startDate, Carbon $endDate): int
    {
        return $startDate->diffInWeekdays($endDate->copy()->addDay());
    }

    public function hasEnoughBalance(User $user, int $requestedDays): bool
    {
        return $user->annual_leave_allowance >= $requestedDays;
    }
}