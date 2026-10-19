<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Report — Pipeline summary</h1>
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
            <label class="text-sm">Stage
                <select name="stage_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All stages</option>
                    @foreach ($stages as $stage)
                        <option value="{{ $stage->id }}" @selected((string) ($filters['stage_id'] ?? '') === (string) $stage->id)>{{ $stage->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex gap-2 sm:col-span-4">
                <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Apply filters</button>
                <a href="{{ route('reports.pipeline') }}" class="rounded-lg px-4 py-2 text-sm text-slate-500 hover:underline">Reset</a>
                <a href="{{ route('reports.pipeline.export', $filters) }}" class="ml-auto rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Export CSV</a>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto"><table class="crm-table min-w-full divide-y divide-slate-100 text-sm">
                <thead><tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-5 py-3">Stage</th><th class="px-5 py-3 text-right">Probability</th><th class="px-5 py-3 text-right">Deals</th>
                    <th class="px-5 py-3 text-right">Total value (PKR)</th><th class="px-5 py-3 text-right">Weighted value (PKR)</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($data['rows'] as $row)
                        <tr>
                            <td class="px-5 py-3 font-medium">{{ $row['stage']->name }}</td>
                            <td class="px-5 py-3 text-right">{{ $row['stage']->probability }}%</td>
                            <td class="px-5 py-3 text-right">{{ $row['count'] }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($row['total']) }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($row['weighted']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50 font-medium">
                        <td class="px-5 py-3">Total</td><td></td>
                        <td class="px-5 py-3 text-right">{{ $data['totals']['count'] }}</td>
                        <td class="px-5 py-3 text-right">{{ number_format($data['totals']['total']) }}</td>
                        <td class="px-5 py-3 text-right">{{ number_format($data['totals']['weighted']) }}</td>
                    </tr>
                </tfoot>
            </table></div>
        </div>
        <p class="px-1 text-xs text-slate-500">Weighted value uses each deal's own probability override when set, otherwise the stage probability — the same rule as the pipeline board and dashboard. Date filters apply to when deals were created.</p>
    </div>
</x-app-layout>
