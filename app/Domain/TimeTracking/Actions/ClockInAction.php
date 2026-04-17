<?php

namespace App\Domain\TimeTracking\Actions;

use App\Domain\IdentityAndAccess\Models\User;
use App\Domain\TimeTracking\Models\TimeLog;
use Exception;
use Carbon\Carbon;

class ClockInAction
{
    public function execute(User $user): TimeLog
    {
        if (TimeLog::forUser($user->id)->open()->exists()) {
            throw new Exception('You are already clocked in. Please clock out first.');
        }

        return TimeLog::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now(),
        ]);
    }
}