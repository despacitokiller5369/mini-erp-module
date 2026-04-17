# AGENT.md — Mini ERP: Time & Workforce Management

> Drop this file in your project root. Your AI coding agent (Claude Code, Cursor, Copilot, etc.) will use it to understand your project's architecture, conventions, and rules — so every suggestion it makes already follows your standards.

---

## Project Overview

**Name:** Mini ERP — Time & Workforce Management
**Stack:** Laravel 12.x, Livewire 3 (Volt), Tailwind CSS 4.x, Alpine.js 3.x, MySQL
**Architecture:** Domain-Driven Design (DDD)
**PHP Version:** 8.2+
**Testing:** Pest / PHPUnit

---

## Domain Map

This app has four bounded contexts (business domains):

| Domain | Responsibility | Key Models |
|---|---|---|
| **IdentityAndAccess** | Users, roles (employee/manager/admin), authentication | `User` |
| **TimeTracking** | Clock in/out, manual time entries, daily/weekly totals | `TimeLog` |
| **TaskManagement** | Tasks, assignments, status tracking, hour logging | `Task`, `TaskAssignment` |
| **WorkforcePlanning** | Holiday requests, leave balance, shift scheduling | `Holiday`, `Shift`, `ShiftAssignment` |

### Key Relationships

```
User ──has many──▶ TimeLog
User ──has many──▶ TaskAssignment ──belongs to──▶ Task
Task ──has many──▶ TaskAssignment ──belongs to──▶ User
User ──has many──▶ Holiday (as requester)
User ──has many──▶ Holiday (as approver)
Shift ──has many──▶ ShiftAssignment ──belongs to──▶ User
```

### Critical Date/Time Relationships

This is a date-heavy application. Nearly every feature involves overlap detection or date-range logic:

- **TimeLog:** `clock_in` and `clock_out` timestamps. Duration is computed, not stored.
- **Holiday:** `start_date` to `end_date` range. Must not overlap with other approved holidays for the same employee.
- **Shift:** `date` + `start_time` + `end_time`. Must not overlap with other shifts for the same employee. Must not fall on approved leave days.
- **Task:** `due_date` for overdue detection. `estimated_hours` vs actual logged hours.

---

## Directory Structure

```
app/
├── Domain/
│   ├── IdentityAndAccess/
│   │   ├── Enums/                # UserRole (employee, manager, admin)
│   │   ├── Models/               # User
│   │   └── Policies/
│   ├── TimeTracking/
│   │   ├── Actions/              # ClockInAction, ClockOutAction, CreateManualEntryAction
│   │   ├── DTOs/                 # ManualTimeEntryDTO
│   │   ├── Models/               # TimeLog
│   │   └── Services/             # TimeCalculationService
│   ├── TaskManagement/
│   │   ├── Actions/              # CreateTaskAction, AssignTaskAction, LogTaskHoursAction
│   │   ├── DTOs/                 # CreateTaskDTO
│   │   ├── Enums/                # TaskStatus, TaskPriority
│   │   ├── Models/               # Task, TaskAssignment
│   │   └── Services/             # TaskProgressService
│   └── WorkforcePlanning/
│       ├── Actions/              # RequestHolidayAction, ApproveHolidayAction, AssignShiftAction
│       ├── DTOs/                 # HolidayRequestDTO, ShiftAssignmentDTO
│       ├── Enums/                # HolidayStatus, LeaveType, ShiftLabel
│       ├── Models/               # Holiday, Shift, ShiftAssignment
│       └── Services/             # LeaveBalanceService, OverlapDetectionService
├── Http/
│   ├── Controllers/
│   └── Middleware/               # RoleMiddleware
resources/
├── views/
│   ├── livewire/
│   │   ├── dashboard/            # Summary view (hours, pending requests, shifts)
│   │   ├── time/                 # Clock in/out, manual entries, weekly view
│   │   ├── tasks/                # List, create, detail, log hours
│   │   ├── holidays/             # Request, approve/reject, balance
│   │   └── shifts/               # Calendar view, assign employees
│   ├── layouts/
│   └── components/
database/
├── migrations/
├── seeders/
└── factories/
tests/
├── Feature/
└── Unit/
```

---

## Conventions & Rules

### Naming

- **Models:** Singular PascalCase → `TimeLog`, `TaskAssignment`, `ShiftAssignment`
- **Tables:** Plural snake_case → `time_logs`, `task_assignments`, `shift_assignments`
- **Actions:** Verb + Noun + "Action" → `ClockInAction`, `ApproveHolidayAction`
- **DTOs:** Noun + "DTO" → `HolidayRequestDTO`, `ManualTimeEntryDTO`
- **Enums:** Noun → `TaskStatus`, `HolidayStatus`, `LeaveType`, `ShiftLabel`
- **Services:** Noun + "Service" → `LeaveBalanceService`, `OverlapDetectionService`

### Architecture Rules

1. **Controllers are thin.** Validate, call an Action, return a response. No business logic.
2. **Actions do one thing.** `ApproveHolidayAction` approves a holiday. It does not also check balance — that's delegated to `LeaveBalanceService`.
3. **Services handle complex logic.** `OverlapDetectionService` is reused across holidays, shifts, and time logs. `LeaveBalanceService` calculates remaining days.
4. **Models own relationships and scopes.** `TimeLog::scopeThisWeek()`, `Holiday::scopePending()`, `Shift::scopeUpcoming()`.
5. **DTOs carry data between layers.** Never pass raw requests into Actions.
6. **Enums for all statuses and types.** `TaskStatus`, `HolidayStatus`, `LeaveType`, `TaskPriority`, `ShiftLabel`.

### Database

- **Primary keys:** ULIDs → `$table->ulid('id')->primary()`
- **Foreign keys:** Always constrained → `$table->foreignUlid('user_id')->constrained()->cascadeOnDelete()`
- **Dates:** Use `date` type for `start_date`/`end_date` (holidays). Use `timestamp` for `clock_in`/`clock_out` (time logs). Use `date` + `time` for shifts.
- **Computed values:** `TimeLog.duration` is NOT stored — it's computed via an accessor: `clock_out - clock_in`. This avoids stale data.
- **Indexes:** On `user_id`, `date`, `status`, `clock_in` — anything used in date-range queries.

### Business Rules

These are the critical rules your code must enforce:

**Time Tracking:**
- An employee cannot clock in if they have an open time log (no `clock_out`). Prevent double clock-in.
- Manual entries require a `note` explaining the reason.
- Weekly total = sum of all `TimeLog` durations for that ISO week.

**Tasks:**
- Status transitions: `todo → in_progress → in_review → done`. Only forward. Use `TaskStatus::canTransitionTo()`.
- A task can have multiple assignees. Each assignee logs their own hours.
- Overdue = `due_date < today AND status != done`.

**Holidays:**
- Leave balance: each employee gets a configurable allowance (default: 20 days/year).
- When counting leave days, count only **weekdays** (Mon–Fri). A request from Friday to Monday = 2 days, not 4.
- Validate no overlap with existing approved holidays for the same employee.
- On approval: deduct business days from balance. On rejection: no change.
- A manager cannot approve their own request (optional rule — document your decision).

**Shifts:**
- An employee cannot be assigned to two overlapping shifts on the same day.
- An employee cannot be assigned to a shift on a day they have approved leave.
- Shift labels: `Morning`, `Afternoon`, `Night` (use an enum).

### Overlap Detection (Reusable)

Create an `OverlapDetectionService` with a method like:

```php
public function hasOverlap(string $model, string $userColumn, string $userId,
                           string $startColumn, string $endColumn,
                           $newStart, $newEnd, ?string $excludeId = null): bool
```

Two ranges overlap if: `start_a < end_b AND start_b < end_a`. This single service handles shifts, holidays, and time logs.

### Frontend / UI

- **Livewire Volt** single-file components.
- **Tailwind CSS** only.
- **Alpine.js** for dropdowns, date pickers, modals.
- The shift calendar view can use a simple HTML table grid (days as columns, employees as rows).

### Testing

- **Feature tests** for: double clock-in prevention, holiday balance validation, overlapping shift rejection, task status transitions, role-based access denial.
- **Unit tests** for: `OverlapDetectionService`, `LeaveBalanceService`, `TimeCalculationService`.
- Use `RefreshDatabase` trait. Use Factories with realistic date ranges.

### Git

- Imperative mood, max 72 chars: `Prevent overlapping shift assignments`
- Branch names: `feature/holiday-approval`, `fix/double-clock-in`
- One logical change per commit.

---

## Common Commands

```bash
php artisan serve                          # Start dev server
php artisan migrate:fresh --seed           # Reset DB with 2 weeks of sample data
php artisan make:migration create_X_table  # New migration
php artisan test                           # Run all tests
php artisan test --filter=HolidayTest      # Run specific test
./vendor/bin/pint                          # Fix code style
php artisan optimize:clear                 # Clear all caches
```

---

## When Generating Code

- Place models under `app/Domain/{Context}/Models/`.
- Always create a Factory alongside a new Model. Seed with realistic date ranges.
- Feature order: Migration → Model → Factory → Seeder → Action/DTO → Volt Component → Route → Test.
- Never `$guarded = []`. Always explicit `$fillable`.
- Always type-hint parameters and return types.
- Use Carbon for all date manipulation. Never raw string math.
- Wrap multi-step writes in `DB::transaction()`.

---

## Example Prompts

Here are prompts that work well with this project's architecture:

**Scaffolding a feature:**
> "Create the TimeTracking domain: migration for `time_logs` with `user_id`, `clock_in` (timestamp), `clock_out` (nullable timestamp), and `note` (nullable text). Create the TimeLog model with a `duration` accessor that returns the difference in hours, a `scopeThisWeek` scope, and a `scopeForUser` scope. Add a Factory that generates entries within the last 14 days."

**Building business logic:**
> "Create an `ApproveHolidayAction` in the WorkforcePlanning domain. It should: accept a Holiday model and a manager User, verify the manager has the 'manager' role, check the employee's remaining leave balance via `LeaveBalanceService`, verify no overlap with existing approved holidays using `OverlapDetectionService`, update status to 'approved', deduct business days (weekdays only) from the balance. Wrap in a DB transaction. Throw a descriptive exception if any check fails."

**Writing tests:**
> "Write Feature tests for shift assignment. Test: (1) manager can assign employee to a shift, (2) assignment is rejected when it overlaps an existing shift, (3) assignment is rejected when the employee has approved leave on that day, (4) employee role cannot assign shifts (403)."
