<?php

namespace App\Domain\TaskManagement\Actions;

use App\Domain\TaskManagement\Models\Task;
use App\Domain\TaskManagement\Models\TaskAssignment;
use Illuminate\Support\Facades\DB;

class CreateTaskAction
{
    public function execute(array $taskData, array $assigneeIds): Task
    {
        return DB::transaction(function () use ($taskData, $assigneeIds) {
            $task = Task::create([
                'title' => $taskData['title'],
                'description' => $taskData['description'],
                'priority' => $taskData['priority'],
                'due_date' => $taskData['due_date'],
                'estimated_hours' => $taskData['estimated_hours'],
                'status' => 'todo',
            ]);

            foreach ($assigneeIds as $userId) {
                TaskAssignment::create([
                    'task_id' => $task->id,
                    'user_id' => $userId,
                ]);
            }

            return $task;
        });
    }
}