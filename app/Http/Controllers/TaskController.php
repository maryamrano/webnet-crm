<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskFormRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\CrmParentTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Task::class);

        $tasks = Task::visibleTo($request->user())
            ->with(['assignee', 'taskable'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->string('priority')))
            ->when($request->filled('assigned_to'), fn ($q) => $q->where('assigned_to', $request->integer('assigned_to')))
            ->when($request->input('due') === 'overdue', fn ($q) => $q->overdue())
            ->when($request->input('due') === 'soon', fn ($q) => $q->open()->whereNotNull('due_at')->whereBetween('due_at', [now(), now()->addHours(48)]))
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->paginate(15)
            ->withQueryString();

        return view('tasks.index', [
            'tasks' => $tasks,
            'users' => User::orderBy('name')->get(),
            'filters' => $request->only(['status', 'priority', 'assigned_to', 'due']),
        ]);
    }

    /** In-app reminders — evaluated on page load from stored times. Deterministic rules
     *  (see Task model): overdue = past due & open; due soon = due within 48h; reminder
     *  due = reminder_at reached & task open. No email/SMS, no background scheduler. */
    public function reminders(Request $request): View
    {
        $this->authorize('viewAny', Task::class);
        $base = Task::visibleTo($request->user())->with(['assignee', 'taskable']);

        return view('tasks.reminders', [
            'overdue' => (clone $base)->overdue()->orderBy('due_at')->get(),
            'dueSoon' => (clone $base)->open()->whereNotNull('due_at')->whereBetween('due_at', [now(), now()->addHours(48)])->orderBy('due_at')->get(),
            'reminderDue' => (clone $base)->open()->whereNotNull('reminder_at')->where('reminder_at', '<=', now())->orderBy('reminder_at')->get(),
            'upcoming' => (clone $base)->open()->whereNotNull('due_at')->whereBetween('due_at', [now()->addHours(48), now()->addDays(7)])->orderBy('due_at')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Task::class);

        return view('tasks.create', $this->formData(new Task));
    }

    public function store(TaskFormRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $parent = $this->resolveAndAuthorizeParent($request, $data);

        $task = new Task($data);
        $task->taskable_type = $parent?->getMorphClass();
        $task->taskable_id = $parent?->id;
        $task->created_by = $request->user()->id; // server-side
        $task->assigned_to = $task->assigned_to ?? $request->user()->id;
        $task->completed_at = $task->status === 'completed' ? now() : null;
        $task->save();
        AuditLogger::record('task.created', $task, AuditLogger::createdChanges($task, 'task'),
            $parent ? ['parent_label' => AuditLogger::label($parent), 'parent_type' => class_basename($parent)] : []);

        return redirect()->route('tasks.show', $task)->with('status', 'Task created successfully.');
    }

    public function show(Task $task): View
    {
        $this->authorize('view', $task);
        $task->load(['assignee', 'creator', 'taskable']);

        return view('tasks.show', ['task' => $task]);
    }

    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        return view('tasks.edit', $this->formData($task));
    }

    public function update(TaskFormRequest $request, Task $task): RedirectResponse
    {
        $before = AuditLogger::snapshot($task, 'task');
        $data = $request->validated();
        $parent = $this->resolveAndAuthorizeParent($request, $data);
        unset($data['created_by'], $data['taskable_key'], $data['taskable_id']);

        $task->fill($data);
        $task->taskable_type = $parent?->getMorphClass();
        $task->taskable_id = $parent?->id;
        // Completion timestamp follows the status deterministically.
        $task->completed_at = $task->status === 'completed' ? ($task->completed_at ?? now()) : null;
        $task->save();
        AuditLogger::record('task.updated', $task, AuditLogger::diffFrom($before, $task, 'task'));

        return redirect()->route('tasks.show', $task)->with('status', 'Task updated successfully.');
    }

    public function complete(Task $task): RedirectResponse
    {
        $this->authorize('update', $task);
        $before = AuditLogger::snapshot($task, 'task');
        $task->update(['status' => 'completed', 'completed_at' => now()]);
        AuditLogger::record('task.completed', $task, AuditLogger::diffFrom($before, $task, 'task'));

        return back()->with('status', 'Task marked as completed.');
    }

    public function reopen(Task $task): RedirectResponse
    {
        $this->authorize('update', $task);
        $before = AuditLogger::snapshot($task, 'task');
        $task->update(['status' => 'pending', 'completed_at' => null]);
        AuditLogger::record('task.reopened', $task, AuditLogger::diffFrom($before, $task, 'task'));

        return back()->with('status', 'Task reopened.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);
        $changes = AuditLogger::deletedChanges($task, 'task');
        $task->delete();
        AuditLogger::record('task.deleted', $task, $changes);

        return redirect()->route('tasks.index')->with('status', 'Task deleted successfully.');
    }

    /** Resolve the optional parent through the whitelist and require VIEW access to it. */
    private function resolveAndAuthorizeParent(Request $request, array $data): ?Model
    {
        if (empty($data['taskable_key']) || empty($data['taskable_id'])) {
            return null;
        }

        $class = CrmParentTypes::classFor($data['taskable_key']);
        $parent = $class::findOrFail($data['taskable_id']);
        abort_unless($request->user()->can('view', $parent), 403);

        return $parent;
    }

    private function formData(Task $task): array
    {
        $user = auth()->user();

        return [
            'task' => $task,
            'users' => $user->isSalesExecutive() ? User::whereKey($user->id)->get() : User::orderBy('name')->get(),
            'parents' => [
                'company' => Company::visibleTo($user)->orderBy('name')->get(),
                'lead' => Lead::visibleTo($user)->latest()->limit(50)->get(),
                'contact' => Contact::visibleTo($user)->orderBy('name')->limit(50)->get(),
                'deal' => Deal::visibleTo($user)->latest()->limit(50)->get(),
            ],
            'parentKey' => $task->taskable_type ? CrmParentTypes::keyFor($task->taskable_type) : null,
        ];
    }
}
