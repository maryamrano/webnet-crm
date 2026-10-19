<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Convert lead — {{ $lead->name }}</h1>
    </x-slot>

    <form method="POST" action="{{ route('leads.convert.store', $lead) }}" class="max-w-3xl space-y-4">
        @csrf

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h4 class="font-semibold text-slate-800">Company</h4>
            @if ($candidateCompanies->isNotEmpty())
                <p class="mt-1 text-sm text-amber-700">We found {{ $candidateCompanies->count() }} existing compan{{ $candidateCompanies->count() > 1 ? 'ies' : 'y' }} with a similar name. A name match alone never links automatically — please choose explicitly:</p>
                <div class="mt-3 space-y-2">
                    @foreach ($candidateCompanies as $company)
                        <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm">
                            <input type="radio" name="company_choice" value="{{ $company->id }}" @checked(old('company_choice') == $company->id)>
                            <span>Link to existing: <strong>{{ $company->name }}</strong> <span class="text-slate-500">({{ $company->city }}, {{ $company->contacts_count ?? $company->contacts->count() }} contacts)</span></span>
                        </label>
                    @endforeach
                    <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm">
                        <input type="radio" name="company_choice" value="new" @checked(old('company_choice') === 'new')>
                        <span>Create a new company named <strong>{{ $lead->company_name ?: $lead->name }}</strong></span>
                    </label>
                </div>
                <x-input-error :messages="$errors->get('company_choice')" class="mt-2" />
            @else
                <p class="mt-1 text-sm text-slate-500">No existing company matches this lead's company name. A new company will be created.</p>
                <input type="hidden" name="company_choice" value="new">
                <p class="mt-3 rounded-lg bg-slate-50 px-4 py-3 text-sm">New company: <strong>{{ $lead->company_name ?: $lead->name.' (company)' }}</strong></p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h4 class="font-semibold text-slate-800">Contact</h4>
            @if ($candidateContacts->isNotEmpty())
                <p class="mt-1 text-sm text-amber-700">An exact email match was found. Please choose explicitly — we never link silently:</p>
                <div class="mt-3 space-y-2">
                    @foreach ($candidateContacts as $contact)
                        <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm">
                            <input type="radio" name="contact_choice" value="{{ $contact->id }}" @checked(old('contact_choice') == $contact->id)>
                            <span>Link to existing: <strong>{{ $contact->name }}</strong> <span class="text-slate-500">at {{ $contact->company->name }} ({{ $contact->email }})</span></span>
                        </label>
                    @endforeach
                    <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm">
                        <input type="radio" name="contact_choice" value="new" @checked(old('contact_choice') === 'new')>
                        <span>Create a new contact for <strong>{{ $lead->name }}</strong> in the company chosen above</span>
                    </label>
                </div>
                <x-input-error :messages="$errors->get('contact_choice')" class="mt-2" />
            @else
                <p class="mt-1 text-sm text-slate-500">No existing contact matches this lead's email. A new contact will be created in the company chosen above.</p>
                <input type="hidden" name="contact_choice" value="new">
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
                <input type="checkbox" name="create_deal" value="1" class="rounded border-slate-300" @checked(old('create_deal'))>
                Also create a first deal (optional)
            </label>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="deal_title" value="Deal title" />
                    <x-text-input id="deal_title" name="deal_title" class="mt-1 block w-full" :value="old('deal_title')" placeholder="Defaults to company name — first deal" />
                </div>
                <div>
                    <x-input-label for="deal_value" value="Deal value (PKR)" />
                    <x-text-input id="deal_value" name="deal_value" type="number" min="0" step="0.01" class="mt-1 block w-full" :value="old('deal_value')" />
                    <x-input-error :messages="$errors->get('deal_value')" class="mt-2" />
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-500">The deal starts in the first pipeline stage and is owned by the lead's assignee.</p>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-500">Convert lead</button>
            <a href="{{ route('leads.show', $lead) }}" class="text-sm text-slate-500 hover:underline">Cancel</a>
        </div>
        <p class="text-xs text-slate-500">Conversion is atomic: either everything is created/linked and the lead is marked converted, or nothing changes at all.</p>
    </form>
</x-app-layout>
