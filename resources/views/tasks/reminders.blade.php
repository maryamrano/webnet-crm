<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Reminders</h1>
    </x-slot>

    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm text-sm text-slate-600">
            <p><strong class="text-slate-800">How reminders work here:</strong> they are in-app only — no email or SMS is sent. This page is calculated fresh every time it loads: a task appears under <em>Reminders due</em> once its reminder time has passed, under <em>Overdue</em> once its due time has passed, and under <em>Due soon</em> when it is due within the next 48 hours. Completed tasks never appear. There is no background scheduler — nothing is pushed to you while the app is closed.</p>
        </div>

        @php
            $sections = [
                ['title' => 'Overdue', 'tasks' => $overdue, 'class' => 'border-red-200 bg-red-50/60', 'empty' => 'Nothing overdue.'],
                ['title' => 'Reminders due', 'tasks' => $reminderDue, 'class' => 'border-amber-200 bg-amber-50/60', 'empty' => 'No reminders have come due.'],
                ['title' => 'Due within 48 hours', 'tasks' => $dueSoon, 'class' => 'border-amber-200 bg-white', 'empty' => 'Nothing due in the next 48 hours.'],
                ['title' => 'Due later this week', 'tasks' => $upcoming, 'class' => 'border-slate-200 bg-white', 'empty' => 'Nothing else due this week.'],
            ];
        @endphp

        @foreach ($sections as $section)
            <section class="rounded-2xl border {{ $section['class'] }} p-5 shadow-sm">
                <h3 class="font-semibold text-slate-800">{{ $section['title'] }} <span class="text-sm font-normal text-slate-500">({{ $section['tasks']->count() }})</span></h3>
                <ul class="mt-3 divide-y divide-slate-100">
                    @forelse ($section['tasks'] as $task)
                        <li class="flex flex-wrap items-center gap-2 py-2.5 text-sm">
                            <a href="{{ route('tasks.show', $task) }}" class="font-medium text-indigo-600 hover:underline">{{ $task->title }}</a>
                            @if ($task->taskable)<span class="text-slate-500">· {{ $task->taskable->name ?? $task->taskable->title }}</span>@endif
                            <span class="ml-auto text-slate-500">
                                Due {{ $task->due_at?->format('d M, h:i A') ?? '—' }}
                                @if ($task->reminder_at) · Reminder {{ $task->reminder_at->format('d M, h:i A') }} @endif
                                · {{ $task->assignee?->name ?? 'Unassigned' }}
                            </span>
                        </li>
                    @empty
                        <li class="py-2.5 text-sm text-slate-500">{{ $section['empty'] }}</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
</x-app-layout>
