# Engineering Task: Mini ERP — Time \& Workforce Management

## Overview

Build a small ERP module focused on **workforce management**: employees log their **working time**, manage **tasks**, request **holidays**, and get assigned to **shifts**. The goal is to assess your ability to handle overlapping date/time logic, enforce business rules with validation, and produce clean, well-structured code.

\---

## Scope

**In scope:**

* Employee profiles with roles (employee, manager, admin)
* Time logging (clock in/out with automatic duration calculation)
* Task management (create, assign, track status and logged hours)
* Holiday/leave requests with an approval workflow
* Shift scheduling (assign employees to shifts, prevent conflicts)
* A simple dashboard summarising hours worked, pending requests, and upcoming shifts

**Out of scope:**

* Payroll calculations or salary management
* Third-party calendar integrations
* Real-time notifications (WebSockets)
* Deployment / CI-CD

\---

## Tasks

### 1\. Data Modelling \& Database

* Design the schema for: `users`, `time\_logs`, `tasks`, `task\_assignments`, `holidays`, `shifts`, `shift\_assignments`.
* Users have a role: `employee`, `manager`, or `admin`.
* Time logs track `clock\_in`, `clock\_out`, and a computed `duration`.
* Holidays have a `start\_date`, `end\_date`, `type` (annual, sick, personal), and `status` (pending, approved, rejected).
* Shifts have a `date`, `start\_time`, `end\_time`, and a `label` (e.g. "Morning", "Night").
* Implement migrations and seeders with at least 2 weeks of realistic sample data.

### 2\. Authentication \& Role-Based Access

* Implement registration and login.
* Employees can: log time, view their tasks, request holidays, view their shifts.
* Managers can: approve/reject holidays, assign tasks and shifts, view team summaries.
* Admins can: manage all users and all data.

### 3\. Time Logging

* Employees can clock in and clock out.
* Prevent double clock-in (must clock out before clocking in again).
* Allow manual time log entries for corrections (with a `note` field explaining the reason).
* Calculate daily and weekly totals.

### 4\. Task Management

* Create tasks with: title, description, priority (low/medium/high), due date, estimated hours.
* Assign one or more employees to a task.
* Employees can log hours against a task; the system tracks actual vs. estimated hours.
* Tasks move through statuses: `todo` → `in\_progress` → `in\_review` → `done`.

### 5\. Holiday / Leave Requests

* Employees submit leave requests specifying date range and leave type.
* Validate that the request does not overlap with existing approved holidays.
* Validate against a configurable annual leave allowance (e.g. 20 days/year) — reject if insufficient balance.
* Managers approve or reject with an optional comment.
* On approval, the leave days are deducted from the employee's balance.

### 6\. Shift Scheduling

* Managers create shifts and assign employees to them.
* Prevent scheduling conflicts: an employee cannot be assigned to two overlapping shifts.
* Prevent assigning an employee to a shift on a day they have approved leave.
* Display a weekly shift calendar view for a team.

### 7\. Dashboard

* Build a summary view showing:

  * Hours logged this week (vs. expected hours, e.g. 40h)
  * Pending holiday requests (for managers)
  * Upcoming shifts for the next 7 days
  * Overdue tasks

### 8\. Tests

* Write at least **5 meaningful tests** covering: overlapping shift prevention, holiday balance validation, double clock-in prevention, task status transitions, and role-based access denial.

\---

## Hints

### Getting Started (Read This First)

If you're staring at a blank project and don't know where to begin — that's normal. Here's a concrete order of operations:

1. **Set up the project** — scaffold a fresh app in your framework of choice and confirm you can connect to a database. Get `php artisan serve` (or equivalent) running before writing any business logic.
2. **Sketch the schema on paper.** Draw boxes for `users`, `time\_logs`, `tasks`, `task\_assignments`, `holidays`, `shifts`, `shift\_assignments`. Draw lines and label them "has many", "belongs to", etc. This 15-minute exercise prevents hours of confusion later.
3. **Build vertically, not horizontally.** Don't write all 7 migrations, then all 7 models, then all controllers. Instead, pick one slice (e.g. "employee can clock in and out"), build it from database to UI, test it, commit it, and move on to the next feature.

### Architecture \& Code Organisation

* **Date/time logic is the core challenge here.** Invest time upfront in a helper method or small service class for overlap detection (e.g. `DateRangeOverlapChecker`). You will reuse it across holidays, shifts, and time logs. A simple overlap check: two ranges overlap if `start\_a < end\_b AND start\_b < end\_a`.
* **Use query scopes** (or the equivalent in your framework) for common time-based filters like `scopeThisWeek()`, `scopeUpcoming()`, `scopeForEmployee($id)`. These keep controllers clean and make your queries self-documenting.
* **Keep approval logic in a service/action class** — not in the controller. The manager's "approve holiday" action should validate balance, check overlaps, update status, and deduct days — all inside a single database transaction. This is a great candidate for a `ApproveHolidayAction` class.
* **Use enums for statuses and types.** Holiday status (`pending`, `approved`, `rejected`), task status (`todo`, `in\_progress`, `in\_review`, `done`), leave type (`annual`, `sick`, `personal`) — these should all be PHP enums or string constants, never raw strings scattered through your code.

### Data, Dates \& Seeding

* **Holiday balance is trickier than it looks.** Consider: what if a request spans a weekend? Do you count only business days? There's no single "right" answer — just pick a rule, implement it consistently, and document it in a code comment. For example: "We count only weekdays (Mon–Fri) when calculating leave days."
* **Seed with realistic data.** Generate two weeks of time logs (with some employees having missed clock-outs), a few overlapping holiday requests (some approved, some pending), and a populated shift schedule. This makes the dashboard meaningful and helps you catch bugs visually.
* **Use a date library.** Don't do date math with raw strings. Use Carbon (Laravel), Day.js (JS), or your framework's equivalent. Getting comfortable with methods like `diffInWeekdays()`, `overlaps()`, and `startOfWeek()` will save you a lot of pain.

### Edge Cases to Think About

Don't just handle the happy path. Ask yourself these questions before you submit:

* What happens if an employee forgets to clock out? Is there an open `time\_log` with no `clock\_out`? How does that affect daily totals?
* What if a manager revokes an already-approved holiday? Should the balance be restored?
* What if an employee is deleted — what happens to their assigned shifts and open tasks?
* What if someone requests leave for a period that partially overlaps an existing approved leave?
* Can a manager approve their own holiday request? Should they be allowed to?

You don't need to solve all of these perfectly, but **acknowledging them** (even with a `// TODO: handle edge case where...` comment) shows thoughtfulness.

### Using AI Tools (Encouraged)

We **actively encourage** you to use AI coding assistants during this task. In fact, how effectively you use them is something we evaluate. Here's how to get the most out of them:

* **Set up an `AGENT.md` file** in your project root. This file tells your AI agent about your project's architecture, naming conventions, directory structure, and coding standards. Without it, your AI will make generic guesses. With it, every suggestion follows your rules. We've included a sample `AGENT.md` alongside this task — use it as a starting point and adapt it to your choices.
* **Use agents like Claude Code, Cursor, GitHub Copilot, or similar.** These tools are especially good at: generating migrations from a schema description, writing date overlap validation logic, scaffolding CRUD endpoints, and writing test boilerplate.
* **Be specific in your prompts.** Instead of "make the holiday system", try: "Create an `ApproveHolidayAction` that takes a Holiday model and a manager User. It should: validate the employee has enough leave balance, check the requested dates don't overlap with existing approved holidays, update the status to 'approved', deduct the business days from the employee's balance, all inside a DB transaction." The more context you give, the better the output.
* **Don't blindly accept generated code.** Always read what the AI produces. Does the overlap detection actually work? Are the date calculations correct? Does it handle weekends? The AI is your junior pair-programmer — you're still the senior in charge.
* **Use AI for debugging too.** Stuck on a cryptic date-related error? Paste the full stack trace into your AI agent and ask "What's causing this and how do I fix it?" This is often faster than searching forums.
* **Commit AI-generated code with the same standards as hand-written code.** We don't distinguish between the two — we care about the result. If generated code is sloppy, refactor it before committing.

### Git Workflow

* **Commit early, commit often.** Each feature slice should be its own commit: "Add time logging with clock in/out", "Add holiday request with balance validation", etc.
* **Write clear commit messages** in imperative mood: "Prevent overlapping shift assignments" not "fixed shift bug" or "WIP".
* **If you mess up, don't panic.** `git stash`, `git reset --soft HEAD\~1`, and `git revert` are your friends. Ask your AI agent how to undo things safely.

\---

## Evaluation Criteria

|Area|Weight|
|-|-|
|Code structure \& readability|25%|
|Date/time logic correctness|20%|
|Business rules \& validation|20%|
|Error handling \& edge cases|15%|
|Test quality \& coverage|10%|
|Git hygiene (clear commits, sensible history)|10%|
|AI tool usage (AGENT.md, prompt quality, not blindly accepting output)|Bonus|

\---

## Included Files

You should have received the following alongside this task:

* **This document** — your task specification
* **`AGENT.md`** — a sample AI agent configuration file. Copy it into your project root, read through it, and adapt it to match your actual tech stack and architecture decisions. This is the single most impactful thing you can do to improve your AI-assisted workflow.

\---

**Time guideline:** 4 hours

Good luck!

