<?php

namespace App\Livewire;

use App\Domain\IdentityAndAccess\Models\User;
use App\Domain\WorkforcePlanning\Actions\AssignShiftAction;
use App\Domain\WorkforcePlanning\Enums\ShiftLabel;
use App\Domain\WorkforcePlanning\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Carbon\Carbon;
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

    public int $weekOffset = 0;

    public function previousWeek()
    {
        $this->weekOffset--;
    }

    public function nextWeek()
    {
        $this->weekOffset++;
    }

    public function currentWeek()
    {
        $this->weekOffset = 0;
    }

    #[Computed]
    public function weekDates()
    {
        $startOfWeek = now()->startOfWeek()->addWeeks($this->weekOffset);

        return collect(range(0, 6))->map(function ($days) use ($startOfWeek) {
            return $startOfWeek->copy()->addDays($days);
        });
    }

    #[Computed]
    public function employees()
    {
        return User::query()
            ->where('role', 'employee')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function calendarData()
    {
        $startDate = $this->weekDates->first()->format('Y-m-d');
        $endDate = $this->weekDates->last()->format('Y-m-d');

        $shifts = Shift::query()
            ->with('assignments.user')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('start_time')
            ->get();

        $grid = [];
        foreach ($this->employees as $employee) {
            foreach ($this->weekDates as $date) {
                $grid[$employee->id][$date->format('Y-m-d')] = [];
            }
        }

        foreach ($shifts as $shift) {
            foreach ($shift->assignments as $assignment) {
                if (isset($grid[$assignment->user_id])) {
                    $grid[$assignment->user_id][$shift->date->format('Y-m-d')][] = $shift;
                }
            }
        }

        return $grid;
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

            $shiftDate = Carbon::parse($shift->date);
            $this->weekOffset = now()->startOfWeek()->diffInWeeks($shiftDate->startOfWeek(), false);

        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.shift-scheduling');
    }
}
