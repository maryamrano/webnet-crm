<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task === null
            ? $this->user()->can('create', Task::class)
            : $this->user()->can('update', $task);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'taskable_key' => ['nullable', Rule::in(['lead', 'company', 'contact', 'deal'])],
            'taskable_id' => ['nullable', 'integer', 'required_with:taskable_key'],
            'assigned_to' => ['nullable', Rule::exists('users', 'id'),
                Rule::when($user->isSalesExecutive(), [Rule::in([$user->id])])],
            'due_at' => ['nullable', 'date'],
            'reminder_at' => ['nullable', 'date'],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'status' => ['required', Rule::in(Task::STATUSES)],
        ];
    }
}
