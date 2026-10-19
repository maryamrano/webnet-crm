<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Dashboard</h1>
    </x-slot>

    @php
        $deltaBadge = function ($delta) {
            if ($delta === null) {
                return '<span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">new</span>';
            }
            $sign = $delta > 0 ? '+' : '';
            $class = $delta >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600';
            return '<span class="rounded-full px-2 py-0.5 text-xs font-medium '.$class.'">'.$sign.$delta.'% vs last month</span>';
        };
    @endphp

    <div class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-2 px-1">
            <div>
                <h2 class="text-xl font-semibold text-slate-800">Assalam-o-Alaikum, {{ auth()->user()->name }}</h2>
                <p class="text-sm text-slate-500">{{ auth()->user()->roleLabel() }} · {{ now()->format('l, d F Y') }} · {{ auth()->user()->isSalesExecutive() ? 'Showing your records only' : 'Showing the whole team' }}</p>
            </div>
            <a href="{{ route('reports.index') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500 hover:shadow active:bg-indigo-700">View reports</a>
        </div>

        @unless ($hasAnyData)
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center">
                <p class="font-medium text-slate-700">No CRM data yet</p>
                <p class="mt-1 text-sm text-slate-500">Once leads, companies and deals are added, your statistics, charts and comparisons will appear here. Start by <a href="{{ route('leads.create') }}" class="text-indigo-600 hover:underline">adding a lead</a>.</p>
            </div>
        @endunless

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total leads</p><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"><svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="10" cy="8" r="3.4"/><path stroke-linecap="round" d="M4.6 19.2c.6-3 2.8-4.6 5.4-4.6 1.5 0 2.9.5 3.9 1.5M18.6 8.2v5.4M15.9 10.9h5.4"/></svg></span></div>
                <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-slate-900">{{ number_format($stats['total_leads']) }}</p>
                <p class="mt-2 flex items-center gap-2 text-xs text-slate-500">This month: {{ $comparison['leads']['current'] }} {!! $deltaBadge($comparison['leads']['delta']) !!}</p>
            </div>
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Companies</p><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-50 text-sky-600"><svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="5" y="3.5" width="14" height="17" rx="1"/><path stroke-linecap="round" d="M9 7.5h1.6M13.4 7.5H15M9 11h1.6M13.4 11H15M10.2 20.5v-2.8h3.6v2.8"/></svg></span></div>
                <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-slate-900">{{ number_format($stats['total_companies']) }}</p>
                <p class="mt-2 text-xs text-slate-500">Companies you can access</p>
            </div>
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Lead conversion rate</p><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-50 text-violet-600"><svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12a7.5 7.5 0 1 1-2.2-5.3M19.7 3.6v4h-4"/></svg></span></div>
                <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-slate-900">{{ $stats['conversion_rate'] }}%</p>
                <p class="mt-2 flex items-center gap-2 text-xs text-slate-500">{{ $stats['converted_leads'] }} converted · this month {{ $comparison['conversions']['current'] }} {!! $deltaBadge($comparison['conversions']['delta']) !!}</p>
            </div>
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Open deals</p><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600"><svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3.5" y="4" width="4.6" height="13" rx="1"/><rect x="9.7" y="4" width="4.6" height="8.5" rx="1"/><rect x="15.9" y="4" width="4.6" height="10.5" rx="1"/></svg></span></div>
                <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-slate-900">{{ number_format($stats['open_deals']) }}</p>
                <p class="mt-2 text-xs text-slate-500">Not yet won or lost</p>
            </div>
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Open pipeline value</p><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"><svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3" y="6.5" width="18" height="11" rx="2"/><circle cx="12" cy="12" r="2.6"/><path stroke-linecap="round" d="M6.2 9.8h.01M17.8 14.2h.01"/></svg></span></div>
                <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-slate-900">PKR {{ number_format($stats['open_pipeline_value']) }}</p>
                <p class="mt-2 text-xs text-slate-500">Weighted: PKR {{ number_format($stats['open_weighted_value']) }}</p>
            </div>
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Won value</p><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-50 text-teal-600"><svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4h9v4.5a4.5 4.5 0 0 1-9 0zM7.5 5.5H4.8a2.7 2.7 0 0 0 2.9 3.4M16.5 5.5h2.7a2.7 2.7 0 0 1-2.9 3.4M12 13v3M8.8 20h6.4M12 16l-1 4h2z"/></svg></span></div>
                <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-slate-900">PKR {{ number_format($stats['won_value']) }}</p>
                <p class="mt-2 flex items-center gap-2 text-xs text-slate-500">{{ $stats['won_deals'] }} won deals · this month PKR {{ number_format($comparison['won_value']['current']) }} {!! $deltaBadge($comparison['won_value']['delta']) !!}</p>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-800">Leads by status</h3>
                @if ($stats['total_leads'] > 0)
                    <div class="relative mt-3 h-56"><canvas class="max-w-full" id="chartLeadStatus" role="img" aria-label="Doughnut chart of leads by status. The same counts appear in the Lead conversion report.">Chart of leads by status (see Lead conversion report for the data table).</canvas></div>
                @else
                    <p class="mt-3 text-sm text-slate-500">No leads yet — the chart will appear once leads exist.</p>
                @endif
            </div>
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-800">Leads created — last 6 months</h3>
                @if ($stats['total_leads'] > 0)
                    <div class="relative mt-3 h-56"><canvas class="max-w-full" id="chartMonthly" role="img" aria-label="Bar chart of leads created in each of the last 6 months.">Chart of leads created per month for the last 6 months.</canvas></div>
                @else
                    <p class="mt-3 text-sm text-slate-500">No leads yet — the trend will appear once leads exist.</p>
                @endif
            </div>
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-800">Pipeline value by stage</h3>
                @if ($stats['open_deals'] + $stats['won_deals'] > 0)
                    <div class="relative mt-3 h-56"><canvas class="max-w-full" id="chartPipeline" role="img" aria-label="Bar chart comparing total and weighted pipeline value by stage. The same figures appear in the Pipeline report.">Chart of pipeline value by stage (see Pipeline report for the data table).</canvas></div>
                @else
                    <p class="mt-3 text-sm text-slate-500">No deals yet — stage values will appear once deals exist.</p>
                @endif
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="flex items-center justify-between font-semibold text-slate-800">Upcoming tasks — next 7 days <a href="{{ route('tasks.reminders') }}" class="text-sm font-normal text-indigo-600 hover:underline">Reminders</a></h3>
                <ul class="mt-3 divide-y divide-slate-100 text-sm">
                    @forelse ($upcomingTasks as $task)
                        <li class="flex items-center gap-2 py-2.5">
                            <a href="{{ route('tasks.show', $task) }}" class="font-medium text-indigo-600 hover:underline">{{ $task->title }}</a>
                            @if ($task->taskable)<span class="text-slate-500">· {{ $task->taskable->name ?? $task->taskable->title }}</span>@endif
                            <span class="ml-auto whitespace-nowrap text-slate-500">{{ $task->due_at->format('d M, h:i A') }}</span>
                        </li>
                    @empty
                        <li class="py-2.5 text-slate-500">Nothing due in the next 7 days.</li>
                    @endforelse
                </ul>
            </div>
            <div class="min-w-0 rounded-2xl border {{ $overdueTasks->isNotEmpty() ? 'border-red-200' : 'border-slate-200' }} bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-800">Overdue tasks <span class="text-sm font-normal text-slate-500">({{ $overdueTasks->count() }})</span></h3>
                <ul class="mt-3 divide-y divide-slate-100 text-sm">
                    @forelse ($overdueTasks as $task)
                        <li class="flex items-center gap-2 py-2.5">
                            <a href="{{ route('tasks.show', $task) }}" class="font-medium text-indigo-600 hover:underline">{{ $task->title }}</a>
                            @if ($task->taskable)<span class="text-slate-500">· {{ $task->taskable->name ?? $task->taskable->title }}</span>@endif
                            <span class="ml-auto whitespace-nowrap font-medium text-red-600">{{ $task->due_at->format('d M, h:i A') }}</span>
                        </li>
                    @empty
                        <li class="py-2.5 text-slate-500">No overdue tasks. Keep it up.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <p class="px-1 text-xs text-slate-500">How these numbers are calculated: conversion rate = converted leads ÷ all leads. Weighted value = deal value × probability, using the deal's own override when set, otherwise its current stage probability (Won 100%, Lost 0%). Month comparisons use created/converted/closed dates in the current vs previous calendar month. Sales Executives see only their own records everywhere, including here.</p>
    </div>

    @if ($hasAnyData)
        <script>
            // Chart.js is bundled locally via Vite (no external CDN dependency).
            // The Vite bundle loads as a deferred module script, so window.Chart
            // only exists once parsing finishes — initialize on DOMContentLoaded.
            document.addEventListener('DOMContentLoaded', function () {
            if (window.Chart) {
            const palette = ['#6366f1', '#f59e0b', '#8b5cf6', '#10b981', '#ef4444'];
            const leadStatus = @json($chart['lead_status_values']);
            if (document.getElementById('chartLeadStatus') && leadStatus.some(v => v > 0)) {
                new Chart(document.getElementById('chartLeadStatus'), {
                    type: 'doughnut',
                    data: { labels: @json($chart['lead_status_labels']), datasets: [{ data: leadStatus, backgroundColor: palette }] },
                    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                });
            }
            if (document.getElementById('chartMonthly')) new Chart(document.getElementById('chartMonthly'), {
                type: 'bar',
                data: {
                    labels: @json($chart['monthly']->pluck('month')),
                    datasets: [{ label: 'Leads created', data: @json($chart['monthly']->pluck('count')), backgroundColor: '#6366f1', borderRadius: 6 }]
                },
                options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
            });
            if (document.getElementById('chartPipeline')) new Chart(document.getElementById('chartPipeline'), {
                type: 'bar',
                data: {
                    labels: @json($chart['pipeline']->pluck('stage')),
                    datasets: [
                        { label: 'Total value', data: @json($chart['pipeline']->pluck('total')), backgroundColor: '#a5b4fc', borderRadius: 6 },
                        { label: 'Weighted value', data: @json($chart['pipeline']->pluck('weighted')), backgroundColor: '#4f46e5', borderRadius: 6 }
                    ]
                },
                options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
            });
            }
            });
        </script>
    @endif
</x-app-layout>
