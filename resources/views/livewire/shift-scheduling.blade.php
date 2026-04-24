<div class="space-y-6">
    @if($errorMessage)
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">
            {{ $errorMessage }}
        </div>
    @endif

    @if($successMessage)
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700">
            {{ $successMessage }}
        </div>
    @endif

    @if(Auth::user()->isManager())
        <div class="rounded-xl bg-white p-6 shadow">
            <h3 class="mb-4 text-lg font-semibold text-gray-800">Create Shift</h3>

            <form wire:submit="createShift" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label class="mb-1 block text-sm text-gray-600">Date</label>
                    <input type="date" wire:model="date" class="w-full rounded border-gray-300">
                    @error('date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm text-gray-600">Label</label>
                    <select wire:model="label" class="w-full rounded border-gray-300">
                        @foreach(\App\Domain\WorkforcePlanning\Enums\ShiftLabel::cases() as $shiftLabel)
                            <option value="{{ $shiftLabel->value }}">{{ ucfirst($shiftLabel->value) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-gray-600">Start time</label>
                    <input type="time" wire:model="startTime" class="w-full rounded border-gray-300">
                    @error('startTime') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm text-gray-600">End time</label>
                    <input type="time" wire:model="endTime" class="w-full rounded border-gray-300">
                    @error('endTime') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="lg:col-span-1">
                    <label class="mb-1 block text-sm text-gray-600">Assign employees</label>
                    <select multiple wire:model="assigneeIds" class="h-20 w-full rounded border-gray-300">
                        @foreach($this->employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                    @error('assigneeIds') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2 lg:col-span-5">
                    <button type="submit"
                        class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">
                        Save shift
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="rounded-xl bg-white shadow overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-800">Weekly Shift Calendar</h3>
            <div class="flex space-x-2">
                <button wire:click="previousWeek"
                    class="px-3 py-1 text-sm bg-white border border-gray-300 rounded hover:bg-gray-100">&larr;
                    Prev</button>
                <button wire:click="currentWeek"
                    class="px-3 py-1 text-sm bg-white border border-gray-300 rounded hover:bg-gray-100">Current
                    Week</button>
                <button wire:click="nextWeek"
                    class="px-3 py-1 text-sm bg-white border border-gray-300 rounded hover:bg-gray-100">Next
                    &rarr;</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border-collapse">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 border-r w-48 border-gray-200 sticky left-0 bg-gray-50 z-10">
                            Employee</th>
                        @foreach($this->weekDates as $date)
                            <th
                                class="px-2 py-3 text-center text-xs font-medium text-gray-500 border-r border-gray-200 min-w-[120px] {{ $date->isToday() ? 'bg-blue-50 text-blue-700' : '' }}">
                                <div class="uppercase">{{ $date->format('l') }}</div>
                                <div class="text-lg font-bold">{{ $date->format('M d') }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($this->employees as $employee)
                        <tr>
                            <td
                                class="px-4 py-4 text-sm font-medium text-gray-900 border-r border-gray-200 sticky left-0 bg-white z-10 shadow-[1px_0_0_0_#e5e7eb]">
                                {{ $employee->name }}
                            </td>
                            @foreach($this->weekDates as $date)
                                                @php
                                                    $dateStr = $date->format('Y-m-d');
                                                    $shifts = $this->calendarData[$employee->id][$dateStr] ?? [];
                                                @endphp
                                 <td
                                                    class="p-2 align-top border-r border-gray-200 {{ $date->isToday() ? 'bg-blue-50/30' : '' }}">
                                                    @if(!empty($shifts))
                                                        <div class="space-y-2">
                                                            @foreach($shifts as $shift)
                                                                <div class="p-2 text-xs rounded border 
                                                                                            {{ $shift->label->value === 'morning' ? 'bg-orange-50 border-orange-200 text-orange-800' : '' }}
                                                                                            {{ $shift->label->value === 'afternoon' ? 'bg-blue-50 border-blue-200 text-blue-800' : '' }}
                                                                                            {{ $shift->label->value === 'night' ? 'bg-indigo-50 border-indigo-200 text-indigo-800' : '' }}
                                                                                        ">
                                                                    <div class="font-bold capitalize">{{ $shift->label->value }}</div>
                                                                    <div>{{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} -
                                                                        {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}</div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">No employees found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>