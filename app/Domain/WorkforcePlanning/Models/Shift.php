<?php

namespace App\Domain\WorkforcePlanning\Models;

use App\Domain\WorkforcePlanning\Enums\ShiftLabel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Shift extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'label',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'label' => ShiftLabel::class,
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('date', '>=', Carbon::today())
            ->orderBy('date')
            ->orderBy('start_time');
    }

    public function getStartsAtAttribute(): Carbon
    {
        return Carbon::parse($this->date->format('Y-m-d').' '.$this->start_time);
    }

    public function getEndsAtAttribute(): Carbon
    {
        return Carbon::parse($this->date->format('Y-m-d').' '.$this->end_time);
    }
}