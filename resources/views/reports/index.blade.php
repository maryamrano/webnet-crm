<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Reports</h1>
    </x-slot>

    <div class="grid gap-4 md:grid-cols-3">
        <a href="{{ route('reports.conversion') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-indigo-200 hover:shadow">
            <h3 class="font-semibold text-slate-800">Lead conversion</h3>
            <p class="mt-1 text-sm text-slate-500">How leads convert, grouped by source and by owner — with date, owner and source filters.</p>
            <p class="mt-3 text-sm font-medium text-indigo-600">Open report →</p>
        </a>
        <a href="{{ route('reports.pipeline') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-indigo-200 hover:shadow">
            <h3 class="font-semibold text-slate-800">Pipeline summary</h3>
            <p class="mt-1 text-sm text-slate-500">Deal count, total value and weighted value for every stage, using the same probability rule as the pipeline board.</p>
            <p class="mt-3 text-sm font-medium text-indigo-600">Open report →</p>
        </a>
        <a href="{{ route('reports.team') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-indigo-200 hover:shadow">
            <h3 class="font-semibold text-slate-800">Team performance</h3>
            <p class="mt-1 text-sm text-slate-500">Per-person leads, conversions, pipeline, won value and task completion. Sales Executives see only their own row.</p>
            <p class="mt-3 text-sm font-medium text-indigo-600">Open report →</p>
        </a>
    </div>
    <p class="mt-4 px-1 text-xs text-slate-500">Every report can be exported to CSV with the same filters applied. Exports are recorded in the audit log (Admin viewer) — the report contents themselves are never logged.</p>
</x-app-layout>
