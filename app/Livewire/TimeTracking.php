<?php

namespace App\Livewire;

use App\Domain\TimeTracking\Actions\ClockInAction;
use App\Domain\TimeTracking\Actions\ClockOutAction;
use App\Domain\TimeTracking\Actions\CreateManualEntryAction;
use App\Domain\TimeTracking\Models\TimeLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Exception;

class TimeTracking extends Component
{
    public string $manualClockIn = '';
    public string $manualClockOut = '';
    public string $manualNote = '';
    public string $errorMessage = '';

    #[Computed]
    public function timeLogs()
    {
        return TimeLog::forUser(Auth::id())
            ->thisWeek()
            ->orderByDesc('clock_in')
            ->get();
    }

    #[Computed]
    public function openLog()
    {
        return TimeLog::forUser(Auth::id())->open()->first();
    }

    #[Computed]
    public function weeklyTotalHours()
    {
        return $this->timeLogs->sum('duration_hours');
    }

    public function clockIn(ClockInAction $action)
    {
        $this->errorMessage = '';
        try {
            $action->execute(Auth::user());
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function clockOut(ClockOutAction $action)
    {
        $this->errorMessage = '';
        try {
            $action->execute(Auth::user());
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function createManualEntry(CreateManualEntryAction $action)
    {
        $this->validate([
            'manualClockIn' => 'required|date',
            'manualClockOut' => 'required|date|after:manualClockIn',
            'manualNote' => 'required|string|min:5',
        ]);

        $this->errorMessage = '';
        try {
            $action->execute(Auth::user(), $this->manualClockIn, $this->manualClockOut, $this->manualNote);
            $this->reset(['manualClockIn', 'manualClockOut', 'manualNote']);
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.time-tracking');
    }
}