<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Application-level audit trail. Append-only from the application's point of
 * view: there are no routes or workflows to edit or delete entries, and the
 * model itself refuses updates/deletes. This is NOT tamper-proof storage —
 * anyone with direct database access could alter it; it is an accountability
 * log inside the application boundary.
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'actor_name', 'action', 'auditable_type', 'auditable_id',
        'changes', 'metadata', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AuditLog $log) {
            $log->created_at ??= now();
        });

        // Append-only guard at the application layer.
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Name to display even after the actor's account has been deleted. */
    public function actorDisplayName(): string
    {
        return $this->actor_name ?? $this->actor?->name ?? 'Deleted user';
    }

    public function entityLabel(): string
    {
        return $this->metadata['entity_label'] ?? class_basename($this->auditable_type).' #'.$this->auditable_id;
    }
}
