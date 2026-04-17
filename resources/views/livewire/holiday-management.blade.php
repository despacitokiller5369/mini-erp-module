<div class="max-w-7xl mx-auto p-6 space-y-8">
    <h1 class="text-3xl font-bold text-gray-800">Holiday Management</h1>

    @if($errorMessage)
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ $errorMessage }}</div>
    @endif
    @if($successMessage)
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ $successMessage }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="space-y-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">My Leave Balance</h2>
                <div class="text-4xl font-bold text-blue-600">
                    {{ Auth::user()->annual_leave_allowance }} <span class="text-lg text-gray-500 font-normal">days
                        remaining</span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">Request Time Off</h2>
                <form wire:submit="submitRequest" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-600">Start Date</label>
                            <input type="date" wire:model="startDate" class="w-full border rounded p-2">
                            @error('startDate') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600">End Date</label>
                            <input type="date" wire:model="endDate" class="w-full border rounded p-2">
                            @error('endDate') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600">Leave Type</label>
                        <select wire:model="leaveType" class="w-full border rounded p-2">
                            @foreach(\App\Domain\WorkforcePlanning\Enums\LeaveType::cases() as $type)
                                <option value="{{ $type->value }}">{{ ucfirst($type->value) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-600">Reason (Optional)</label>
                        <textarea wire:model="reason" class="w-full border rounded p-2" rows="2"></textarea>
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700">
                        Submit Request
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-8">

            @if(Auth::user()->isManager())
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-yellow-500">
                    <h2 class="text-xl font-semibold mb-4">Team Requests Pending Approval</h2>

                    @if($this->pendingTeamRequests->isEmpty())
                        <p class="text-gray-500">No pending requests to review.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($this->pendingTeamRequests as $request)
                                <div class="border rounded p-4 bg-gray-50">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="font-bold">{{ $request->user->name }}</span> requested <span
                                                class="font-semibold">{{ ucfirst($request->type->value) }}</span> Leave
                                            <div class="text-sm text-gray-600 mt-1">
                                                {{ $request->start_date->format('M d, Y') }} to
                                                {{ $request->end_date->format('M d, Y') }}
                                            </div>
                                            @if($request->reason)
                                                <div class="text-sm italic mt-2">"{{ $request->reason }}"</div>
                                            @endif
                                        </div>
                                        <div class="space-y-2 text-right">
                                            <input type="text" wire:model="managerComment" placeholder="Optional comment..."
                                                class="text-sm border rounded p-1 w-48 block">
                                            <div class="flex gap-2 justify-end">
                                                <button wire:click="approveRequest('{{ $request->id }}')"
                                                    class="bg-green-600 text-white text-sm px-3 py-1 rounded hover:bg-green-700">Approve</button>
                                                <button wire:click="rejectRequest('{{ $request->id }}')"
                                                    class="bg-red-600 text-white text-sm px-3 py-1 rounded hover:bg-red-700">Reject</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-4">My Request History</h2>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Manager Note
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($this->myHolidays as $holiday)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $holiday->start_date->format('M d') }} - {{ $holiday->end_date->format('M d') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ ucfirst($holiday->type->value) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $holiday->status->value === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $holiday->status->value === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $holiday->status->value === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                                        {{ ucfirst($holiday->status->value) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $holiday->manager_comment ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>