<?php

namespace App\Domain\TaskManagement\Models;

use App\Domain\TaskManagement\Enums\TaskPriority;
use App\Domain\TaskManagement\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = ['title', 'description', 'priority', 'status', 'due_date', 'estimated_hours'];

    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'due_date' => 'date',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function getTotalLoggedHoursAttribute(): float
    {
        return $this->assignments()->sum('logged_hours');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('due_date', '<', Carbon::today())
                     ->where('status', '!=', TaskStatus::Done->value);
    }
}