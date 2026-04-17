<?php

namespace App\Domain\TaskManagement\Actions;

use App\Domain\TaskManagement\Models\Task;
use App\Domain\TaskManagement\Enums\TaskStatus;
use Exception;

class UpdateTaskStatusAction
{
    public function execute(Task $task, TaskStatus $newStatus): Task
    {
        if (!$task->status->canTransitionTo($newStatus)) {
            throw new Exception("Cannot transition task from {$task->status->value} to {$newStatus->value}.");
        }

        $task->update(['status' => $newStatus]);

        return $task;
    }
}