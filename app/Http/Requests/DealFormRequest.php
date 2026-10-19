<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DealFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $deal = $this->route('deal');

        return $deal === null
            ? $this->user()->can('create', Deal::class)
            : $this->user()->can('update', $deal);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'title' => ['required', 'string', 'max:255'],
            // The company must exist AND be visible to the acting user — no deals
            // against records the user cannot access.
            'company_id' => ['required', Rule::exists('companies', 'id'),
                function ($attribute, $value, $fail) use ($user) {
                    $company = Company::find($value);
                    if ($company && ! $user->can('view', $company)) {
                        $fail('You do not have access to the selected company.');
                    }
                }],
            // The contact (if any) must belong to the chosen company.
            'contact_id' => ['nullable', Rule::exists('contacts', 'id'),
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }
                    $contact = Contact::find($value);
                    if ($contact && (int) $contact->company_id !== (int) $this->input('company_id')) {
                        $fail('The selected contact does not belong to the selected company.');
                    }
                }],
            // Optional originating lead — must be visible to the acting user.
            'lead_id' => ['nullable', Rule::exists('leads', 'id'),
                function ($attribute, $value, $fail) use ($user) {
                    if (! $value) {
                        return;
                    }
                    $lead = Lead::find($value);
                    if ($lead && ! $user->can('view', $lead)) {
                        $fail('You do not have access to the selected lead.');
                    }
                }],
            'stage_id' => ['required', Rule::exists('pipeline_stages', 'id')],
            'owner_id' => ['nullable', Rule::exists('users', 'id'),
                Rule::when($user->isSalesExecutive(), [Rule::in([$user->id])])],
            'value' => ['required', 'numeric', 'min:0'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ];
    }
}
