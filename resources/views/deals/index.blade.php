<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Deals</h1>
    </x-slot>

    <div class="space-y-4">
        <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-4">
            <input type="text" name="q" aria-label="Search deals" value="{{ $filters['q'] ?? '' }}" placeholder="Search deal title…"
                   class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:col-span-2">
            <select name="stage_id" aria-label="Filter by stage" class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All stages</option>
                @foreach ($stages as $stage)
                    <option value="{{ $stage->id }}" @selected((string) ($filters['stage_id'] ?? '') === (string) $stage->id)>{{ $stage->name }}</option>
                @endforeach
            </select>
            <select name="owner_id" aria-label="Filter by owner" class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All owners</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected((string) ($filters['owner_id'] ?? '') === (string) $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
            <div class="flex flex-wrap gap-2 sm:col-span-4">
                <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
                <a href="{{ route('deals.index') }}" class="rounded-lg px-4 py-2 text-sm text-slate-500 hover:underline">Reset</a>
                <a href="{{ route('pipeline.index') }}" class="ml-auto rounded-lg border border-indigo-200 px-4 py-2 text-sm font-medium text-indigo-600 hover:bg-indigo-50">Pipeline board</a>
                <a href="{{ route('deals.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500 hover:shadow active:bg-indigo-700">Add deal</a>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="crm-table min-w-full divide-y divide-slate-100 text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3">Deal</th>
                            <th class="px-5 py-3">Stage</th>
                            <th class="px-5 py-3 text-right">Value (PKR)</th>
                            <th class="px-5 py-3 text-right">Probability</th>
                            <th class="px-5 py-3 text-right">Weighted (PKR)</th>
                            <th class="px-5 py-3">Expected close</th>
                            <th class="px-5 py-3">Owner</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($deals as $deal)
                            <tr>
                                <td class="px-5 py-3">
                                    <a href="{{ route('deals.show', $deal) }}" class="font-medium text-indigo-600 hover:underline">{{ $deal->title }}</a>
                                    <span class="block text-xs text-slate-500">{{ $deal->company->name }}</span>
                                </td>
                                <td class="px-5 py-3">{{ $deal->stage->name }}</td>
                                <td class="px-5 py-3 text-right">{{ number_format((float) $deal->value) }}</td>
                                <td class="px-5 py-3 text-right">{{ $deal->effectiveProbability() }}% @if ($deal->probability !== null)<span class="text-xs text-slate-500">(override)</span>@endif</td>
                                <td class="px-5 py-3 text-right">{{ number_format($deal->weightedValue()) }}</td>
                                <td class="px-5 py-3">{{ $deal->expected_close_date?->format('d M Y') ?? '—' }}</td>
                                <td class="px-5 py-3">{{ $deal->owner?->name ?? 'Unassigned' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">No deals match your filters yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4">{{ $deals->links() }}</div>
        </div>
    </div>
</x-app-layout>
