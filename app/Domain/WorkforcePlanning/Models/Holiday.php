<?php
namespace App\Domain\WorkforcePlanning\Models;

use App\Domain\IdentityAndAccess\Models\User;
use App\Domain\WorkforcePlanning\Enums\HolidayStatus;
use App\Domain\WorkforcePlanning\Enums\LeaveType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Holiday extends Model
{
    use HasUlids;

    protected $fillable = [
        'user_id', 'approver_id', 'start_date', 'end_date', 
        'type', 'status', 'reason', 'manager_comment'
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'type' => LeaveType::class,
            'status' => HolidayStatus::class,
        ];
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', HolidayStatus::Pending->value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}