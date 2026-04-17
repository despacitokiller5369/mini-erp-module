<?php

namespace App\Domain\TaskManagement\Models;

use App\Domain\IdentityAndAccess\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssignment extends Model
{
    use HasUlids;

    protected $fillable = ['task_id', 'user_id', 'logged_hours'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}