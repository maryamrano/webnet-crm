<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Companies</h1>
    </x-slot>

    <div class="space-y-4">
        <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-3">
            <input type="text" name="q" aria-label="Search companies" value="{{ $filters['q'] ?? '' }}" placeholder="Search name, city, industry…"
                   class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:col-span-2">
            <select name="owner_id" aria-label="Filter by owner" class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All owners</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected((string) ($filters['owner_id'] ?? '') === (string) $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
            <div class="flex gap-2 sm:col-span-3">
                <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
                <a href="{{ route('companies.index') }}" class="rounded-lg px-4 py-2 text-sm text-slate-500 hover:underline">Reset</a>
                <a href="{{ route('companies.create') }}" class="ml-auto rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500 hover:shadow active:bg-indigo-700">Add company</a>
            </div>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($companies as $company)
                <a href="{{ route('companies.show', $company) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow">
                    <p class="font-semibold text-slate-800">{{ $company->name }}</p>
                    <p class="mt-0.5 text-sm text-slate-500">{{ $company->industry ?? '—' }} · {{ $company->city ?? '' }}</p>
                    <p class="mt-3 flex gap-4 text-xs text-slate-500">
                        <span>{{ $company->contacts_count }} contacts</span>
                        <span>{{ $company->deals_count }} deals</span>
                        <span class="ml-auto">Owner: {{ $company->owner?->name ?? 'Unassigned' }}</span>
                    </p>
                </a>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center text-slate-500 sm:col-span-2 xl:col-span-3">No companies match yet.</p>
            @endforelse
        </div>

        <div>{{ $companies->links() }}</div>
    </div>
</x-app-layout>
