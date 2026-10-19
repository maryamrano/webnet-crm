<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Leads</h1>
    </x-slot>

    <div class="space-y-4">
        <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
            <input type="text" name="q" aria-label="Search leads" value="{{ $filters['q'] ?? '' }}" placeholder="Search name, email, company…"
                   class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 lg:col-span-2">
            <select name="source" aria-label="Filter by source" class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All sources</option>
                @foreach ($sources as $source)
                    <option value="{{ $source }}" @selected(($filters['source'] ?? '') === $source)>{{ str($source)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
            <select name="status" aria-label="Filter by status" class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str($status)->title() }}</option>
                @endforeach
            </select>
            <select name="assigned_to" aria-label="Filter by assignee" class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All assignees</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected((string) ($filters['assigned_to'] ?? '') === (string) $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-5">
                <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
                <a href="{{ route('leads.index') }}" class="rounded-lg px-4 py-2 text-sm text-slate-500 hover:underline">Reset</a>
                <a href="{{ route('leads.create') }}" class="ml-auto rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500 hover:shadow active:bg-indigo-700">Add lead</a>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="crm-table min-w-full divide-y divide-slate-100 text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3">Lead</th>
                            <th class="px-5 py-3">Company</th>
                            <th class="px-5 py-3">Source</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Assigned to</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($leads as $lead)
                            <tr>
                                <td class="px-5 py-3">
                                    <span class="font-medium text-slate-800">{{ $lead->name }}</span>
                                    <span class="block text-xs text-slate-500">{{ $lead->email }}</span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $lead->company_name ?? '—' }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ str($lead->source)->replace('_', ' ')->title() }}</td>
                                <td class="px-5 py-3">
                                    @php $colors = ['new' => 'bg-sky-50 text-sky-700', 'contacted' => 'bg-amber-50 text-amber-700', 'qualified' => 'bg-violet-50 text-violet-700', 'converted' => 'bg-emerald-50 text-emerald-700', 'lost' => 'bg-red-50 text-red-700']; @endphp
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset ring-slate-900/10 {{ $colors[$lead->status] ?? 'bg-slate-100 text-slate-600' }}">{{ str($lead->status)->title() }}</span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $lead->assignee?->name ?? 'Unassigned' }}</td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('leads.show', $lead) }}" class="text-indigo-600 hover:underline">View</a>
                                    <a href="{{ route('leads.edit', $lead) }}" class="ml-3 text-indigo-600 hover:underline">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">No leads match your filters yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4">{{ $leads->links() }}</div>
        </div>
    </div>
</x-app-layout>
