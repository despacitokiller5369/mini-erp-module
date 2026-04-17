<?php

namespace App\Domain\TimeTracking\Actions;

use App\Domain\IdentityAndAccess\Models\User;
use App\Domain\TimeTracking\Models\TimeLog;
use Exception;
use Carbon\Carbon;

class ClockOutAction
{
    public function execute(User $user): TimeLog
    {
        $timeLog = TimeLog::forUser($user->id)->open()->first();

        if (!$timeLog) {
            throw new Exception('You are not currently clocked in.');
        }

        $timeLog->update([
            'clock_out' => Carbon::now(),
        ]);

        return $timeLog;
    }
}