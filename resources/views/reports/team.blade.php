<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Report — Team performance</h1>
    </x-slot>

    <div class="space-y-4">
        <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-3">
            <label class="text-sm">From <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></label>
            <label class="text-sm">To <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></label>
            <div class="flex items-end gap-2">
                <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Apply filters</button>
                <a href="{{ route('reports.team') }}" class="rounded-lg px-4 py-2 text-sm text-slate-500 hover:underline">Reset</a>
                <a href="{{ route('reports.team.export', $filters) }}" class="ml-auto rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Export CSV</a>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="crm-table min-w-full divide-y divide-slate-100 text-sm">
                    <thead><tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-3">Team member</th>
                        <th class="px-5 py-3 text-right">Leads</th><th class="px-5 py-3 text-right">Converted</th><th class="px-5 py-3 text-right">Rate</th>
                        <th class="px-5 py-3 text-right">Open deals</th><th class="px-5 py-3 text-right">Open value</th>
                        <th class="px-5 py-3 text-right">Won deals</th><th class="px-5 py-3 text-right">Won value</th>
                        <th class="px-5 py-3 text-right">Tasks done</th><th class="px-5 py-3 text-right">Tasks overdue</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($data['rows'] as $row)
                            <tr>
                                <td class="px-5 py-3"><span class="font-medium">{{ $row['user']->name }}</span><span class="block text-xs text-slate-500">{{ $row['user']->roleLabel() }}</span></td>
                                <td class="px-5 py-3 text-right">{{ $row['leads'] }}</td>
                                <td class="px-5 py-3 text-right">{{ $row['converted'] }}</td>
                                <td class="px-5 py-3 text-right">{{ $row['rate'] }}%</td>
                                <td class="px-5 py-3 text-right">{{ $row['open_deals'] }}</td>
                                <td class="px-5 py-3 text-right">{{ number_format($row['open_value']) }}</td>
                                <td class="px-5 py-3 text-right">{{ $row['won_deals'] }}</td>
                                <td class="px-5 py-3 text-right">{{ number_format($row['won_value']) }}</td>
                                <td class="px-5 py-3 text-right">{{ $row['tasks_completed'] }}</td>
                                <td class="px-5 py-3 text-right {{ $row['tasks_overdue'] > 0 ? 'font-medium text-red-600' : '' }}">{{ $row['tasks_overdue'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-5 py-8 text-center text-slate-500">No team data yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="px-1 text-xs text-slate-500">Definitions: leads = leads assigned to the person; rate = converted ÷ assigned leads; open/won value from deals they own; tasks completed counts completions in the date range; overdue is a current snapshot. Date filters apply to lead/deal creation and task completion dates.</p>
    </div>
</x-app-layout>
