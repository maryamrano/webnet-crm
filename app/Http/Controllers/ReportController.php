<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\Task;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reports. On-screen views and CSV exports share the SAME dataset builders and
 * the SAME visibility scoping, so an export can never contain records the user
 * could not see on screen. Definitions:
 *  - Conversion report: leads grouped by source and by assignee; rate =
 *    converted / total * 100 within the (filtered, scoped) lead set. Date
 *    filters apply to lead created_at.
 *  - Pipeline report: deals grouped by stage; weighted value uses the locked
 *    rule (deal override, else current stage probability). Date filters apply
 *    to deal created_at.
 *  - Team report: one row per team member (Sales Executives see only their own
 *    row): assigned leads, conversions + rate, open deals + open value, won
 *    deals + won value, completed tasks (by completed_at in range), overdue
 *    tasks (current snapshot, not date-filtered).
 */
class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index');
    }

    public function conversion(Request $request): View
    {
        return view('reports.conversion', [
            'data' => $this->conversionData($request),
            'users' => User::orderBy('name')->get(),
            'sources' => Lead::SOURCES,
            'filters' => $request->only(['from', 'to', 'owner_id', 'source']),
        ]);
    }

    public function pipeline(Request $request): View
    {
        return view('reports.pipeline', [
            'data' => $this->pipelineData($request),
            'users' => User::orderBy('name')->get(),
            'stages' => PipelineStage::orderBy('position')->get(),
            'filters' => $request->only(['from', 'to', 'owner_id', 'stage_id']),
        ]);
    }

    public function team(Request $request): View
    {
        return view('reports.team', [
            'data' => $this->teamData($request),
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    public function exportConversion(Request $request): StreamedResponse
    {
        $data = $this->conversionData($request);
        $rows = [['Section', 'Group', 'Total leads', 'Converted', 'Conversion rate %']];
        foreach ($data['by_source'] as $row) {
            $rows[] = ['By source', $row['label'], $row['total'], $row['converted'], $row['rate']];
        }
        foreach ($data['by_owner'] as $row) {
            $rows[] = ['By owner', $row['label'], $row['total'], $row['converted'], $row['rate']];
        }

        return $this->csv('lead-conversion-report.csv', $rows, $request, 'Lead conversion');
    }

    public function exportPipeline(Request $request): StreamedResponse
    {
        $data = $this->pipelineData($request);
        $rows = [['Stage', 'Probability %', 'Deals', 'Total value (PKR)', 'Weighted value (PKR)']];
        foreach ($data['rows'] as $row) {
            $rows[] = [$row['stage']->name, $row['stage']->probability, $row['count'], $row['total'], $row['weighted']];
        }
        $rows[] = ['TOTAL', '', $data['totals']['count'], $data['totals']['total'], $data['totals']['weighted']];

        return $this->csv('pipeline-report.csv', $rows, $request, 'Pipeline');
    }

    public function exportTeam(Request $request): StreamedResponse
    {
        $data = $this->teamData($request);
        $rows = [['Team member', 'Role', 'Leads assigned', 'Converted', 'Conversion rate %', 'Open deals', 'Open value (PKR)', 'Won deals', 'Won value (PKR)', 'Tasks completed', 'Tasks overdue']];
        foreach ($data['rows'] as $row) {
            $rows[] = [$row['user']->name, $row['user']->roleLabel(), $row['leads'], $row['converted'], $row['rate'],
                $row['open_deals'], $row['open_value'], $row['won_deals'], $row['won_value'], $row['tasks_completed'], $row['tasks_overdue']];
        }

        return $this->csv('team-performance-report.csv', $rows, $request, 'Team performance');
    }

    // ---------- Shared dataset builders (view + export identical) ----------

    private function conversionData(Request $request): array
    {
        $user = $request->user();
        $leads = Lead::visibleTo($user)
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->when($request->filled('owner_id'), fn ($q) => $q->where('assigned_to', $request->integer('owner_id')))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->input('source')))
            ->get();

        $group = fn ($items, $label) => [
            'label' => $label,
            'total' => $items->count(),
            'converted' => $items->where('status', 'converted')->count(),
            'rate' => $items->count() > 0 ? round($items->where('status', 'converted')->count() / $items->count() * 100, 1) : 0.0,
        ];

        return [
            'by_source' => collect(Lead::SOURCES)
                ->map(fn ($s) => $group($leads->where('source', $s), str($s)->replace('_', ' ')->title()->toString()))
                ->all(),
            'by_owner' => $leads->groupBy('assigned_to')
                ->map(fn ($items, $ownerId) => $group($items, User::find($ownerId)?->name ?? 'Unassigned'))
                ->values()->all(),
            'totals' => $group($leads, 'All leads'),
        ];
    }

    private function pipelineData(Request $request): array
    {
        $deals = Deal::visibleTo($request->user())->with('stage')
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->when($request->filled('owner_id'), fn ($q) => $q->where('owner_id', $request->integer('owner_id')))
            ->when($request->filled('stage_id'), fn ($q) => $q->where('stage_id', $request->integer('stage_id')))
            ->get()
            ->groupBy('stage_id');

        $rows = PipelineStage::orderBy('position')->get()
            ->when($request->filled('stage_id'), fn ($stages) => $stages->where('id', $request->integer('stage_id')))
            ->map(function ($stage) use ($deals) {
                $group = $deals->get($stage->id, collect());

                return [
                    'stage' => $stage,
                    'count' => $group->count(),
                    'total' => (float) $group->sum('value'),
                    'weighted' => (float) $group->sum(fn ($d) => $d->weightedValue()),
                ];
            })->values()->all();

        return [
            'rows' => $rows,
            'totals' => [
                'count' => collect($rows)->sum('count'),
                'total' => collect($rows)->sum('total'),
                'weighted' => collect($rows)->sum('weighted'),
            ],
        ];
    }

    private function teamData(Request $request): array
    {
        $actor = $request->user();
        $members = $actor->isSalesExecutive()
            ? User::whereKey($actor->id)->get()
            : User::orderBy('name')->get();
        $ids = $members->pluck('id');

        // Aggregated up front (a constant number of queries for any team size)
        // instead of four queries per member.
        $leads = Lead::whereIn('assigned_to', $ids)
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->get(['assigned_to', 'status'])->groupBy('assigned_to');
        $deals = Deal::whereIn('owner_id', $ids)->with('stage')
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->get()->groupBy('owner_id');
        $tasksCompleted = Task::whereIn('assigned_to', $ids)->where('status', 'completed')
            ->when($request->filled('from'), fn ($q) => $q->whereDate('completed_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('completed_at', '<=', $request->input('to')))
            ->selectRaw('assigned_to, COUNT(*) as aggregate')->groupBy('assigned_to')
            ->pluck('aggregate', 'assigned_to');
        $tasksOverdue = Task::whereIn('assigned_to', $ids)->overdue()
            ->selectRaw('assigned_to, COUNT(*) as aggregate')->groupBy('assigned_to')
            ->pluck('aggregate', 'assigned_to');

        $rows = $members->map(function ($member) use ($leads, $deals, $tasksCompleted, $tasksOverdue) {
            $memberLeads = $leads->get($member->id, collect());
            $memberDeals = $deals->get($member->id, collect());
            $open = $memberDeals->filter(fn ($d) => $d->stage->isOpen());
            $won = $memberDeals->filter(fn ($d) => $d->stage->is_won);
            $converted = $memberLeads->where('status', 'converted')->count();

            return [
                'user' => $member,
                'leads' => $memberLeads->count(),
                'converted' => $converted,
                'rate' => $memberLeads->count() > 0 ? round($converted / $memberLeads->count() * 100, 1) : 0.0,
                'open_deals' => $open->count(),
                'open_value' => (float) $open->sum('value'),
                'won_deals' => $won->count(),
                'won_value' => (float) $won->sum('value'),
                'tasks_completed' => (int) ($tasksCompleted[$member->id] ?? 0),
                'tasks_overdue' => (int) ($tasksOverdue[$member->id] ?? 0),
            ];
        })->all();

        return ['rows' => $rows];
    }

    // ---------- CSV ----------

    private function csv(string $filename, array $rows, Request $request, string $reportLabel): StreamedResponse
    {
        // One audit entry per export: report name + which filters were applied.
        // Report CONTENTS (rows, names, values) are never written to the log.
        AuditLogger::record('report.exported', $request->user(), [], [
            'entity_label' => "Report: {$reportLabel}",
            'report' => $reportLabel,
            'filters_applied' => collect($request->only(['from', 'to', 'owner_id', 'source', 'stage_id']))
                ->filter(fn ($v) => $v !== null && $v !== '')->keys()->all(),
        ]);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, array_map(fn ($cell) => $this->guardFormula($cell), $row));
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** CSV formula-injection guard: spreadsheet apps execute cells starting with
     *  =, +, -, @ (or tab/CR). Prefix such text cells with a single quote. */
    private function guardFormula(mixed $cell): mixed
    {
        if (is_string($cell) && preg_match('/^[\s]*[=+\-@\t\r]/', $cell)) {
            return "'".$cell;
        }

        return $cell;
    }
}
