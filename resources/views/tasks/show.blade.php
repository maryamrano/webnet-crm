<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Task — {{ $task->title }}</h1>
    </x-slot>

    <div class="max-w-3xl space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @if ($task->isOverdue())
                <p class="mb-4 rounded-lg bg-red-50 px-4 py-2 text-sm font-medium text-red-700">This task is overdue.</p>
            @elseif ($task->isReminderDue())
                <p class="mb-4 rounded-lg bg-amber-50 px-4 py-2 text-sm font-medium text-amber-700">Reminder time reached for this task.</p>
            @elseif ($task->isDueSoon())
                <p class="mb-4 rounded-lg bg-amber-50 px-4 py-2 text-sm font-medium text-amber-700">Due within the next 48 hours.</p>
            @endif

            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Status</dt><dd class="mt-0.5 font-medium">{{ str($task->status)->replace('_', ' ')->title() }}</dd></div>
                <div><dt class="text-slate-500">Priority</dt><dd class="mt-0.5 font-medium">{{ str($task->priority)->title() }}</dd></div>
                <div><dt class="text-slate-500">Due</dt><dd class="mt-0.5 font-medium">{{ $task->due_at?->format('d M Y, h:i A') ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Reminder</dt><dd class="mt-0.5 font-medium">{{ $task->reminder_at?->format('d M Y, h:i A') ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Assigned to</dt><dd class="mt-0.5 font-medium">{{ $task->assignee?->name ?? 'Unassigned' }}</dd></div>
                <div><dt class="text-slate-500">Created by</dt><dd class="mt-0.5 font-medium">{{ $task->creator?->name ?? '—' }}</dd></div>
                @if ($task->taskable)
                    <div><dt class="text-slate-500">Linked record</dt><dd class="mt-0.5 font-medium">{{ class_basename($task->taskable) }}: {{ $task->taskable->name ?? $task->taskable->title }}</dd></div>
                @endif
                @if ($task->completed_at)
                    <div><dt class="text-slate-500">Completed</dt><dd class="mt-0.5 font-medium">{{ $task->completed_at->format('d M Y, h:i A') }}</dd></div>
                @endif
            </dl>
            @if ($task->description)
                <div class="mt-5 border-t border-slate-100 pt-4">
                    <p class="text-sm text-slate-500">Description</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $task->description }}</p>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-3">
                @if ($task->status !== 'completed')
                    <form method="POST" action="{{ route('tasks.complete', $task) }}">
                        @csrf
                        <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Mark completed</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('tasks.reopen', $task) }}">
                        @csrf
                        <button class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Reopen</button>
                    </form>
                @endif
                <a href="{{ route('tasks.edit', $task) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500 hover:shadow active:bg-indigo-700">Edit task</a>
                @can('delete', $task)
                    <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?')">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Delete</button>
                    </form>
                @endcan
                <a href="{{ route('tasks.index') }}" class="rounded-lg px-4 py-2 text-sm text-slate-500 hover:underline">Back to tasks</a>
            </div>
        </div>
    </div>
</x-app-layout>
