{{-- Activities timeline + log form for a parent record.
     Expects: $record (Lead|Company|Contact|Deal) with ->activities loaded (author eager loaded). --}}
@php
    $parentKey = match (true) {
        $record instanceof \App\Models\Lead => 'lead',
        $record instanceof \App\Models\Company => 'company',
        $record instanceof \App\Models\Contact => 'contact',
        $record instanceof \App\Models\Deal => 'deal',
        default => null,
    };
    $typeIcons = ['call' => '📞', 'email' => '✉️', 'meeting' => '🤝', 'note' => '📝'];
@endphp

<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h4 class="font-semibold text-slate-800">Activity timeline</h4>

    <form method="POST" action="{{ route('activities.store') }}" class="mt-4 space-y-3 rounded-xl bg-slate-50 p-4">
        @csrf
        <input type="hidden" name="activityable_key" value="{{ $parentKey }}">
        <input type="hidden" name="activityable_id" value="{{ $record->id }}">
        <div class="grid gap-3 sm:grid-cols-3">
            <select name="type" class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach (\App\Models\Activity::TYPES as $type)
                    <option value="{{ $type }}">{{ str($type)->title() }}</option>
                @endforeach
            </select>
            <input type="text" name="subject" placeholder="Subject (optional)" class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:col-span-2">
        </div>
        <textarea name="body" rows="2" required placeholder="What happened? (required)" class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
        <div class="flex items-center gap-3">
            <input type="datetime-local" name="happened_at" class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500 hover:shadow active:bg-indigo-700">Log activity</button>
        </div>
        <x-input-error :messages="$errors->get('body')" />
        <x-input-error :messages="$errors->get('type')" />
    </form>

    <div class="mt-5 space-y-4">
        @forelse ($record->activities as $activity)
            <div class="flex gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-base">{{ $typeIcons[$activity->type] ?? '•' }}</div>
                <div class="min-w-0 flex-1 border-b border-slate-100 pb-4">
                    <p class="text-sm">
                        <span class="font-medium text-slate-800">{{ $activity->subject ?: str($activity->type)->title() }}</span>
                        <span class="text-slate-500">· {{ $activity->happened_at->format('d M Y, h:i A') }} · by {{ $activity->author?->name ?? 'a removed user' }}</span>
                    </p>
                    <p class="mt-1 text-sm text-slate-600">{{ $activity->body }}</p>
                    @can('update', $activity)
                        <p class="mt-1.5 flex gap-3 text-xs">
                            <a href="{{ route('activities.edit', $activity) }}" class="text-indigo-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('activities.destroy', $activity) }}" onsubmit="return confirm('Delete this activity?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </p>
                    @endcan
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">No activities logged yet. Calls, emails, meetings and notes will appear here.</p>
        @endforelse
    </div>
</div>
