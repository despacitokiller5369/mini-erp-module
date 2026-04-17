<?php

namespace App\Livewire;

use App\Domain\IdentityAndAccess\Models\User;
use App\Domain\WorkforcePlanning\Actions\AssignShiftAction;
use App\Domain\WorkforcePlanning\Enums\ShiftLabel;
use App\Domain\WorkforcePlanning\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Exception;

class ShiftScheduling extends Component
{
    public string $date = '';
    public string $startTime = '';
    public string $endTime = '';
    public string $label = 'morning';
    public array $assigneeIds = [];
    public string $errorMessage = '';
    public string $successMessage = '';

    #[Computed]
    public function employees()
    {
        return User::query()
            ->where('role', 'employee')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function weeklyShifts()
    {
        return Shift::query()
            ->with('assignments.user')
            ->upcoming()
            ->take(20)
            ->get();
    }

    public function createShift(AssignShiftAction $assignShiftAction): void
    {
        $this->validate([
            'date' => ['required', 'date'],
            'startTime' => ['required'],
            'endTime' => ['required', 'after:startTime'],
            'label' => ['required', 'in:morning,afternoon,night'],
            'assigneeIds' => ['required', 'array', 'min:1'],
            'assigneeIds.*' => ['exists:users,id'],
        ]);

        $this->errorMessage = '';
        $this->successMessage = '';

        try {
            $shift = Shift::create([
                'date' => $this->date,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'label' => $this->label,
            ]);

            foreach ($this->assigneeIds as $userId) {
                $employee = User::findOrFail($userId);
                $assignShiftAction->execute($shift, $employee, Auth::user());
            }

            $this->reset(['date', 'startTime', 'endTime', 'label', 'assigneeIds']);
            $this->label = ShiftLabel::Morning->value;
            $this->successMessage = 'Shift created successfully.';
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.shift-scheduling');
    }
}