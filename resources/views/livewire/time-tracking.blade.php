<div class="max-w-4xl mx-auto p-6 space-y-8">
            <div class="flex justify-between items-center bg-white p-6 rounded-lg shadow">
                <h1 class="text-2xl font-bold text-gray-800">Time Tracking</h1>
                <div class="text-right">
                    <p class="text-sm text-gray-500">This Week's Total</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $this->weeklyTotalHours }} hrs</p>
                </div>
            </div>

            @if($errorMessage)
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ $errorMessage }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-lg shadow text-center space-y-4">
                    <h2 class="text-lg font-semibold text-gray-700">Current Status</h2>
                    
                    @if($this->openLog)
                        <div class="animate-pulse text-green-600 font-medium">
                            Clocked in since {{ $this->openLog->clock_in->format('H:i') }}
                        </div>
                        <button wire:click="clockOut" class="w-full bg-red-600 text-white font-bold py-3 px-4 rounded hover:bg-red-700 transition">
                            Clock Out
                        </button>
                    @else
                        <div class="text-gray-500">You are currently clocked out.</div>
                        <button wire:click="clockIn" class="w-full bg-green-600 text-white font-bold py-3 px-4 rounded hover:bg-green-700 transition">
                            Clock In
                        </button>
                    @endif
                </div>

                <div class="bg-white p-6 rounded-lg shadow space-y-4">
                    <h2 class="text-lg font-semibold text-gray-700">Add Manual Entry</h2>
                    <form wire:submit="createManualEntry" class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm text-gray-600">Start Time</label>
                                <input type="datetime-local" wire:model="manualClockIn" class="w-full border rounded p-2">
                                @error('manualClockIn') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600">End Time</label>
                                <input type="datetime-local" wire:model="manualClockOut" class="w-full border rounded p-2">
                                @error('manualClockOut') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600">Reason / Note</label>
                            <input type="text" wire:model="manualNote" placeholder="Forgot to clock out..." class="w-full border rounded p-2">
                            @error('manualNote') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition">
                            Save Entry
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clock In</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Clock Out</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Note</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($this->timeLogs as $log)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $log->clock_in->format('M d, Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $log->clock_in->format('H:i') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $log->clock_out ? $log->clock_out->format('H:i') : 'Active' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">{{ $log->duration_hours }}h</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $log->note ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>