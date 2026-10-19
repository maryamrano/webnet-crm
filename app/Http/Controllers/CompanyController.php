<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyFormRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::visibleTo($request->user())
            ->with(['owner'])
            ->withCount(['contacts', 'deals'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.trim($request->string('q')).'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('city', 'like', $term)->orWhere('industry', 'like', $term));
            })
            ->when($request->filled('owner_id'), fn ($q) => $q->where('owner_id', $request->integer('owner_id')))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('companies.index', [
            'companies' => $companies,
            'users' => User::orderBy('name')->get(),
            'filters' => $request->only(['q', 'owner_id']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Company::class);

        return view('companies.create', [
            'company' => new Company,
            'users' => User::assignableFor(auth()->user()),
        ]);
    }

    public function store(CompanyFormRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id; // server-side, never client-supplied
        $data['owner_id'] = $data['owner_id'] ?? $request->user()->id;

        $duplicate = Company::where('name_normalized', Company::normalizeName($data['name']))->first();
        $company = Company::create($data);
        AuditLogger::record('company.created', $company, AuditLogger::createdChanges($company, 'company'));

        $message = 'Company created successfully.';
        if ($duplicate) {
            $message .= " Warning: a company with a similar name already exists ({$duplicate->name}). Names are ambiguous, so both records were kept — nothing was merged.";
        }

        return redirect()->route('companies.show', $company)->with('status', $message);
    }

    public function show(Company $company): View
    {
        $this->authorize('view', $company);

        $company->load(['owner', 'creator', 'contacts', 'deals.stage', 'convertedLeads']);
        $company->load(['activities' => fn ($q) => $q->with('author')->latest('happened_at')]);

        return view('companies.show', ['company' => $company]);
    }

    public function edit(Company $company): View
    {
        $this->authorize('update', $company);

        return view('companies.edit', [
            'company' => $company,
            'users' => User::assignableFor(auth()->user()),
        ]);
    }

    public function update(CompanyFormRequest $request, Company $company): RedirectResponse
    {
        $data = $request->validated();
        unset($data['created_by']);
        $before = AuditLogger::snapshot($company, 'company');
        $company->update($data);
        AuditLogger::record('company.updated', $company, AuditLogger::diffFrom($before, $company, 'company'));

        return redirect()->route('companies.show', $company)->with('status', 'Company updated successfully.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        if ($company->contacts()->exists() || $company->deals()->exists() || $company->convertedLeads()->exists()) {
            return redirect()->route('companies.show', $company)
                ->with('error', 'This company has linked contacts, deals or converted leads, so it cannot be deleted. CRM history is protected.');
        }

        try {
            $changes = AuditLogger::deletedChanges($company, 'company');
            $company->delete();
            AuditLogger::record('company.deleted', $company, $changes);
        } catch (QueryException) {
            return redirect()->route('companies.show', $company)
                ->with('error', 'This company is linked to other records and cannot be deleted.');
        }

        return redirect()->route('companies.index')->with('status', 'Company deleted successfully.');
    }
}
