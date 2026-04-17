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
        
        return Holiday::query()
            ->where('status', \App\Domain\WorkforcePlanning\Enums\HolidayStatus::Pending->value)
            ->where('user_id', '!=', Auth::id())
            ->with('user')
            ->orderBy('created_at')
            ->get();
    }

    #[Computed]
    public function upcomingShifts()
    {
        return ShiftAssignment::where('user_id', Auth::id())
            ->whereHas('shift', function ($query) {
                $query->whereBetween('date', [now()->format('Y-m-d'), now()->addDays(7)->format('Y-m-d')]);
            })
            ->with('shift')
            ->get()
            ->sortBy(function ($assignment) {
                return $assignment->shift->date->timestamp;
        });
    }

    public function render()
    {
        return <<<'HTML'
        <div class="max-w-7xl mx-auto p-6 space-y-6">
            
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-bold text-gray-800">Welcome back, {{ Auth::user()->name }}</h1>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full 
                        {{ Auth::user()->role === 'admin' ? 'bg-red-100 text-red-800' : '' }}
                        {{ Auth::user()->role === 'manager' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ Auth::user()->role === 'employee' ? 'bg-gray-100 text-gray-800' : '' }}">
                        {{ ucfirst(Auth::user()->role->value) }}
                    </span>
                </div>
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
                    
                    @if(Auth::user()->isManager())
                        <div class="mt-2">
                            <div class="flex items-baseline gap-2 mb-2">
                                <span class="text-3xl font-extrabold text-gray-900">{{ $this->pendingHolidays->count() }}</span>
                                <span class="text-sm font-semibold text-gray-500">to review</span>
                            </div>
                            
                            @if($this->pendingHolidays->count() > 0)
                                <ul class="space-y-1">
                                    @foreach($this->pendingHolidays->take(3) as $holiday)
                                        <li class="text-xs text-gray-600 flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-yellow-400 inline-block"></span>
                                            <span class="font-medium truncate max-w-[80px]">{{ $holiday->user->name }}</span>
                                            <span class="text-gray-500 whitespace-nowrap">
                                                ({{ $holiday->start_date->format('M d') }} - {{ $holiday->end_date->format('M d') }})
                                            </span>
                                        </li>
                                    @endforeach
                                    @if($this->pendingHolidays->count() > 3)
                                        <li class="text-xs text-yellow-600 font-medium">+ {{ $this->pendingHolidays->count() - 3 }} more...</li>
                                    @endif
                                </ul>
                            @endif
                        </div>
                    @else
                        <div class="mt-2 text-sm italic text-gray-500">
                            N/A (Managers only)
                        </div>
                    @endif
                </div>

                <!-- Upcoming Shifts Card -->
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500 cursor-pointer hover:bg-gray-50 transition" wire:click="setTab('shifts')">
                    <h3 class="text-gray-500 text-sm font-medium uppercase tracking-wider">Shifts (Next 7 Days)</h3>
                    <div class="mt-2">
                        <div class="flex items-baseline gap-2 mb-2">
                            <span class="text-3xl font-extrabold text-gray-900">{{ $this->upcomingShifts->count() }}</span>
                            <span class="text-sm font-semibold text-gray-500">assigned</span>
                        </div>
                        
                        @if($this->upcomingShifts->count() > 0)
                            <ul class="space-y-1">
                                @foreach($this->upcomingShifts->take(3) as $assignment)
                                    <li class="text-xs text-gray-600 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-400 inline-block"></span>
                                        <span class="font-medium">{{ $assignment->shift->date->format('D, M d') }}</span>
                                        <span class="text-gray-500">({{ ucfirst($assignment->shift->label->value) }})</span>
                                    </li>
                                @endforeach
                                @if($this->upcomingShifts->count() > 3)
                                    <li class="text-xs text-purple-600 font-medium">+ {{ $this->upcomingShifts->count() - 3 }} more...</li>
                                @endif
                            </ul>
                        @else
                            <span class="text-sm italic text-gray-500">No shifts scheduled.</span>
                        @endif
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