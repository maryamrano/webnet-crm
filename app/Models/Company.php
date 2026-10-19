<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'name_normalized', 'industry', 'website', 'email', 'phone',
        'address', 'city', 'owner_id', 'created_by',
    ];

    /** Keep the normalized matching copy in sync whenever the name is set. */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = $value;
        $this->attributes['name_normalized'] = self::normalizeName($value);
    }

    public static function normalizeName(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }

    /** Admins/Managers see all companies; Sales Executives only their own (owned or created). */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin() || $user->isManager()) {
            return $query;
        }

        return $query->where(fn ($q) => $q->where('owner_id', $user->id)->orWhere('created_by', $user->id));
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function primaryContact()
    {
        return $this->hasOne(Contact::class)->where('is_primary', true);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function convertedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'converted_company_id');
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
