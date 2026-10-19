<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LeadAlreadyConvertedException extends RuntimeException {}
class LeadConversionException extends RuntimeException {}

/**
 * Atomic lead conversion.
 *
 * Concurrency safeguard (deliberately NOT "lockForUpdate and hope"):
 * SQLite serializes writers per-transaction but has no MySQL-style row locks,
 * and this code must be correct on both. The claim step is therefore an ATOMIC
 * CONDITIONAL UPDATE — it only succeeds for a lead that is still unconverted
 * (converted_at IS NULL and status != 'converted'). If two attempts race, only
 * one claim can affect a row; the loser aborts and the WHOLE transaction
 * (including any company/contact it just created) rolls back.
 */
class LeadConversionService
{
    /**
     * @param  array{company_choice: string, contact_choice: string, create_deal?: bool, deal_title?: ?string, deal_value?: mixed}  $choices
     * @return array{company: Company, contact: Contact, company_created: bool, contact_created: bool, deal: ?Deal, message: string}
     */
    public function convert(Lead $lead, array $choices, User $actor): array
    {
        return DB::transaction(function () use ($lead, $choices, $actor) {
            // Fast-path check for a clean error message (the atomic claim below is the real guard).
            $fresh = Lead::findOrFail($lead->id);
            if ($fresh->isConverted()) {
                throw new LeadAlreadyConvertedException("This lead was already converted on {$fresh->converted_at->format('d M Y')}.");
            }

            [$company, $companyCreated] = $this->resolveCompany($fresh, $choices, $actor);
            [$contact, $contactCreated] = $this->resolveContact($fresh, $choices, $company, $actor);

            if ($contact->company_id !== $company->id) {
                throw new LeadConversionException('The selected contact belongs to a different company than the selected company. Nothing was changed.');
            }

            // ATOMIC CLAIM: one statement, succeeds only while the lead is unconverted.
            $claimed = Lead::whereKey($fresh->id)
                ->whereNull('converted_at')
                ->where('status', '!=', 'converted')
                ->update([
                    'status' => 'converted',
                    'converted_company_id' => $company->id,
                    'converted_contact_id' => $contact->id,
                    'converted_at' => now(),
                ]);

            if ($claimed !== 1) {
                throw new LeadAlreadyConvertedException('This lead was just converted by someone else. Nothing was changed.');
            }

            $deal = null;
            if (! empty($choices['create_deal'])) {

                $deal = Deal::create([
                    'title' => $choices['deal_title'] ?: $company->name.' — first deal',
                    'company_id' => $company->id,
                    'contact_id' => $contact->id,
                    'lead_id' => $fresh->id,
                    'stage_id' => PipelineStage::orderBy('position')->firstOrFail()->id,
                    'owner_id' => $fresh->assigned_to ?? $actor->id,
                    'created_by' => $actor->id,
                    'value' => $choices['deal_value'] ?? 0,
                ]);
            }

            // Audit INSIDE the same transaction: if the conversion rolls back,
            // this entry rolls back with it — no misleading success events.
            AuditLogger::record('lead.converted', $fresh, [
                'status' => ['before' => $fresh->status, 'after' => 'converted'],
                'converted_company_id' => ['after' => $company->id],
                'converted_contact_id' => ['after' => $contact->id],
            ], [
                'company_label' => $company->name,
                'company_created' => $companyCreated,
                'contact_label' => $contact->name,
                'contact_created' => $contactCreated,
                'deal_id' => $deal?->id,
                'actor_id' => $actor->id,
            ]);

            $message = sprintf(
                'Lead converted. Company "%s" was %s. Contact "%s" was %s.%s',
                $company->name,
                $companyCreated ? 'created' : 'linked (existing record)',
                $contact->name,
                $contactCreated ? 'created' : 'linked (existing record)',
                $deal ? " A first deal \"{$deal->title}\" was created." : ''
            );

            return [
                'company' => $company, 'contact' => $contact,
                'company_created' => $companyCreated, 'contact_created' => $contactCreated,
                'deal' => $deal, 'message' => $message,
            ];
        });
    }

    /** @return array{0: Company, 1: bool} */
    private function resolveCompany(Lead $lead, array $choices, User $actor): array
    {
        if (($choices['company_choice'] ?? 'new') !== 'new') {
            $company = Company::find($choices['company_choice']);
            if (! $company) {
                throw new LeadConversionException('The selected company no longer exists. Nothing was changed.');
            }

            return [$company, false];
        }

        $company = Company::create([
            'name' => $lead->company_name ?: $lead->name.' (company)',
            'email' => null,
            'phone' => $lead->phone,
            'owner_id' => $lead->assigned_to ?? $actor->id,
            'created_by' => $actor->id,
        ]);

        return [$company, true];
    }

    /** @return array{0: Contact, 1: bool} */
    private function resolveContact(Lead $lead, array $choices, Company $company, User $actor): array
    {
        if (($choices['contact_choice'] ?? 'new') !== 'new') {
            $contact = Contact::find($choices['contact_choice']);
            if (! $contact) {
                throw new LeadConversionException('The selected contact no longer exists. Nothing was changed.');
            }

            return [$contact, false];
        }

        $contact = Contact::create([
            'company_id' => $company->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'is_primary' => ! $company->contacts()->exists(),
        ]);

        return [$contact, true];
    }
}
