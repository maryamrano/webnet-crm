<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Report — Lead conversion</h1>
    </x-slot>

    <div class="space-y-4">
        <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-4">
            <label class="text-sm">From <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></label>
            <label class="text-sm">To <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></label>
            <label class="text-sm">Owner
                <select name="owner_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All owners</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected((string) ($filters['owner_id'] ?? '') === (string) $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">Source
                <select name="source" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All sources</option>
                    @foreach ($sources as $source)
                        <option value="{{ $source }}" @selected(($filters['source'] ?? '') === $source)>{{ str($source)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex gap-2 sm:col-span-4">
                <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Apply filters</button>
                <a href="{{ route('reports.conversion') }}" class="rounded-lg px-4 py-2 text-sm text-slate-500 hover:underline">Reset</a>
                <a href="{{ route('reports.conversion.export', $filters) }}" class="ml-auto rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Export CSV</a>
            </div>
        </form>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm text-sm">
            <strong>Overall:</strong> {{ $data['totals']['total'] }} leads · {{ $data['totals']['converted'] }} converted · <strong>{{ $data['totals']['rate'] }}%</strong> conversion rate
            <span class="text-slate-500">(dates filter by when leads were created; you only see records you are allowed to access)</span>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <h3 class="border-b border-slate-100 px-5 py-3 font-semibold text-slate-800">By source</h3>
                <div class="overflow-x-auto"><table class="crm-table min-w-full divide-y divide-slate-100 text-sm">
                    <thead><tr class="text-left text-xs uppercase tracking-wide text-slate-500"><th class="px-5 py-2.5">Source</th><th class="px-5 py-2.5 text-right">Leads</th><th class="px-5 py-2.5 text-right">Converted</th><th class="px-5 py-2.5 text-right">Rate</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($data['by_source'] as $row)
                            <tr><td class="px-5 py-2.5">{{ $row['label'] }}</td><td class="px-5 py-2.5 text-right">{{ $row['total'] }}</td><td class="px-5 py-2.5 text-right">{{ $row['converted'] }}</td><td class="px-5 py-2.5 text-right">{{ $row['rate'] }}%</td></tr>
                        @endforeach
                    </tbody>
                </table></div>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <h3 class="border-b border-slate-100 px-5 py-3 font-semibold text-slate-800">By owner (assignee)</h3>
                <div class="overflow-x-auto"><table class="crm-table min-w-full divide-y divide-slate-100 text-sm">
                    <thead><tr class="text-left text-xs uppercase tracking-wide text-slate-500"><th class="px-5 py-2.5">Owner</th><th class="px-5 py-2.5 text-right">Leads</th><th class="px-5 py-2.5 text-right">Converted</th><th class="px-5 py-2.5 text-right">Rate</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($data['by_owner'] as $row)
                            <tr><td class="px-5 py-2.5">{{ $row['label'] }}</td><td class="px-5 py-2.5 text-right">{{ $row['total'] }}</td><td class="px-5 py-2.5 text-right">{{ $row['converted'] }}</td><td class="px-5 py-2.5 text-right">{{ $row['rate'] }}%</td></tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-8 text-center text-slate-500">No leads in this filter range yet.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
</x-app-layout>
