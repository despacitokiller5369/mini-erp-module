<?php

namespace App\Domain\TimeTracking\Models;

use App\Domain\IdentityAndAccess\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class TimeLog extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'clock_in',
        'clock_out',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
        ];
    }

    public function getDurationHoursAttribute(): float
    {
        if (!$this->clock_out) {
            return round($this->clock_in->diffInMinutes(Carbon::now()) / 60, 2);
        }
        
        return round($this->clock_in->diffInMinutes($this->clock_out) / 60, 2);
    }

    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('clock_in', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('clock_out');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
