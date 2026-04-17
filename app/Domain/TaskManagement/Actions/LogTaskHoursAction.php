<?php

namespace App\Domain\TaskManagement\Actions;

use App\Domain\TaskManagement\Models\TaskAssignment;
use Exception;

class LogTaskHoursAction
{
    public function execute(TaskAssignment $assignment, float $hoursToAdd): TaskAssignment
    {
        if ($hoursToAdd <= 0) {
            throw new Exception("Logged hours must be greater than zero.");
        }

        $assignment->increment('logged_hours', $hoursToAdd);

        return $assignment;
    }
}