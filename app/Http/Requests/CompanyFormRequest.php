<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = $this->route('company');

        return $company === null
            ? $this->user()->can('create', Company::class)
            : $this->user()->can('update', $company);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'owner_id' => [
                'nullable',
                Rule::exists('users', 'id'),
                Rule::when($user->isSalesExecutive(), [Rule::in([$user->id])]),
            ],
        ];
    }
}
