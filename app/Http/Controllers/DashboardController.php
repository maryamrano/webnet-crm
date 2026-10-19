<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard metrics — every number is derived live from the database through the
 * SAME visibility scopes as the rest of the CRM (Sales Executives see only their
 * own records; Managers/Admins see everything). Nothing is hardcoded.
 *
 * Definitions:
 *  - Total leads / companies: all visible records, any status.
 *  - Open deals: deals whose stage is neither Won nor Lost.
 *  - Open pipeline value: sum of open deals' values; weighted uses the locked
 *    rule (deal override, else current stage probability).
 *  - Won value: sum of values of deals currently in the Won stage.
 *  - Conversion rate: converted leads / total leads * 100 (0 when no leads).
 *  - Month comparison: current calendar month vs the previous one — leads by
 *    created_at, conversions by converted_at, won value by closed_at of deals
 *    currently in the Won stage (i.e. value actually won that month).
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $leads = Lead::visibleTo($user);
        $totalLeads = (clone $leads)->count();
        $convertedLeads = (clone $leads)->where('status', 'converted')->count();
        $conversionRate = $totalLeads > 0 ? round($convertedLeads / $totalLeads * 100, 1) : 0.0;

        $totalCompanies = Company::visibleTo($user)->count();

        $openDeals = Deal::visibleTo($user)->with('stage')
            ->whereHas('stage', fn ($q) => $q->where('is_won', false)->where('is_lost', false))->get();
        $wonDeals = Deal::visibleTo($user)->with('stage')
            ->whereHas('stage', fn ($q) => $q->where('is_won', true))->get();

        $now = now();
        $month = fn ($query, string $column = 'created_at') => $query
            ->whereYear($column, $now->year)->whereMonth($column, $now->month);
        $prev = fn ($query, string $column = 'created_at') => $query
            ->whereYear($column, $now->copy()->subMonth()->year)->whereMonth($column, $now->copy()->subMonth()->month);

        $comparison = [
            'leads' => [
                'current' => $month(Lead::visibleTo($user))->count(),
                'previous' => $prev(Lead::visibleTo($user))->count(),
            ],
            'conversions' => [
                'current' => $month(Lead::visibleTo($user)->whereNotNull('converted_at'), 'converted_at')->count(),
                'previous' => $prev(Lead::visibleTo($user)->whereNotNull('converted_at'), 'converted_at')->count(),
            ],
            'won_value' => [
                'current' => (float) $month(Deal::visibleTo($user)->whereHas('stage', fn ($q) => $q->where('is_won', true))->whereNotNull('closed_at'), 'closed_at')->sum('value'),
                'previous' => (float) $prev(Deal::visibleTo($user)->whereHas('stage', fn ($q) => $q->where('is_won', true))->whereNotNull('closed_at'), 'closed_at')->sum('value'),
            ],
        ];
        foreach ($comparison as &$row) {
            $row['delta'] = $row['previous'] > 0
                ? round(($row['current'] - $row['previous']) / $row['previous'] * 100, 1)
                : null; // null renders as "new" — no meaningful baseline
        }

        $tasks = Task::visibleTo($user)->with(['taskable']);
        $upcomingTasks = (clone $tasks)->open()->whereNotNull('due_at')
            ->whereBetween('due_at', [$now, $now->copy()->addDays(7)])->orderBy('due_at')->limit(8)->get();
        $overdueTasks = (clone $tasks)->overdue()->orderBy('due_at')->limit(8)->get();

        // Chart data (all scoped, all derived from the tables above):
        $leadsByStatus = collect(Lead::STATUSES)->map(fn ($s) => (clone $leads)->where('status', $s)->count());
        $dealsByStage = Deal::visibleTo($user)->with('stage')->get()->groupBy('stage_id');
        $stageNames = PipelineStage::orderBy('position')->pluck('name', 'id');
        $pipelineChart = $stageNames->map(function ($name, $id) use ($dealsByStage) {
            $group = $dealsByStage->get($id, collect());

            return [
                'stage' => $name,
                'total' => (float) $group->sum('value'),
                'weighted' => (float) $group->sum(fn ($d) => $d->weightedValue()),
            ];
        })->values();

        $monthlyLeads = collect(range(5, 0))->map(function ($monthsAgo) use ($user) {
            $date = now()->subMonths($monthsAgo);

            return [
                'month' => $date->format('M Y'),
                'count' => Lead::visibleTo($user)->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
            ];
        });

        return view('dashboard', [
            'stats' => [
                'total_leads' => $totalLeads,
                'total_companies' => $totalCompanies,
                'open_deals' => $openDeals->count(),
                'open_pipeline_value' => (float) $openDeals->sum('value'),
                'open_weighted_value' => (float) $openDeals->sum(fn ($d) => $d->weightedValue()),
                'won_value' => (float) $wonDeals->sum('value'),
                'won_deals' => $wonDeals->count(),
                'conversion_rate' => $conversionRate,
                'converted_leads' => $convertedLeads,
            ],
            'comparison' => $comparison,
            'upcomingTasks' => $upcomingTasks,
            'overdueTasks' => $overdueTasks,
            'chart' => [
                'lead_status_labels' => collect(Lead::STATUSES)->map(fn ($s) => ucfirst($s))->all(),
                'lead_status_values' => $leadsByStatus->values()->all(),
                'pipeline' => $pipelineChart,
                'monthly' => $monthlyLeads,
            ],
            'hasAnyData' => $totalLeads > 0 || $totalCompanies > 0 || $openDeals->isNotEmpty() || $wonDeals->isNotEmpty(),
        ]);
    }
}
