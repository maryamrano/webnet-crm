<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Audit log</h1>
    </x-slot>

    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm text-sm text-slate-600">
            <p>This is the application-level audit trail: who did what, and which business fields changed. Entries are read-only here and cannot be edited or deleted from the application. It is <strong>not</strong> tamper-proof storage — it protects accountability inside the app, and anyone with direct database access sits outside that boundary.</p>
        </div>

        <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
            <select name="actor_id" aria-label="Filter by user" class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All actors</option>
                @foreach ($actors as $actorOption)
                    <option value="{{ $actorOption->id }}" @selected((string) ($filters['actor_id'] ?? '') === (string) $actorOption->id)>{{ $actorOption->name }}</option>
                @endforeach
            </select>
            <select name="action" aria-label="Filter by action" class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All actions</option>
                @foreach ($actions as $actionOption)
                    <option value="{{ $actionOption }}" @selected(($filters['action'] ?? '') === $actionOption)>{{ $actionOption }}</option>
                @endforeach
            </select>
            <select name="entity" aria-label="Filter by record type" class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All record types</option>
                @foreach ($entities as $entityOption)
                    <option value="{{ $entityOption['value'] }}" @selected(($filters['entity'] ?? '') === $entityOption['value'])>{{ $entityOption['label'] }}</option>
                @endforeach
            </select>
            <input type="date" name="from" aria-label="From date" value="{{ $filters['from'] ?? '' }}" class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <input type="date" name="to" aria-label="To date" value="{{ $filters['to'] ?? '' }}" class="w-full min-w-0 rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <div class="flex gap-2 lg:col-span-5">
                <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Filter</button>
                <a href="{{ route('admin.audit-logs.index') }}" class="rounded-lg px-4 py-2 text-sm text-slate-500 hover:underline">Reset</a>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="crm-table min-w-full divide-y divide-slate-100 text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3">When</th>
                            <th class="px-5 py-3">Actor</th>
                            <th class="px-5 py-3">Action</th>
                            <th class="px-5 py-3">Record</th>
                            <th class="px-5 py-3">Changes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 align-top">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-3 text-slate-500">{{ $log->created_at?->format('d M Y, h:i A') }}</td>
                                <td class="px-5 py-3">
                                    {{ $log->actorDisplayName() }}
                                    @if ($log->user_id === null && $log->actor_name)<span class="block text-xs text-slate-500">(account deleted)</span>@endif
                                </td>
                                <td class="px-5 py-3"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ $log->action }}</span></td>
                                <td class="px-5 py-3">
                                    {{ class_basename($log->auditable_type) }} — {{ $log->entityLabel() }}
                                    @if (! empty($log->metadata['parent_label']))<span class="block text-xs text-slate-500">on {{ $log->metadata['parent_type'] ?? '' }} {{ $log->metadata['parent_label'] }}</span>@endif
                                    @if (! empty($log->metadata['stage_before']))<span class="block text-xs text-slate-500">{{ $log->metadata['stage_before'] }} → {{ $log->metadata['stage_after'] }}</span>@endif
                                </td>
                                <td class="px-5 py-3">
                                    @if ($log->changes)
                                        <ul class="space-y-0.5 text-xs text-slate-600">
                                            @foreach ($log->changes as $field => $change)
                                                <li>
                                                    <span class="font-medium">{{ $field }}:</span>
                                                    @if (array_key_exists('before', $change))<span class="text-red-600">{{ is_bool($change['before']) ? ($change['before'] ? 'yes' : 'no') : ($change['before'] ?? '—') }}</span>@endif
                                                    @if (array_key_exists('before', $change) && array_key_exists('after', $change)) → @endif
                                                    @if (array_key_exists('after', $change))<span class="text-emerald-700">{{ is_bool($change['after']) ? ($change['after'] ? 'yes' : 'no') : ($change['after'] ?? '—') }}</span>@endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-xs text-slate-500">—</span>
                                    @endif
                                    @if (! empty($log->metadata['password_changed']))
                                        <p class="mt-1 text-xs text-amber-700">Password was changed (value never recorded).</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">No audit entries match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4">{{ $logs->links() }}</div>
        </div>
    </div>
</x-app-layout>
