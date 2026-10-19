<?php

namespace App\Http\Controllers;

use App\Http\Requests\DealFormRequest;
use App\Models\Company;
use App\Models\Deal;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Deal::class);

        $deals = Deal::visibleTo($request->user())
            ->with(['company', 'stage', 'owner'])
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.trim($request->string('q')).'%'))
            ->when($request->filled('stage_id'), fn ($q) => $q->where('stage_id', $request->integer('stage_id')))
            ->when($request->filled('owner_id'), fn ($q) => $q->where('owner_id', $request->integer('owner_id')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('deals.index', [
            'deals' => $deals,
            'stages' => PipelineStage::orderBy('position')->get(),
            'users' => User::orderBy('name')->get(),
            'filters' => $request->only(['q', 'stage_id', 'owner_id']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Deal::class);

        return view('deals.create', $this->formData(new Deal));
    }

    public function store(DealFormRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id; // server-side, never client-supplied
        $data['owner_id'] = $data['owner_id'] ?? $request->user()->id;
        $data['closed_at'] = $this->closedAtFor(PipelineStage::find($data['stage_id']), null);

        $deal = Deal::create($data);
        AuditLogger::record('deal.created', $deal, AuditLogger::createdChanges($deal, 'deal'));

        return redirect()->route('deals.show', $deal)->with('status', 'Deal created successfully.');
    }

    public function show(Deal $deal): View
    {
        $this->authorize('view', $deal);

        $deal->load(['company', 'contact', 'lead', 'stage', 'owner', 'creator', 'tasks.assignee']);
        $deal->load(['activities' => fn ($q) => $q->with('author')->latest('happened_at')]);

        return view('deals.show', [
            'deal' => $deal,
            'stages' => PipelineStage::orderBy('position')->get(),
        ]);
    }

    public function edit(Deal $deal): View
    {
        $this->authorize('update', $deal);

        return view('deals.edit', $this->formData($deal));
    }

    public function update(DealFormRequest $request, Deal $deal): RedirectResponse
    {
        $data = $request->validated();
        unset($data['created_by']);
        $data['closed_at'] = $this->closedAtFor(PipelineStage::find($data['stage_id']), $deal->closed_at);
        $before = AuditLogger::snapshot($deal, 'deal');
        $deal->update($data);
        AuditLogger::record('deal.updated', $deal, AuditLogger::diffFrom($before, $deal, 'deal'));

        return redirect()->route('deals.show', $deal)->with('status', 'Deal updated successfully.');
    }

    /** Reliable stage-change control used by the pipeline board and deal page. */
    public function changeStage(Request $request, Deal $deal): RedirectResponse
    {
        $this->authorize('update', $deal);

        $validated = $request->validate([
            'stage_id' => ['required', 'exists:pipeline_stages,id'],
        ]);

        $beforeStage = $deal->stage;
        $newStage = PipelineStage::find($validated['stage_id']);
        $deal->update([
            'stage_id' => $validated['stage_id'],
            'closed_at' => $this->closedAtFor($newStage, $deal->closed_at),
        ]);
        AuditLogger::record('deal.stage_changed', $deal, [
            'stage_id' => ['before' => $beforeStage->id, 'after' => $newStage->id],
        ], ['stage_before' => $beforeStage->name, 'stage_after' => $newStage->name]);

        return back()->with('status', "Deal moved to {$deal->stage->name}.");
    }

    public function destroy(Deal $deal): RedirectResponse
    {
        $this->authorize('delete', $deal);

        // Keep history tidy and safe: the deal's activities go with it; linked tasks
        // survive but are detached (they keep their own assignment/completion history).
        $changes = AuditLogger::deletedChanges($deal, 'deal');
        $deal->activities()->delete();
        $deal->tasks()->update(['taskable_type' => null, 'taskable_id' => null]);
        $deal->delete();
        AuditLogger::record('deal.deleted', $deal, $changes);

        return redirect()->route('deals.index')->with('status', 'Deal deleted successfully.');
    }

    /**
     * Won/Lost stages close the deal (timestamped); moving back to an open stage
     * re-opens it. Probability itself is never stored here — the locked rule reads
     * the deal override first, else the CURRENT stage probability (Won=100, Lost=0).
     */
    private function closedAtFor(PipelineStage $stage, $existing)
    {
        return ($stage->is_won || $stage->is_lost) ? ($existing ?? now()) : null;
    }

    private function formData(Deal $deal): array
    {
        $user = auth()->user();

        return [
            'deal' => $deal,
            'companies' => Company::visibleTo($user)->orderBy('name')->get(),
            'stages' => PipelineStage::orderBy('position')->get(),
            'users' => User::assignableFor($user),
        ];
    }
}
