<div class="max-w-6xl mx-auto p-6 space-y-8">
    <h1 class="text-2xl font-bold text-gray-800">My Tasks</h1>

    @if($errorMessage)
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($this->myTasks as $task)
            <div class="bg-white p-5 rounded-lg shadow space-y-4 {{ $task->status->value === 'done' ? 'opacity-75' : '' }}">
                <div class="flex justify-between items-start">
                    <h3 class="text-lg font-bold">{{ $task->title }}</h3>
                    <span
                        class="px-2 py-1 text-xs font-semibold rounded 
                                    {{ $task->priority->value === 'high' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ ucfirst($task->priority->value) }}
                    </span>
                </div>

                <p class="text-sm text-gray-600 line-clamp-2">{{ $task->description }}</p>

                <div class="text-sm">
                    <span class="font-semibold text-gray-700">Due:</span>
                    <span
                        class="{{ $task->due_date->isPast() && $task->status->value !== 'done' ? 'text-red-600 font-bold' : 'text-gray-600' }}">
                        {{ $task->due_date->format('M d, Y') }}
                    </span>
                </div>

                <!-- Status Update -->
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-700">Status:</span>
                    <select wire:change="updateStatus('{{ $task->id }}', $event.target.value)"
                        class="text-sm border-gray-300 rounded shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        @foreach(\App\Domain\TaskManagement\Enums\TaskStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected($task->status === $status)>
                                {{ ucfirst($status->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Progress & Logged Hours -->
                @php $myAssignment = $task->assignments->first(); @endphp
                @if($myAssignment)
                    <div class="pt-4 border-t border-gray-100">
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Logged: {{ $task->total_logged_hours }}h</span>
                            <span>Est: {{ $task->estimated_hours }}h</span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                            <div class="bg-blue-600 h-2 rounded-full"
                                style="width: {{ min(($task->total_logged_hours / max($task->estimated_hours, 1)) * 100, 100) }}%">
                            </div>
                        </div>

                        <!-- Log Time Form -->
                        @if($task->status->value !== 'done')
                            <form wire:submit="logHours" class="flex gap-2">
                                <input type="hidden" wire:model="selectedAssignmentId">
                                <input type="number" step="0.5" wire:model="hoursToLog" placeholder="Hours"
                                    class="w-20 text-sm border-gray-300 rounded shadow-sm">
                                <button type="submit" wire:click="$set('selectedAssignmentId', '{{ $myAssignment->id }}')"
                                    class="flex-1 bg-gray-800 text-white text-sm font-bold py-1 px-2 rounded hover:bg-gray-700">
                                    Log Time
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>