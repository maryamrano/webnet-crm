<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    public const PRIORITIES = ['low', 'medium', 'high'];

    public const STATUSES = ['pending', 'in_progress', 'completed'];

    protected $fillable = [
        'title', 'description', 'taskable_type', 'taskable_id', 'assigned_to', 'created_by',
        'due_at', 'reminder_at', 'priority', 'status', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'reminder_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'completed' && $this->due_at !== null && $this->due_at->isPast();
    }

    /** Deterministic reminder rules (evaluated at page-load; no background scheduler):
     *  - "Due soon" = not completed and due within the next 48 hours (inclusive).
     *  - "Reminder due" = not completed and reminder_at has been reached. */
    public function isDueSoon(): bool
    {
        return $this->status !== 'completed' && $this->due_at !== null
            && $this->due_at->isFuture() && $this->due_at->lte(now()->addHours(48));
    }

    public function isReminderDue(): bool
    {
        return $this->status !== 'completed' && $this->reminder_at !== null && $this->reminder_at->lte(now());
    }

    /** Admins/Managers see all tasks; Sales Executives only tasks assigned to or created by them. */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin() || $user->isManager()) {
            return $query;
        }

        return $query->where(fn ($q) => $q->where('assigned_to', $user->id)->orWhere('created_by', $user->id));
    }

    public function scopeOpen($query)
    {
        return $query->where('status', '!=', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->open()->whereNotNull('due_at')->where('due_at', '<', now());
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
