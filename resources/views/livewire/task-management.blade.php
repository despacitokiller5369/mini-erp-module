<div x-data="{ taskView: 'mine' }" class="space-y-6">

    @if($errorMessage)
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ $errorMessage }}</div>
    @endif
    @if($successMessage)
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ $successMessage }}</div>
    @endif

    <div class="flex justify-between items-center">
        <div class="flex gap-4 border-b border-gray-200">
            <button @click="taskView = 'mine'"
                :class="taskView === 'mine' ? 'border-b-2 border-blue-500 text-blue-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                class="pb-3 text-sm px-1 transition">
                My Tasks ({{ $this->myTasks->count() }})
            </button>
            @if(Auth::user()->isManager())
                <button @click="taskView = 'team'"
                    :class="taskView === 'team' ? 'border-b-2 border-blue-500 text-blue-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                    class="pb-3 text-sm px-1 transition">
                    All Team Tasks ({{ $this->teamTasks->count() }})
                </button>
            @endif
        </div>

        @if(Auth::user()->isManager())
            <button wire:click="$toggle('showCreateForm')"
                class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 text-sm font-semibold">
                {{ $showCreateForm ? 'Cancel' : '+ New Task' }}
            </button>
        @endif
    </div>

    @if($showCreateForm && Auth::user()->isManager())
        <div class="bg-gray-50 border border-gray-200 p-6 rounded-lg shadow-sm mb-6">
            <h2 class="text-lg font-bold mb-4">Create New Task</h2>
            <form wire:submit="createTask" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-600">Title</label>
                        <input type="text" wire:model="newTaskTitle" class="w-full border rounded p-2">
                        @error('newTaskTitle') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600">Priority</label>
                        <select wire:model="newTaskPriority" class="w-full border rounded p-2">
                            @foreach(\App\Domain\TaskManagement\Enums\TaskPriority::cases() as $priority)
                                <option value="{{ $priority->value }}">{{ ucfirst($priority->value) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600">Description</label>
                        <textarea wire:model="newTaskDescription" rows="2" class="w-full border rounded p-2"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600">Due Date</label>
                        <input type="date" wire:model="newTaskDueDate" class="w-full border rounded p-2">
                        @error('newTaskDueDate') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600">Estimated Hours</label>
                        <input type="number" step="0.5" wire:model="newTaskEstimatedHours"
                            class="w-full border rounded p-2">
                        @error('newTaskEstimatedHours') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-600 mb-1">Assign To (Hold Ctrl/Cmd to select multiple)</label>
                        <select multiple wire:model="newTaskAssignees" class="w-full border rounded p-2 h-24">
                            @foreach($this->allEmployees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->role->value }})</option>
                            @endforeach
                        </select>
                        @error('newTaskAssignees') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex justify-end pt-2">
                    <button type="submit" class="bg-green-600 text-white font-bold py-2 px-6 rounded hover:bg-green-700">
                        Save Task
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div x-show="taskView === 'mine'" x-cloak>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($this->myTasks as $task)
                <div
                    class="bg-white p-5 rounded-lg shadow border border-gray-100 {{ $task->status->value === 'done' ? 'opacity-75 bg-gray-50' : '' }}">
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="text-lg font-bold text-gray-800">{{ $task->title }}</h3>
                        <span
                            class="px-2 py-1 text-xs font-semibold rounded {{ $task->priority->value === 'high' ? 'bg-red-100 text-red-800' : ($task->priority->value === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                            {{ ucfirst($task->priority->value) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 line-clamp-2 mb-4">{{ $task->description }}</p>
                    <div class="flex items-center justify-between text-sm mb-4">
                        <div>
                            <span class="text-gray-500">Due:</span>
                            <span
                                class="{{ $task->due_date->isPast() && $task->status->value !== 'done' ? 'text-red-600 font-bold' : 'text-gray-900 font-medium' }}">
                                {{ $task->due_date->format('M d, Y') }}
                            </span>
                        </div>
                        <select wire:change="updateStatus('{{ $task->id }}', $event.target.value)"
                            class="text-sm border-gray-300 rounded focus:ring-blue-500 py-1">
                            @foreach(\App\Domain\TaskManagement\Enums\TaskStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected($task->status === $status)>
                                    {{ ucfirst($status->value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @php $myAssignment = $task->assignments->first(); @endphp
                    @if($myAssignment)
                        <div class="pt-4 border-t border-gray-100">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>Logged: {{ $myAssignment->logged_hours }}h</span>
                                <span>Est: {{ $task->estimated_hours }}h</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 mb-4">
                                <div class="bg-blue-500 h-1.5 rounded-full"
                                    style="width: {{ min(($myAssignment->logged_hours / max($task->estimated_hours, 1)) * 100, 100) }}%">
                                </div>
                            </div>
                            @if($task->status->value !== 'done')
                                <div class="flex gap-2">
                                    <input type="number" step="0.5" wire:model="hoursToLog" placeholder="Hours"
                                        class="w-24 text-sm border-gray-300 rounded shadow-sm py-1">
                                    <button wire:click="$set('selectedAssignmentId', '{{ $myAssignment->id }}')"
                                        wire:click="logHours"
                                        class="flex-1 bg-gray-800 text-white text-sm font-semibold py-1 px-2 rounded hover:bg-gray-700">
                                        Log Time
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-3 text-center py-12 text-gray-500 bg-white rounded-lg border border-dashed">No tasks
                    assigned to you yet.</div>
            @endforelse
        </div>
    </div>

    @if(Auth::user()->isManager())
        <div x-show="taskView === 'team'" x-cloak>
            <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Task</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned To</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($this->teamTasks as $task)
                            <tr
                                class="{{ $task->due_date->isPast() && $task->status->value !== 'done' ? 'bg-red-50' : 'hover:bg-gray-50' }}">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-900">{{ $task->title }}</div>
                                    <div class="text-xs text-gray-500">{{ ucfirst($task->priority->value) }} priority</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $task->assignments->pluck('user.name')->join(', ') ?: 'Unassigned' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full
                                                                        {{ $task->status->value === 'done' ? 'bg-green-100 text-green-800' : '' }}
                                                                        {{ $task->status->value === 'inprogress' ? 'bg-blue-100 text-blue-800' : '' }}
                                                                        {{ $task->status->value === 'inreview' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                                        {{ $task->status->value === 'todo' ? 'bg-gray-100 text-gray-800' : '' }}">
                                        {{ ucfirst($task->status->value) }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 text-sm {{ $task->due_date->isPast() && $task->status->value !== 'done' ? 'text-red-600 font-bold' : 'text-gray-600' }}">
                                    {{ $task->due_date->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $task->assignments->sum('logged_hours') }}h / {{ $task->estimated_hours }}h
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">No tasks created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>