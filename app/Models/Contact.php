<?php

namespace App\Models;

use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id', 'name', 'designation', 'email', 'email_normalized',
        'phone', 'phone_normalized', 'is_primary',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value;
        $this->attributes['email_normalized'] = $value === null ? null : self::normalizeEmail($value);
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = $value;
        $this->attributes['phone_normalized'] = $value === null ? null : self::normalizePhone($value);
    }

    public static function normalizeEmail(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /**
     * Digits only, with Pakistani formats unified (92... and 03... compare equal).
     * Matching heuristic ONLY — never proof of identity, never auto-merges records.
     */
    public static function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value);
        if (str_starts_with($digits, '92') && strlen($digits) > 10) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }

    /** Contacts inherit visibility from their company. */
    public function scopeVisibleTo($query, User $user)
    {
        return $query->whereHas('company', fn ($q) => $q->visibleTo($user));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'activityable');
    }
}
