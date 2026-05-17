<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'description', 'due_date', 'completed_at', 'priority', 'status', 'started_at', 'project_id'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'due_date' => 'date',
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'started_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isStarted(): bool
    {
        return $this->status === TaskStatus::InProgress;
    }

    public function markAsStarted(): void
    {
        if ($this->status !== TaskStatus::InProgress) {
            $this->update([
                'status' => TaskStatus::InProgress,
                'started_at' => now(),
            ]);
        }
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeStarted($query)
    {
        return $query->where('status', TaskStatus::InProgress);
    }

    public function scopeNotStarted($query)
    {
        return $query->where('status', '!=', TaskStatus::InProgress)->whereNull('completed_at');
    }
}
