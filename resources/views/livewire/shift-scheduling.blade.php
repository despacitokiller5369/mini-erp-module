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

            <form wire:submit="createShift" class="grid grid-cols-1 gap-4 md:grid-cols-2">
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

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm text-gray-600">Assign employees</label>
                    <select multiple wire:model="assigneeIds" class="h-32 w-full rounded border-gray-300">
                        @foreach($this->employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                    @error('assigneeIds') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <button type="submit"
                        class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">
                        Save shift
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="rounded-xl bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-semibold text-gray-800">Upcoming Shifts</h3>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Label</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Employees</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($this->weeklyShifts as $shift)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $shift->date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $shift->start_time }} - {{ $shift->end_time }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ ucfirst($shift->label->value) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $shift->assignments->pluck('user.name')->join(', ') ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No shifts yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>