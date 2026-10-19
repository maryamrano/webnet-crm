<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Deal — {{ $deal->title }}</h1>
    </x-slot>

    <div class="space-y-4">
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Company</dt><dd class="mt-0.5 font-medium"><a class="text-indigo-600 hover:underline" href="{{ route('companies.show', $deal->company) }}">{{ $deal->company->name }}</a></dd></div>
                    <div><dt class="text-slate-500">Contact</dt><dd class="mt-0.5 font-medium">{{ $deal->contact?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Value</dt><dd class="mt-0.5 font-medium">PKR {{ number_format((float) $deal->value) }}</dd></div>
                    <div><dt class="text-slate-500">Probability</dt><dd class="mt-0.5 font-medium">{{ $deal->effectiveProbability() }}% @if ($deal->probability !== null)<span class="text-xs text-slate-500">(deal override — stage is {{ $deal->stage->probability }}%)</span>@else<span class="text-xs text-slate-500">(from stage)</span>@endif</dd></div>
                    <div><dt class="text-slate-500">Weighted value</dt><dd class="mt-0.5 font-medium">PKR {{ number_format($deal->weightedValue()) }}</dd></div>
                    <div><dt class="text-slate-500">Expected close</dt><dd class="mt-0.5 font-medium">{{ $deal->expected_close_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Owner</dt><dd class="mt-0.5 font-medium">{{ $deal->owner?->name ?? 'Unassigned' }}</dd></div>
                    <div><dt class="text-slate-500">Created by</dt><dd class="mt-0.5 font-medium">{{ $deal->creator?->name ?? '—' }}</dd></div>
                    @if ($deal->closed_at)
                        <div><dt class="text-slate-500">Closed</dt><dd class="mt-0.5 font-medium">{{ $deal->closed_at->format('d M Y, h:i A') }}</dd></div>
                    @endif
                    @if ($deal->lead)
                        <div><dt class="text-slate-500">Originating lead</dt><dd class="mt-0.5 font-medium"><a class="text-indigo-600 hover:underline" href="{{ route('leads.show', $deal->lead) }}">{{ $deal->lead->name }}</a></dd></div>
                    @endif
                </dl>
                @if ($deal->description)
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <p class="text-sm text-slate-500">Description</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $deal->description }}</p>
                    </div>
                @endif
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('deals.edit', $deal) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500 hover:shadow active:bg-indigo-700">Edit deal</a>
                    @can('delete', $deal)
                        <form method="POST" action="{{ route('deals.destroy', $deal) }}" onsubmit="return confirm('Delete this deal? Its activities will be removed and linked tasks detached.')">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Delete</button>
                        </form>
                    @endcan
                    <a href="{{ route('pipeline.index') }}" class="rounded-lg px-4 py-2 text-sm text-slate-500 hover:underline">Pipeline board</a>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h4 class="font-semibold text-slate-800">Stage</h4>
                    <p class="mt-1 text-sm text-slate-500">Currently: <strong>{{ $deal->stage->name }}</strong></p>
                    <form method="POST" action="{{ route('deals.stage', $deal) }}" class="mt-3 flex gap-2">
                        @csrf
                        @method('PATCH')
                        <select name="stage_id" class="min-w-0 flex-1 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($stages as $stage)
                                <option value="{{ $stage->id }}" @selected($deal->stage_id === $stage->id)>{{ $stage->name }} ({{ $stage->probability }}%)</option>
                            @endforeach
                        </select>
                        <button class="rounded-lg bg-slate-800 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700">Move</button>
                    </form>
                    <p class="mt-2 text-xs text-slate-500">Without a deal-level override, probability follows the stage automatically. Won closes at 100%, Lost at 0%.</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h4 class="font-semibold text-slate-800">Linked tasks</h4>
                    <ul class="mt-3 space-y-2 text-sm">
                        @forelse ($deal->tasks as $task)
                            <li class="flex items-center justify-between gap-2">
                                <a href="{{ route('tasks.show', $task) }}" class="text-indigo-600 hover:underline">{{ $task->title }}</a>
                                <span class="text-xs {{ $task->isOverdue() ? 'font-medium text-red-600' : 'text-slate-500' }}">{{ $task->status === 'completed' ? 'Completed' : ($task->isOverdue() ? 'Overdue' : 'Due '.$task->due_at?->format('d M')) }}</span>
                            </li>
                        @empty
                            <li class="text-slate-500">No tasks linked yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        @include('activities.section', ['record' => $deal])
    </div>
</x-app-layout>
