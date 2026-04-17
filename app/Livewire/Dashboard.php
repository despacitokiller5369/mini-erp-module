<?php

namespace App\Livewire;

use App\Domain\TaskManagement\Models\Task;
use App\Domain\TimeTracking\Models\TimeLog;
use App\Domain\WorkforcePlanning\Models\Holiday;
use App\Domain\WorkforcePlanning\Models\ShiftAssignment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    public int $expectedHours = 40;
    
    // We will use this to toggle between tabs on the dashboard
    public string $currentTab = 'time';

    public function setTab(string $tab)
    {
        $this->currentTab = $tab;
    }

    #[Computed]
    public function hoursLoggedThisWeek(): float
    {
        $logs = TimeLog::forUser(Auth::id())->thisWeek()->get();
        return round($logs->sum('duration_hours'), 2);
    }

    #[Computed]
    public function overdueTasks()
    {
        return Task::whereHas('assignments', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->overdue()
            ->get();
    }
    
    #[Computed]
    public function pendingHolidays()
    {
        if (!Auth::user()->isManager()) {
            return collect();
        }
        return Holiday::pending()
            ->where('user_id', '!=', Auth::id())
            ->get();
    }

    #[Computed]
    public function upcomingShifts()
    {
        // Get the user's upcoming shifts (from today onwards)
        return ShiftAssignment::where('user_id', Auth::id())
            ->whereHas('shift', function ($query) {
                $query->whereDate('date', '>=', now());
            })
            ->count();
    }

    public function render()
    {
        return <<<'HTML'
        <div class="max-w-7xl mx-auto p-6 space-y-6">
            
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-800">Welcome back, {{ Auth::user()->name }}</h1>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Weekly Hours Card -->
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500 cursor-pointer hover:bg-gray-50 transition" wire:click="setTab('time')">
                    <h3 class="text-gray-500 text-sm font-medium uppercase tracking-wider">Hours This Week</h3>
                    <div class="mt-2 flex items-baseline">
                        <span class="text-3xl font-extrabold text-gray-900">{{ $this->hoursLoggedThisWeek }}</span>
                        <span class="ml-1 text-xl font-semibold text-gray-500">/ {{ $expectedHours }}h</span>
                    </div>
                </div>

                <!-- Overdue Tasks Card -->
                <div class="bg-white p-6 rounded-lg shadow border-l-4 {{ $this->overdueTasks->count() > 0 ? 'border-red-500' : 'border-green-500' }} cursor-pointer hover:bg-gray-50 transition" wire:click="setTab('tasks')">
                    <h3 class="text-gray-500 text-sm font-medium uppercase tracking-wider">Overdue Tasks</h3>
                    <div class="mt-2">
                        <span class="text-3xl font-extrabold text-gray-900">{{ $this->overdueTasks->count() }}</span>
                    </div>
                </div>
                
                <!-- Pending Holidays Card -->
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-yellow-500 cursor-pointer hover:bg-gray-50 transition" wire:click="setTab('holidays')">
                    <h3 class="text-gray-500 text-sm font-medium uppercase tracking-wider">Pending Holidays</h3>
                    <div class="mt-2">
                        @if(Auth::user()->isManager())
                            <span class="text-3xl font-extrabold text-gray-900">{{ $this->pendingHolidays->count() }}</span>
                            <span class="ml-1 text-sm font-semibold text-gray-500">to review</span>
                        @else
                            <span class="text-sm italic text-gray-500">N/A (Managers)</span>
                        @endif
                    </div>
                </div>

                <!-- Upcoming Shifts Card -->
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500 cursor-pointer hover:bg-gray-50 transition" wire:click="setTab('shifts')">
                    <h3 class="text-gray-500 text-sm font-medium uppercase tracking-wider">Upcoming Shifts</h3>
                    <div class="mt-2">
                        <span class="text-3xl font-extrabold text-gray-900">{{ $this->upcomingShifts }}</span>
                        <span class="ml-1 text-sm font-semibold text-gray-500">assigned</span>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 overflow-x-auto">
                    <button wire:click="setTab('time')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $currentTab === 'time' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Time Tracking
                    </button>
                    <button wire:click="setTab('tasks')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $currentTab === 'tasks' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Task Management
                    </button>
                    <button wire:click="setTab('holidays')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $currentTab === 'holidays' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Holiday Requests
                    </button>
                    <button wire:click="setTab('shifts')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $currentTab === 'shifts' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Shift Scheduling
                    </button>
                </nav>
            </div>

            <!-- Dynamic Content Area -->
            <div class="mt-6">
                @if($currentTab === 'time')
                    <livewire:time-tracking />
                @elseif($currentTab === 'tasks')
                    <livewire:task-management />
                @elseif($currentTab === 'holidays')
                    <livewire:holiday-management />
                @elseif($currentTab === 'shifts')
                    <livewire:shift-scheduling />
                @endif
            </div>

        </div>
        HTML;
    }
}