<?php

namespace App\Livewire;

use App\Domain\WorkforcePlanning\Models\Holiday;
use App\Domain\WorkforcePlanning\Enums\LeaveType;
use App\Domain\WorkforcePlanning\Actions\RequestHolidayAction;
use App\Domain\WorkforcePlanning\Actions\ApproveHolidayAction;
use App\Domain\WorkforcePlanning\Actions\RejectHolidayAction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Exception;

class HolidayManagement extends Component
{
    public string $startDate = '';
    public string $endDate = '';
    public string $leaveType = 'annual';
    public string $reason = '';
    
    public string $managerComment = '';
    public string $errorMessage = '';
    public string $successMessage = '';

    #[Computed]
    public function myHolidays()
    {
        return Holiday::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function pendingTeamRequests()
    {
        if (!Auth::user()->isManager()) {
            return collect();
        }

        return Holiday::with('user')
            ->pending()
            ->where('user_id', '!=', Auth::id())
            ->orderBy('start_date')
            ->get();
    }

    public function submitRequest(RequestHolidayAction $action)
    {
        $this->validate([
            'startDate' => 'required|date|after_or_equal:today',
            'endDate' => 'required|date|after_or_equal:startDate',
            'leaveType' => 'required|string',
            'reason' => 'nullable|string|max:255',
        ]);

        $this->resetMessages();

        try {
            $action->execute(Auth::user(), $this->startDate, $this->endDate, $this->leaveType, $this->reason);
            $this->reset(['startDate', 'endDate', 'reason']);
            $this->successMessage = 'Holiday request submitted successfully.';
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function approveRequest(string $holidayId, ApproveHolidayAction $action)
    {
        $this->resetMessages();
        $holiday = Holiday::findOrFail($holidayId);

        try {
            $action->execute($holiday, Auth::user(), $this->managerComment);
            $this->managerComment = '';
            $this->successMessage = 'Holiday approved successfully.';
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function rejectRequest(string $holidayId, RejectHolidayAction $action)
    {
        $this->resetMessages();
        $holiday = Holiday::findOrFail($holidayId);

        try {
            $action->execute($holiday, Auth::user(), $this->managerComment);
            $this->managerComment = '';
            $this->successMessage = 'Holiday rejected.';
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    private function resetMessages()
    {
        $this->errorMessage = '';
        $this->successMessage = '';
    }

    public function render()
    {
        return view('livewire.holiday-management');
    }
}