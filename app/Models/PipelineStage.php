<?php

namespace App\Models;

use Database\Factories\PipelineStageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineStage extends Model
{
    /** @use HasFactory<PipelineStageFactory> */
    use HasFactory;

    protected $fillable = ['name', 'position', 'probability', 'is_won', 'is_lost'];

    protected function casts(): array
    {
        return ['is_won' => 'boolean', 'is_lost' => 'boolean', 'probability' => 'integer'];
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'stage_id');
    }

    public function isOpen(): bool
    {
        return ! $this->is_won && ! $this->is_lost;
    }
}
