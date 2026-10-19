<?php

namespace Database\Seeders;

use App\Models\PipelineStage;
use Illuminate\Database\Seeder;

class PipelineStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['name' => 'Prospecting', 'position' => 1, 'probability' => 10, 'is_won' => false, 'is_lost' => false],
            ['name' => 'Qualification', 'position' => 2, 'probability' => 25, 'is_won' => false, 'is_lost' => false],
            ['name' => 'Proposal', 'position' => 3, 'probability' => 50, 'is_won' => false, 'is_lost' => false],
            ['name' => 'Negotiation', 'position' => 4, 'probability' => 75, 'is_won' => false, 'is_lost' => false],
            ['name' => 'Won', 'position' => 5, 'probability' => 100, 'is_won' => true, 'is_lost' => false],
            ['name' => 'Lost', 'position' => 6, 'probability' => 0, 'is_won' => false, 'is_lost' => true],
        ];

        foreach ($stages as $stage) {
            PipelineStage::updateOrCreate(['position' => $stage['position']], $stage);
        }
    }
}
