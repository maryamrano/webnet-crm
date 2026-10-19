<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\PipelineStage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PipelineController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Deal::class);

        $stages = PipelineStage::orderBy('position')->get();
        $deals = Deal::visibleTo($request->user())
            ->with(['company', 'owner', 'stage'])
            ->get()
            ->groupBy('stage_id');

        // Totals use the SAME effective-probability rule as the deal pages:
        // deal override first, otherwise the stage's probability.
        $columns = $stages->map(function ($stage) use ($deals) {
            $stageDeals = $deals->get($stage->id, collect());

            return [
                'stage' => $stage,
                'deals' => $stageDeals,
                'total' => (float) $stageDeals->sum('value'),
                'weighted' => (float) $stageDeals->sum(fn ($d) => $d->weightedValue()),
            ];
        });

        return view('pipeline.index', [
            'columns' => $columns,
            'stages' => $stages,
            'openTotal' => (float) $columns->filter(fn ($c) => $c['stage']->isOpen())->sum('total'),
            'openWeighted' => (float) $columns->filter(fn ($c) => $c['stage']->isOpen())->sum('weighted'),
        ]);
    }
}
