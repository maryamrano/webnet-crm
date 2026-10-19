<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Edit activity</h1>
    </x-slot>

    <div class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('activities.update', $activity) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <x-input-label for="type" value="Type" />
                <select id="type" name="type" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach (\App\Models\Activity::TYPES as $type)
                        <option value="{{ $type }}" @selected(old('type', $activity->type) === $type)>{{ str($type)->title() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="subject" value="Subject" />
                <x-text-input id="subject" name="subject" class="mt-1 block w-full" :value="old('subject', $activity->subject)" />
            </div>
            <div>
                <x-input-label for="body" value="Details *" />
                <textarea id="body" name="body" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('body', $activity->body) }}</textarea>
                <x-input-error :messages="$errors->get('body')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="happened_at" value="When" />
                <x-text-input id="happened_at" name="happened_at" type="datetime-local" class="mt-1 block w-full" :value="old('happened_at', $activity->happened_at?->format('Y-m-d\TH:i'))" />
            </div>
            <div class="flex items-center gap-3">
                <x-primary-button>Save changes</x-primary-button>
                <a href="{{ url()->previous() }}" class="text-sm text-slate-500 hover:underline">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
