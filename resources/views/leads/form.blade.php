<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <x-input-label for="name" value="Full name *" />
            <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $lead->name)" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="company_name" value="Company name" />
            <x-text-input id="company_name" name="company_name" class="mt-1 block w-full" :value="old('company_name', $lead->company_name)" />
            <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $lead->email)" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="phone" value="Phone" />
            <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $lead->phone)" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="source" value="Source *" />
            <select id="source" name="source" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($sources as $source)
                    <option value="{{ $source }}" @selected(old('source', $lead->source) === $source)>{{ str($source)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('source')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="status" value="Status *" />
            <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" @disabled($lead->isConverted())>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(old('status', $lead->status) === $status)>{{ str($status)->title() }}</option>
                @endforeach
            </select>
            @if ($lead->isConverted())
                <p class="mt-1 text-xs text-slate-500">Converted leads keep their status — conversion is a one-way, audited action.</p>
                <input type="hidden" name="status" value="converted">
            @endif
            <x-input-error :messages="$errors->get('status')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="assigned_to" value="Assigned to" />
            <select id="assigned_to" name="assigned_to" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">— Unassigned —</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected((string) old('assigned_to', $lead->assigned_to) === (string) $u->id)>{{ $u->name }} ({{ $u->roleLabel() }})</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('assigned_to')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="notes" value="Notes" />
        <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $lead->notes) }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $lead->exists ? 'Save changes' : 'Create lead' }}</x-primary-button>
        <a href="{{ $lead->exists ? route('leads.show', $lead) : route('leads.index') }}" class="text-sm text-slate-500 hover:underline">Cancel</a>
    </div>
</form>
