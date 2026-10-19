<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;

class ContactFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contact = $this->route('contact');
        if ($contact !== null) {
            return $this->user()->can('update', $contact);
        }

        $company = $this->route('company');

        return $company instanceof Company && $this->user()->can('update', $company);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }
}
