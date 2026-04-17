<?php

namespace App\Livewire;

use App\Domain\TaskManagement\Models\Task;
use App\Domain\TaskManagement\Enums\TaskStatus;
use App\Domain\TaskManagement\Actions\UpdateTaskStatusAction;
use App\Domain\TaskManagement\Actions\LogTaskHoursAction;
use App\Domain\TaskManagement\Models\TaskAssignment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TaskManagement extends Component
{
    public string $errorMessage = '';
    public float $hoursToLog = 0;
    public string $selectedAssignmentId = '';

    #[Computed]
    public function myTasks()
    {
        return Task::whereHas('assignments', function ($query) {
            $query->where('user_id', Auth::id());
        })->with(['assignments' => function ($query) {
            $query->where('user_id', Auth::id());
        }])->orderBy('due_date')->get();
    }

    public function updateStatus(string $taskId, string $newStatusValue, UpdateTaskStatusAction $action)
    {
        $this->errorMessage = '';
        $task = Task::findOrFail($taskId);
        $newStatus = TaskStatus::from($newStatusValue);

        try {
            $action->execute($task, $newStatus);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function logHours(LogTaskHoursAction $action)
    {
        $this->validate([
            'hoursToLog' => 'required|numeric|min:0.1',
            'selectedAssignmentId' => 'required|string',
        ]);

        $this->errorMessage = '';
        $assignment = TaskAssignment::findOrFail($this->selectedAssignmentId);

        try {
            $action->execute($assignment, $this->hoursToLog);
            $this->hoursToLog = 0;
            $this->selectedAssignmentId = '';
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.task-management');
    }
}