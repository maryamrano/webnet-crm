<?php

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    public const SOURCES = ['website', 'referral', 'cold_call', 'social_media', 'walk_in', 'other'];

    public const STATUSES = ['new', 'contacted', 'qualified', 'converted', 'lost'];

    protected $fillable = [
        'name', 'email', 'email_normalized', 'phone', 'phone_normalized', 'company_name',
        'source', 'status', 'notes', 'assigned_to', 'created_by',
        'converted_company_id', 'converted_contact_id', 'converted_at',
    ];

    protected function casts(): array
    {
        return ['converted_at' => 'datetime'];
    }

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value;
        $this->attributes['email_normalized'] = $value === null ? null : Contact::normalizeEmail($value);
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = $value;
        $this->attributes['phone_normalized'] = $value === null ? null : Contact::normalizePhone($value);
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted' && $this->converted_at !== null;
    }

    /**
     * Approved visibility: Admins and Managers see every lead; a Sales Executive
     * sees only leads assigned to them or created by them.
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin() || $user->isManager()) {
            return $query;
        }

        return $query->where(fn ($q) => $q->where('assigned_to', $user->id)->orWhere('created_by', $user->id));
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function convertedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'converted_company_id');
    }

    public function convertedContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'converted_contact_id');
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
