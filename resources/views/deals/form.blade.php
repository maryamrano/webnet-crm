<form method="POST" action="{{ $action }}" class="space-y-5" x-data="{ company: '{{ old('company_id', $deal->company_id) }}' }">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-input-label for="title" value="Deal title *" />
            <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title', $deal->title)" required />
            <x-input-error :messages="$errors->get('title')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="company_id" value="Company *" />
            <select id="company_id" name="company_id" x-model="company" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">— Select company —</option>
                @foreach ($companies as $companyOption)
                    <option value="{{ $companyOption->id }}" @selected((string) old('company_id', $deal->company_id) === (string) $companyOption->id)>{{ $companyOption->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('company_id')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="contact_id" value="Contact (optional)" />
            <select id="contact_id" name="contact_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">— None —</option>
                @foreach ($companies as $companyOption)
                    @foreach ($companyOption->contacts as $contactOption)
                        <option value="{{ $contactOption->id }}" x-show="company == '{{ $companyOption->id }}'" @selected((string) old('contact_id', $deal->contact_id) === (string) $contactOption->id)>{{ $contactOption->name }} ({{ $companyOption->name }})</option>
                    @endforeach
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('contact_id')" class="mt-2" />
            <p class="mt-1 text-xs text-slate-500">Only contacts of the selected company are valid — the server enforces this too.</p>
        </div>
        <div>
            <x-input-label for="stage_id" value="Pipeline stage *" />
            <select id="stage_id" name="stage_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($stages as $stage)
                    <option value="{{ $stage->id }}" @selected((string) old('stage_id', $deal->stage_id) === (string) $stage->id)>{{ $stage->name }} ({{ $stage->probability }}%)</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('stage_id')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="owner_id" value="Owner" />
            <select id="owner_id" name="owner_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">— Unassigned —</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected((string) old('owner_id', $deal->owner_id) === (string) $u->id)>{{ $u->name }} ({{ $u->roleLabel() }})</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('owner_id')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="value" value="Value (PKR) *" />
            <x-text-input id="value" name="value" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('value', $deal->value)" required />
            <x-input-error :messages="$errors->get('value')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="probability" value="Probability override % (optional)" />
            <x-text-input id="probability" name="probability" type="number" min="0" max="100" class="mt-1 block w-full" :value="old('probability', $deal->probability)" placeholder="Leave empty to use the stage probability" />
            <x-input-error :messages="$errors->get('probability')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="expected_close_date" value="Expected close date" />
            <x-text-input id="expected_close_date" name="expected_close_date" type="date" class="mt-1 block w-full" :value="old('expected_close_date', $deal->expected_close_date?->format('Y-m-d'))" />
            <x-input-error :messages="$errors->get('expected_close_date')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $deal->description) }}</textarea>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $deal->exists ? 'Save changes' : 'Create deal' }}</x-primary-button>
        <a href="{{ $deal->exists ? route('deals.show', $deal) : route('deals.index') }}" class="text-sm text-slate-500 hover:underline">Cancel</a>
    </div>
</form>
