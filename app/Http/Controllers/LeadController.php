<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadFormRequest;
use App\Models\Lead;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LeadDuplicateFinder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Lead::class);

        $leads = Lead::visibleTo($request->user())
            ->with(['assignee'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.trim($request->string('q')).'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('company_name', 'like', $term));
            })
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->string('source')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('assigned_to'), fn ($q) => $q->where('assigned_to', $request->integer('assigned_to')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('leads.index', [
            'leads' => $leads,
            'users' => User::orderBy('name')->get(),
            'sources' => Lead::SOURCES,
            'statuses' => Lead::STATUSES,
            'filters' => $request->only(['q', 'source', 'status', 'assigned_to']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Lead::class);

        return view('leads.create', [
            'lead' => new Lead(['status' => 'new', 'source' => 'website']),
            'users' => User::assignableFor(auth()->user()),
            'sources' => Lead::SOURCES,
            'statuses' => ['new', 'contacted', 'qualified', 'lost'],
        ]);
    }

    public function store(LeadFormRequest $request, LeadDuplicateFinder $finder): RedirectResponse
    {
        $data = $request->validated();
        // Creator is ALWAYS the authenticated user — never client-supplied (approved rule).
        $data['created_by'] = $request->user()->id;
        $data['assigned_to'] = $data['assigned_to'] ?? $request->user()->id;

        $lead = Lead::create($data);
        AuditLogger::record('lead.created', $lead, AuditLogger::createdChanges($lead, 'lead'));

        $warnings = $finder->forLead($lead, $request->user());
        $message = 'Lead created successfully.';
        if ($warnings->isNotEmpty()) {
            $message .= ' Possible duplicate warning: '.$warnings->count().' similar record(s) found — review them on the lead page. Nothing was merged or linked automatically.';
        }

        return redirect()->route('leads.show', $lead)->with('status', $message);
    }

    public function show(Request $request, Lead $lead, LeadDuplicateFinder $finder): View
    {
        $this->authorize('view', $lead);

        $lead->load(['assignee', 'creator', 'convertedCompany', 'convertedContact']);
        $lead->load(['activities' => fn ($q) => $q->with('author')->latest('happened_at')]);

        return view('leads.show', [
            'lead' => $lead,
            'duplicateWarnings' => $lead->isConverted() ? collect() : $finder->forLead($lead, $request->user()),
        ]);
    }

    public function edit(Lead $lead): View
    {
        $this->authorize('update', $lead);

        return view('leads.edit', [
            'lead' => $lead,
            'users' => User::assignableFor(auth()->user()),
            'sources' => Lead::SOURCES,
            'statuses' => $lead->isConverted() ? Lead::STATUSES : ['new', 'contacted', 'qualified', 'lost'],
        ]);
    }

    public function update(LeadFormRequest $request, Lead $lead): RedirectResponse
    {
        $data = $request->validated();
        unset($data['created_by']); // creator never changes and is never client-controlled

        if ($lead->isConverted()) {
            unset($data['status']); // converted status is immutable outside the conversion flow
        }

        $before = AuditLogger::snapshot($lead, 'lead');
        $lead->update($data);
        AuditLogger::record('lead.updated', $lead, AuditLogger::diffFrom($before, $lead, 'lead'));

        return redirect()->route('leads.show', $lead)->with('status', 'Lead updated successfully.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $this->authorize('delete', $lead);

        $changes = AuditLogger::deletedChanges($lead, 'lead');
        $lead->delete();
        AuditLogger::record('lead.deleted', $lead, $changes);

        return redirect()->route('leads.index')->with('status', 'Lead deleted successfully.');
    }
}
