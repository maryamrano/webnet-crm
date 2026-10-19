<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Tasks</h1>
    </x-slot>

    <div class="space-y-4">
        <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-4">
            <select name="status" aria-label="Filter by status" class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All statuses</option>
                @foreach (\App\Models\Task::STATUSES as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
            <select name="priority" aria-label="Filter by priority" class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All priorities</option>
                @foreach (\App\Models\Task::PRIORITIES as $priority)
                    <option value="{{ $priority }}" @selected(($filters['priority'] ?? '') === $priority)>{{ str($priority)->title() }}</option>
                @endforeach
            </select>
            <select name="due" aria-label="Filter by due date" class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Any due date</option>
                <option value="overdue" @selected(($filters['due'] ?? '') === 'overdue')>Overdue</option>
                <option value="soon" @selected(($filters['due'] ?? '') === 'soon')>Due within 48 hours</option>
            </select>
            <select name="assigned_to" aria-label="Filter by assignee" class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All assignees</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected((string) ($filters['assigned_to'] ?? '') === (string) $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
            <div class="flex flex-wrap gap-2 sm:col-span-4">
                <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
                <a href="{{ route('tasks.index') }}" class="rounded-lg px-4 py-2 text-sm text-slate-500 hover:underline">Reset</a>
                <a href="{{ route('tasks.reminders') }}" class="ml-auto rounded-lg border border-amber-200 px-4 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50">Reminders</a>
                <a href="{{ route('tasks.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500 hover:shadow active:bg-indigo-700">Add task</a>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="crm-table min-w-full divide-y divide-slate-100 text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3">Task</th>
                            <th class="px-5 py-3">Linked to</th>
                            <th class="px-5 py-3">Due</th>
                            <th class="px-5 py-3">Priority</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Assigned to</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($tasks as $task)
                            <tr>
                                <td class="px-5 py-3">
                                    <a href="{{ route('tasks.show', $task) }}" class="font-medium text-indigo-600 hover:underline">{{ $task->title }}</a>
                                    @if ($task->isReminderDue())<span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Reminder due</span>@endif
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $task->taskable ? ($task->taskable->name ?? $task->taskable->title) : '—' }}</td>
                                <td class="px-5 py-3 {{ $task->isOverdue() ? 'font-medium text-red-600' : '' }}">
                                    {{ $task->due_at?->format('d M, h:i A') ?? '—' }}
                                    @if ($task->isOverdue())<span class="block text-xs">Overdue</span>@elseif ($task->isDueSoon())<span class="block text-xs text-amber-700">Due soon</span>@endif
                                </td>
                                <td class="px-5 py-3">
                                    @php $pColors = ['high' => 'bg-red-50 text-red-700', 'medium' => 'bg-amber-50 text-amber-700', 'low' => 'bg-slate-100 text-slate-600']; @endphp
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset ring-slate-900/10 {{ $pColors[$task->priority] }}">{{ str($task->priority)->title() }}</span>
                                </td>
                                <td class="px-5 py-3">{{ str($task->status)->replace('_', ' ')->title() }}</td>
                                <td class="px-5 py-3">{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    @if ($task->status !== 'completed')
                                        <form method="POST" action="{{ route('tasks.complete', $task) }}" class="inline">
                                            @csrf
                                            <button class="text-emerald-700 hover:underline">Complete</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('tasks.reopen', $task) }}" class="inline">
                                            @csrf
                                            <button class="text-slate-500 hover:underline">Reopen</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">No tasks match your filters yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4">{{ $tasks->links() }}</div>
        </div>
    </div>
</x-app-layout>
