<?php

namespace App\Domain\TimeTracking\Actions;

use App\Domain\IdentityAndAccess\Models\User;
use App\Domain\TimeTracking\Models\TimeLog;
use Exception;

class CreateManualEntryAction
{
    public function execute(User $user, string $clockIn, string $clockOut, string $note): TimeLog
    {
        if (empty(trim($note))) {
            throw new Exception('A note is required for manual time entries.');
        }

        return TimeLog::create([
            'user_id' => $user->id,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'note' => $note,
        ]);
    }
}