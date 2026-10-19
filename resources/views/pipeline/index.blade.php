<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Sales pipeline</h1>
    </x-slot>

    <div class="space-y-4">
        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm text-sm">
            <span class="text-slate-500">Open pipeline:</span>
            <strong>PKR {{ number_format($openTotal) }}</strong>
            <span class="text-slate-500">·</span>
            <span class="text-slate-500">Weighted:</span>
            <strong>PKR {{ number_format($openWeighted) }}</strong>
            <a href="{{ route('deals.create') }}" class="ml-auto rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500 hover:shadow active:bg-indigo-700">Add deal</a>
            <a href="{{ route('deals.index') }}" class="rounded-lg px-2 py-2 text-slate-500 hover:underline">List view</a>
        </div>
        <p class="px-1 text-xs text-slate-500">Stage changes use the selector on each card — reliable on every device. Weighted values use the deal override if set, otherwise the stage probability (Won 100%, Lost 0%).</p>

        <div class="flex gap-4 overflow-x-auto pb-4">
            @foreach ($columns as $column)
                @php $stage = $column['stage']; @endphp
                <section class="w-72 shrink-0 rounded-2xl border {{ $stage->is_won ? 'border-emerald-200 bg-emerald-50/50' : ($stage->is_lost ? 'border-red-200 bg-red-50/50' : 'border-slate-200 bg-slate-50') }} p-3">
                    <header class="px-1 pb-2">
                        <h2 class="flex items-center justify-between font-semibold text-slate-800">
                            {{ $stage->name }}
                            <span class="rounded-full bg-white px-2 py-0.5 text-xs text-slate-500">{{ $stage->probability }}% · {{ $column['deals']->count() }}</span>
                        </h2>
                        <p class="mt-0.5 text-xs text-slate-500">Total PKR {{ number_format($column['total']) }} · Weighted PKR {{ number_format($column['weighted']) }}</p>
                        <div class="mt-2.5 h-1.5 overflow-hidden rounded-full bg-slate-900/10" role="img" aria-label="Stage probability {{ $stage->probability }} percent">
                            <div class="h-full rounded-full {{ $stage->is_won ? 'bg-emerald-500' : ($stage->is_lost ? 'bg-red-400' : 'bg-indigo-500') }}" style="width: {{ $stage->probability }}%"></div>
                        </div>
                    </header>

                    <div class="space-y-2.5">
                        @forelse ($column['deals'] as $deal)
                            <article class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition-shadow hover:shadow-md">
                                <a href="{{ route('deals.show', $deal) }}" class="font-semibold text-slate-800 hover:text-indigo-600">{{ $deal->title }}</a>
                                <p class="text-xs text-slate-500">{{ $deal->company->name }} · {{ $deal->owner?->name ?? 'Unassigned' }}</p>
                                <p class="mt-1.5 text-sm">
                                    <strong>PKR {{ number_format((float) $deal->value) }}</strong>
                                    <span class="text-slate-500">· {{ $deal->effectiveProbability() }}%@if ($deal->probability !== null) override @endif · weighted {{ number_format($deal->weightedValue()) }}</span>
                                </p>
                                <p class="text-xs text-slate-500">Close: {{ $deal->expected_close_date?->format('d M Y') ?? '—' }}</p>
                                <form method="POST" action="{{ route('deals.stage', $deal) }}" class="mt-2 flex gap-1.5">
                                    @csrf
                                    @method('PATCH')
                                    <select name="stage_id" class="min-w-0 flex-1 rounded-md border-slate-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500" aria-label="Move deal to stage">
                                        @foreach ($stages as $target)
                                            <option value="{{ $target->id }}" @selected($deal->stage_id === $target->id)>{{ $target->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="rounded-md bg-slate-800 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-slate-700">Move</button>
                                </form>
                            </article>
                        @empty
                            <p class="rounded-xl border border-dashed border-slate-200 bg-white/60 px-3 py-6 text-center text-xs text-slate-500">No deals in this stage</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
