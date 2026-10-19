<?php

namespace App\Models;

use Database\Factories\DealFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Deal extends Model
{
    /** @use HasFactory<DealFactory> */
    use HasFactory;

    protected $fillable = [
        'title', 'company_id', 'contact_id', 'lead_id', 'stage_id', 'owner_id', 'created_by',
        'value', 'probability', 'expected_close_date', 'closed_at', 'description',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'probability' => 'integer',
            'expected_close_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    /** Deal probability if set, otherwise the stage's probability. */
    public function effectiveProbability(): int
    {
        return $this->probability ?? $this->stage->probability;
    }

    public function weightedValue(): float
    {
        return (float) $this->value * $this->effectiveProbability() / 100;
    }

    public function isOpen(): bool
    {
        return $this->stage->isOpen();
    }

    /** Admins/Managers see all deals; Sales Executives only deals they own or created. */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin() || $user->isManager()) {
            return $query;
        }

        return $query->where(fn ($q) => $q->where('owner_id', $user->id)->orWhere('created_by', $user->id));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
