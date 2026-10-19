<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\LeadAlreadyConvertedException;
use App\Services\LeadConversionException;
use App\Services\LeadConversionService;
use App\Services\LeadDuplicateFinder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadConversionController extends Controller
{
    public function show(Request $request, Lead $lead, LeadDuplicateFinder $finder): View|RedirectResponse
    {
        // Authorization FIRST: an unauthorized user must not learn anything about
        // this lead — not even whether or when it was converted.
        $this->authorize('view', $lead);

        if ($lead->isConverted()) {
            return redirect()->route('leads.show', $lead)
                ->with('error', "This lead was already converted on {$lead->converted_at->format('d M Y')}.");
        }

        $this->authorize('convert', $lead);

        return view('leads.convert', [
            'lead' => $lead,
            'candidateCompanies' => $finder->candidateCompanies($lead, $request->user()),
            'candidateContacts' => $finder->candidateContacts($lead, $request->user()),
        ]);
    }

    public function store(Request $request, Lead $lead, LeadConversionService $service, LeadDuplicateFinder $finder): RedirectResponse
    {
        // Authorization FIRST (see show()): no conversion-state disclosure to
        // users who may not view this lead.
        $this->authorize('view', $lead);

        if ($lead->isConverted()) {
            return redirect()->route('leads.show', $lead)
                ->with('error', "This lead was already converted on {$lead->converted_at->format('d M Y')}. No changes were made.");
        }

        $this->authorize('convert', $lead);

        $contacts = $finder->candidateContacts($lead, $request->user());
        // Linkable companies = name candidates PLUS the companies of email-matched
        // contacts (linking such a contact necessarily means its company).
        $companies = $finder->candidateCompanies($lead, $request->user())
            ->merge($contacts->map(fn ($c) => $c->company)->filter())
            ->unique('id')->values();

        $validated = $request->validate([
            // When candidates exist the user MUST make an explicit choice — silence is not consent.
            'company_choice' => [$companies->isNotEmpty() ? 'required' : 'nullable', Rule::in(array_merge(['new'], $companies->pluck('id')->map(fn ($id) => (string) $id)->all()))],
            'contact_choice' => [$contacts->isNotEmpty() ? 'required' : 'nullable', Rule::in(array_merge(['new'], $contacts->pluck('id')->map(fn ($id) => (string) $id)->all()))],
            'create_deal' => ['nullable', 'boolean'],
            'deal_title' => ['nullable', 'string', 'max:255'],
            'deal_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $result = $service->convert($lead, [
                'company_choice' => $validated['company_choice'] ?? 'new',
                'contact_choice' => $validated['contact_choice'] ?? 'new',
                'create_deal' => $request->boolean('create_deal'),
                'deal_title' => $validated['deal_title'] ?? null,
                'deal_value' => $validated['deal_value'] ?? 0,
            ], $request->user());
        } catch (LeadAlreadyConvertedException|LeadConversionException $e) {
            return redirect()->route('leads.show', $lead)->with('error', $e->getMessage());
        }

        return redirect()->route('leads.show', $lead)->with('status', $result['message']);
    }
}
