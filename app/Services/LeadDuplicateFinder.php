<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Finds possible duplicates using the normalized fields.
 * Results are WARNINGS / CANDIDATES only — this class never merges, links
 * or blocks anything. A human always decides.
 *
 * PRIVACY (Phase 8): matching is scoped to what the ACTING user may see
 * (visibleTo). A Sales Executive is never shown — or offered for linking —
 * records outside their visibility; names of other users' customers must
 * not leak through warning labels. Admins/Managers see the whole
 * organization, so their duplicate detection is unchanged.
 */
class LeadDuplicateFinder
{
    /**
     * @return Collection<int, array{kind: string, label: string, reason: string}>
     */
    public function forInput(?string $email, ?string $phone, ?string $companyName, User $actor, ?int $ignoreLeadId = null): Collection
    {
        $warnings = collect();

        $emailN = $email ? Contact::normalizeEmail($email) : null;
        $phoneN = $phone ? Contact::normalizePhone($phone) : null;
        $companyN = $companyName ? Company::normalizeName($companyName) : null;

        if ($emailN) {
            Contact::visibleTo($actor)->where('email_normalized', $emailN)->with('company')->get()->each(fn ($c) => $warnings->push([
                'kind' => 'contact', 'label' => "Contact {$c->name} ({$c->company->name})", 'reason' => 'exact email match',
            ]));
            Lead::visibleTo($actor)->where('email_normalized', $emailN)->when($ignoreLeadId, fn ($q) => $q->whereKeyNot($ignoreLeadId))->get()
                ->each(fn ($l) => $warnings->push([
                    'kind' => 'lead', 'label' => "Lead {$l->name}", 'reason' => 'exact email match',
                ]));
        }

        if ($phoneN) {
            Contact::visibleTo($actor)->where('phone_normalized', $phoneN)->with('company')->get()->each(fn ($c) => $warnings->push([
                'kind' => 'contact', 'label' => "Contact {$c->name} ({$c->company->name})", 'reason' => 'phone match (normalized)',
            ]));
            Lead::visibleTo($actor)->where('phone_normalized', $phoneN)->when($ignoreLeadId, fn ($q) => $q->whereKeyNot($ignoreLeadId))->get()
                ->each(fn ($l) => $warnings->push([
                    'kind' => 'lead', 'label' => "Lead {$l->name}", 'reason' => 'phone match (normalized)',
                ]));
        }

        if ($companyN) {
            Company::visibleTo($actor)->where('name_normalized', $companyN)->get()->each(fn ($c) => $warnings->push([
                'kind' => 'company', 'label' => "Company {$c->name}", 'reason' => 'company name match (candidate only — names are ambiguous)',
            ]));
        }

        return $warnings->unique(fn ($w) => $w['kind'].$w['label'].$w['reason'])->values();
    }

    public function forLead(Lead $lead, User $actor): Collection
    {
        return $this->forInput($lead->email, $lead->phone, $lead->company_name, $actor, $lead->id);
    }

    /** Companies that are candidates for conversion linking (by normalized name), within the actor's visibility. */
    public function candidateCompanies(Lead $lead, User $actor): Collection
    {
        if (! $lead->company_name) {
            return collect();
        }

        return Company::visibleTo($actor)->where('name_normalized', Company::normalizeName($lead->company_name))->get();
    }

    /** Contacts that exactly match the lead's normalized email — the strong identity signal — within the actor's visibility. */
    public function candidateContacts(Lead $lead, User $actor): Collection
    {
        if (! $lead->email_normalized) {
            return collect();
        }

        return Contact::visibleTo($actor)->with('company')->where('email_normalized', $lead->email_normalized)->get();
    }
}
