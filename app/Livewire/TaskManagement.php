<?php

namespace App\Livewire;

use App\Domain\IdentityAndAccess\Models\User;
use App\Domain\TaskManagement\Models\Task;
use App\Domain\TaskManagement\Enums\TaskStatus;
use App\Domain\TaskManagement\Enums\TaskPriority;
use App\Domain\TaskManagement\Actions\UpdateTaskStatusAction;
use App\Domain\TaskManagement\Actions\LogTaskHoursAction;
use App\Domain\TaskManagement\Actions\CreateTaskAction;
use App\Domain\TaskManagement\Models\TaskAssignment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Exception;

class TaskManagement extends Component
{
    public string $errorMessage = '';
    public string $successMessage = '';
    
    // Log Hours Properties
    public float $hoursToLog = 0;
    public string $selectedAssignmentId = '';

    // Create Task Properties
    public bool $showCreateForm = false;
    public string $newTaskTitle = '';
    public string $newTaskDescription = '';
    public string $newTaskPriority = 'medium';
    public string $newTaskDueDate = '';
    public float $newTaskEstimatedHours = 0;
    public array $newTaskAssignees = [];

    #[Computed]
    public function allEmployees()
    {
        // Get all users to populate the multi-select dropdown
        return User::orderBy('name')->get();
    }

    #[Computed]
    public function myTasks()
    {
        return Task::whereHas('assignments', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->with(['assignments' => function ($query) {
                $query->where('user_id', Auth::id());
            }])
            ->orderBy('due_date')
            ->get();
    }

    #[Computed]
    public function teamTasks()
    {
        if (!Auth::user()->isManager()) {
            return collect();
        }
        return Task::with(['assignments.user'])->orderBy('due_date')->get();
    }

    public function createTask(CreateTaskAction $action)
    {
        $this->validate([
            'newTaskTitle' => 'required|string|max:255',
            'newTaskDescription' => 'nullable|string',
            'newTaskPriority' => 'required|in:low,medium,high',
            'newTaskDueDate' => 'required|date|after_or_equal:today',
            'newTaskEstimatedHours' => 'required|numeric|min:0.5',
            'newTaskAssignees' => 'required|array|min:1',
            'newTaskAssignees.*' => 'exists:users,id',
        ]);

        $this->errorMessage = '';
        $this->successMessage = '';

        try {
            $action->execute([
                'title' => $this->newTaskTitle,
                'description' => $this->newTaskDescription,
                'priority' => $this->newTaskPriority,
                'due_date' => $this->newTaskDueDate,
                'estimated_hours' => $this->newTaskEstimatedHours,
            ], $this->newTaskAssignees);

            // Reset form
            $this->reset([
                'newTaskTitle', 'newTaskDescription', 'newTaskPriority', 
                'newTaskDueDate', 'newTaskEstimatedHours', 'newTaskAssignees', 'showCreateForm'
            ]);
            
            $this->successMessage = 'Task created successfully!';
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function updateStatus(string $taskId, string $newStatusValue, UpdateTaskStatusAction $action)
    {
        $this->errorMessage = '';
        try {
            $action->execute(Task::findOrFail($taskId), TaskStatus::from($newStatusValue));
        } catch (Exception $e) {
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
        try {
            $action->execute(TaskAssignment::findOrFail($this->selectedAssignmentId), $this->hoursToLog);
            $this->hoursToLog = 0;
            $this->selectedAssignmentId = '';
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.task-management');
    }
}