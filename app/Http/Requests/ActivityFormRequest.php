<?php

namespace App\Http\Requests;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // parent-record authorization happens in the controller
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(Activity::TYPES)],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'happened_at' => ['nullable', 'date'],
        ];
    }
}
