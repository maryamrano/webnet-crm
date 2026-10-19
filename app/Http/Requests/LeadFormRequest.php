<?php

namespace App\Http\Requests;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lead = $this->route('lead');

        return $lead === null
            ? $this->user()->can('create', Lead::class)
            : $this->user()->can('update', $lead);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'source' => ['required', Rule::in(Lead::SOURCES)],
            // 'converted' is reachable ONLY through the conversion flow, never selectable
            // in this form. The single exception: an already-converted lead being edited
            // submits its own unchanged 'converted' status back, which is accepted as-is
            // and then ignored by the controller.
            'status' => ['required', Rule::in($this->route('lead')?->isConverted() ? ['converted'] : ['new', 'contacted', 'qualified', 'lost'])],
            'notes' => ['nullable', 'string'],
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id'),
                // Sales Executives may only assign leads to themselves.
                Rule::when($user->isSalesExecutive(), [Rule::in([$user->id])]),
            ],
        ];
    }
}
