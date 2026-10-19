<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Lead — {{ $lead->name }}</h1>
    </x-slot>

    <div class="space-y-4">
        @if ($duplicateWarnings->isNotEmpty())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h4 class="font-semibold text-amber-800">Possible duplicates — please review</h4>
                <p class="mt-1 text-sm text-amber-700">These records look similar based on normalized email, phone or company name. They are warnings only — nothing has been merged or linked automatically.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-800">
                    @foreach ($duplicateWarnings as $warning)
                        <li>{{ $warning['label'] }} — {{ $warning['reason'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <dl class="grid gap-4 sm:grid-cols-2 text-sm">
                    <div><dt class="text-slate-500">Email</dt><dd class="mt-0.5 font-medium">{{ $lead->email ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Phone</dt><dd class="mt-0.5 font-medium">{{ $lead->phone ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Company (as given)</dt><dd class="mt-0.5 font-medium">{{ $lead->company_name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Source</dt><dd class="mt-0.5 font-medium">{{ str($lead->source)->replace('_', ' ')->title() }}</dd></div>
                    <div><dt class="text-slate-500">Status</dt><dd class="mt-0.5 font-medium">{{ str($lead->status)->title() }}</dd></div>
                    <div><dt class="text-slate-500">Assigned to</dt><dd class="mt-0.5 font-medium">{{ $lead->assignee?->name ?? 'Unassigned' }}</dd></div>
                    <div><dt class="text-slate-500">Created by</dt><dd class="mt-0.5 font-medium">{{ $lead->creator?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Created</dt><dd class="mt-0.5 font-medium">{{ $lead->created_at->format('d M Y, h:i A') }}</dd></div>
                </dl>
                @if ($lead->notes)
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <p class="text-sm text-slate-500">Notes</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $lead->notes }}</p>
                    </div>
                @endif
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('leads.edit', $lead) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500 hover:shadow active:bg-indigo-700">Edit lead</a>
                    @can('delete', $lead)
                        <form method="POST" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('Delete this lead?')">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Delete</button>
                        </form>
                    @endcan
                    <a href="{{ route('leads.index') }}" class="rounded-lg px-4 py-2 text-sm text-slate-500 hover:underline">Back to leads</a>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h4 class="font-semibold text-slate-800">Conversion</h4>
                @if ($lead->isConverted())
                    <p class="mt-2 text-sm text-emerald-700">Converted on {{ $lead->converted_at->format('d M Y') }}.</p>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div><dt class="text-slate-500">Company</dt><dd class="font-medium">{{ $lead->convertedCompany?->name ?? '—' }}</dd></div>
                        <div><dt class="text-slate-500">Contact</dt><dd class="font-medium">{{ $lead->convertedContact?->name ?? '—' }}</dd></div>
                    </dl>
                @else
                    <p class="mt-2 text-sm text-slate-500">Turn this lead into a customer: create (or explicitly link) a company and a contact in one atomic step.</p>
                    @can('convert', $lead)
                        <a href="{{ route('leads.convert.show', $lead) }}" class="mt-4 inline-block rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">Convert lead</a>
                    @endcan
                @endif
            </div>
        </div>
        @include('activities.section', ['record' => $lead])
    </div>
</x-app-layout>
