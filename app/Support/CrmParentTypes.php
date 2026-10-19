<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Model;

/**
 * The single whitelist of record types that tasks and activities may attach
 * to. Shared by TaskController and ActivityController (Phase 12 dedupe —
 * previously each controller kept its own copy of this map, and the two
 * copies had to be kept in sync by hand).
 */
final class CrmParentTypes
{
    /** @var array<string, class-string<Model>> */
    public const TYPES = [
        'lead' => Lead::class,
        'company' => Company::class,
        'contact' => Contact::class,
        'deal' => Deal::class,
    ];

    /** @return array<string, class-string<Model>> */
    public static function types(): array
    {
        return self::TYPES;
    }

    /** @return string[] */
    public static function keys(): array
    {
        return array_keys(self::TYPES);
    }

    public static function classFor(string $key): ?string
    {
        return self::TYPES[$key] ?? null;
    }

    public static function keyFor(string $morphClass): ?string
    {
        return array_search($morphClass, self::TYPES, true) ?: null;
    }
}
