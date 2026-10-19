<?php

namespace App\Services;

use App\Models\AuditLog;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Explicit audit capture. Called from controllers/services at the exact point
 * a business action succeeds — never from model observers — so there is exactly
 * one entry per logical action, seeders/factories stay silent, and calls made
 * INSIDE a database transaction (e.g. lead conversion) roll back together with
 * the action they describe.
 *
 * Only business fields are ever recorded. Secrets (passwords, hashes, tokens)
 * must never be passed in — the field whitelists in FIELD_MAP enforce this.
 */
class AuditLogger
{
    /** Business fields that may appear in before/after payloads, per entity. */
    private const FIELD_MAP = [
        'lead' => ['name', 'email', 'phone', 'company_name', 'source', 'status', 'assigned_to'],
        'company' => ['name', 'industry', 'website', 'email', 'phone', 'city', 'owner_id'],
        'contact' => ['name', 'designation', 'email', 'phone', 'company_id', 'is_primary'],
        'deal' => ['title', 'company_id', 'contact_id', 'lead_id', 'stage_id', 'owner_id', 'value', 'probability', 'expected_close_date', 'closed_at'],
        'task' => ['title', 'assigned_to', 'due_at', 'reminder_at', 'priority', 'status', 'completed_at'],
        'user' => ['name', 'email', 'role'],
        'activity' => ['type', 'subject', 'happened_at'],
    ];

    public static function fieldsFor(string $entityKey): array
    {
        return self::FIELD_MAP[$entityKey] ?? [];
    }

    public static function record(string $action, Model $entity, array $changes = [], array $metadata = []): AuditLog
    {
        $actor = auth()->user();

        return AuditLog::create([
            'user_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'action' => $action,
            'auditable_type' => $entity->getMorphClass(),
            'auditable_id' => $entity->getKey(),
            'changes' => $changes ?: null,
            'metadata' => array_merge(['entity_label' => self::label($entity)], $metadata),
        ]);
    }

    /** Snapshot of the whitelisted fields (used for creates and deletes). */
    public static function snapshot(Model $entity, string $entityKey): array
    {
        $snapshot = [];
        foreach (self::fieldsFor($entityKey) as $field) {
            $snapshot[$field] = self::normalize($entity->getAttribute($field));
        }

        return $snapshot;
    }

    /** Changes payload for a create: every whitelisted field as an "after" value. */
    public static function createdChanges(Model $entity, string $entityKey): array
    {
        return collect(self::snapshot($entity, $entityKey))
            ->map(fn ($v) => ['after' => $v])->all();
    }

    /** Changes payload for a delete: every whitelisted field as a "before" value,
     *  so the log still explains what was removed after the record itself is gone. */
    public static function deletedChanges(Model $entity, string $entityKey): array
    {
        return collect(self::snapshot($entity, $entityKey))
            ->map(fn ($v) => ['before' => $v])->all();
    }

    /**
     * Before/after diff from a snapshot taken BEFORE the update (Eloquent syncs
     * originals on save, so a post-save getOriginal() diff would always be empty).
     */
    public static function diffFrom(array $before, Model $entity, string $entityKey): array
    {
        $diff = [];
        foreach (self::fieldsFor($entityKey) as $field) {
            $after = self::normalize($entity->getAttribute($field));
            $b = $before[$field] ?? null;
            if ($b !== $after) {
                $diff[$field] = ['before' => $b, 'after' => $after];
            }
        }

        return $diff;
    }

    /** Before/after diff of whitelisted fields — call AFTER a successful update. */
    public static function diff(Model $entity, string $entityKey): array
    {
        $diff = [];
        foreach (self::fieldsFor($entityKey) as $field) {
            $before = self::normalize($entity->getOriginal($field));
            $after = self::normalize($entity->getAttribute($field));
            if ($before !== $after) {
                $diff[$field] = ['before' => $before, 'after' => $after];
            }
        }

        return $diff;
    }

    public static function label(Model $entity): string
    {
        $name = $entity->getAttribute('name') ?? $entity->getAttribute('title') ?? $entity->getAttribute('email');

        return $name ? (string) $name : class_basename($entity).' #'.$entity->getKey();
    }

    private static function normalize(mixed $value): mixed
    {
        if ($value instanceof CarbonInterface) {
            return $value->toDateTimeString();
        }
        if (is_bool($value)) {
            return $value;
        }

        return $value === null ? null : (is_scalar($value) ? $value : (string) $value);
    }
}
