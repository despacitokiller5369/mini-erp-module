<?php

namespace Tests\Feature;

use App\Domain\IdentityAndAccess\Models\User;
use App\Domain\IdentityAndAccess\Enums\UserRole;
use App\Domain\WorkforcePlanning\Models\Shift;
use App\Domain\WorkforcePlanning\Models\Holiday;
use App\Domain\WorkforcePlanning\Enums\ShiftLabel;
use App\Domain\WorkforcePlanning\Enums\HolidayStatus;
use App\Domain\WorkforcePlanning\Enums\LeaveType;
use App\Domain\WorkforcePlanning\Actions\AssignShiftAction;
use App\Domain\WorkforcePlanning\Actions\ApproveHolidayAction;
use App\Domain\TaskManagement\Models\Task;
use App\Domain\TaskManagement\Enums\TaskStatus;
use App\Domain\TaskManagement\Actions\UpdateTaskStatusAction;
use App\Domain\TimeTracking\Models\TimeLog;
use App\Domain\TimeTracking\Actions\ClockInAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;
use Tests\TestCase;

class BusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Overlapping Shift Prevention
     */
    public function test_prevents_overlapping_shift_assignments()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $employee = User::factory()->create(['role' => 'employee']);

        $shift1 = Shift::create([
            'date' => '2026-05-01', 
            'start_time' => '09:00', 
            'end_time' => '17:00', 
            'label' => ShiftLabel::Morning->value
        ]);
        
        $shift2 = Shift::create([
            'date' => '2026-05-01', 
            'start_time' => '12:00', 
            'end_time' => '20:00', 
            'label' => ShiftLabel::Afternoon->value
        ]);

        $action = app(AssignShiftAction::class);
        
        $action->execute($shift1, $employee, $manager);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Employee already has an overlapping shift');

        $action->execute($shift2, $employee, $manager);
    }

    /**
     * 2. Holiday Balance Validation
     */
    public function test_validates_holiday_balance_on_approval()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $employee = User::factory()->create([
            'role' => 'employee', 
            'annual_leave_allowance' => 5,
        ]);

        $holiday = Holiday::create([
            'user_id' => $employee->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-14',
            'type' => LeaveType::Annual->value,
            'status' => HolidayStatus::Pending->value,
        ]);

        $action = app(ApproveHolidayAction::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insufficient leave balance');

        $action->execute($holiday, $manager);
        
        $this->assertEquals(5, $employee->fresh()->annual_leave_allowance);
    }

    /**
     * 3. Double Clock-In Prevention
     */
    public function test_prevents_double_clock_in()
    {
        $employee = User::factory()->create(['role' => 'employee']);

        TimeLog::create([
            'user_id' => $employee->id,
            'clock_in' => now()->subHours(2),
            'clock_out' => null,
        ]);

        $action = app(ClockInAction::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('already clocked in'); 

        $action->execute($employee);
    }

    /**
     * 4. Task Status Transitions (Forward Only)
     */
    public function test_enforces_forward_only_task_status_transitions()
    {
        $task = Task::factory()->create([
            'status' => TaskStatus::InProgress->value
        ]);

        $action = app(UpdateTaskStatusAction::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('transition'); 

        $action->execute($task, TaskStatus::Todo);
    }

    /**
     * 5. Role-Based Access Denial
     */
    public function test_denies_manager_actions_to_regular_employees()
    {
        $employee1 = User::factory()->create(['role' => 'employee']);
        $employee2 = User::factory()->create(['role' => 'employee']);

        $shift = Shift::create([
            'date' => '2026-05-02', 
            'start_time' => '09:00', 
            'end_time' => '17:00', 
            'label' => ShiftLabel::Morning->value
        ]);

        $action = app(AssignShiftAction::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only managers can assign shifts');

        $action->execute($shift, $employee2, $employee1);
    }
}