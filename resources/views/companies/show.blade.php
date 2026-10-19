<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Company — {{ $company->name }}</h1>
    </x-slot>

    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <dl class="grid gap-4 text-sm sm:grid-cols-3">
                <div><dt class="text-slate-500">Industry</dt><dd class="mt-0.5 font-medium">{{ $company->industry ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">City</dt><dd class="mt-0.5 font-medium">{{ $company->city ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Owner</dt><dd class="mt-0.5 font-medium">{{ $company->owner?->name ?? 'Unassigned' }}</dd></div>
                <div><dt class="text-slate-500">Email</dt><dd class="mt-0.5 font-medium">{{ $company->email ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Phone</dt><dd class="mt-0.5 font-medium">{{ $company->phone ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Website</dt><dd class="mt-0.5 font-medium">{{ $company->website ?? '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-slate-500">Address</dt><dd class="mt-0.5 font-medium">{{ $company->address ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Created by</dt><dd class="mt-0.5 font-medium">{{ $company->creator?->name ?? '—' }}</dd></div>
            </dl>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('companies.edit', $company) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500 hover:shadow active:bg-indigo-700">Edit company</a>
                @can('delete', $company)
                    <form method="POST" action="{{ route('companies.destroy', $company) }}" onsubmit="return confirm('Delete this company? Only possible if it has no linked records.')">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Delete</button>
                    </form>
                @endcan
                <a href="{{ route('companies.index') }}" class="rounded-lg px-4 py-2 text-sm text-slate-500 hover:underline">Back to companies</a>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h4 class="font-semibold text-slate-800">Contacts ({{ $company->contacts->count() }})</h4>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($company->contacts as $contact)
                    <a href="{{ route('contacts.show', $contact) }}" class="rounded-xl border border-slate-100 bg-slate-50 p-4 transition hover:border-indigo-200">
                        <p class="font-medium text-slate-800">{{ $contact->name }} @if ($contact->is_primary)<span class="ml-1 rounded-full bg-indigo-100 px-2 py-0.5 text-xs text-indigo-700">Primary</span>@endif</p>
                        <p class="text-sm text-slate-500">{{ $contact->designation ?? '' }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $contact->email ?? '' }}<br>{{ $contact->phone ?? '' }}</p>
                    </a>
                @endforeach
            </div>

            @can('update', $company)
                <form method="POST" action="{{ route('contacts.store', $company) }}" class="mt-5 space-y-3 rounded-xl bg-slate-50 p-4">
                    @csrf
                    <p class="text-sm font-medium text-slate-700">Add a contact</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input type="text" name="name" required placeholder="Full name *" class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="text" name="designation" placeholder="Designation" class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="email" name="email" placeholder="Email" class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <input type="text" name="phone" placeholder="Phone" class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="is_primary" value="1" class="rounded border-slate-300"> Primary contact
                    </label>
                    <x-input-error :messages="$errors->get('name')" />
                    <x-input-error :messages="$errors->get('email')" />
                    <button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Add contact</button>
                </form>
            @endcan
        </div>

        @if ($company->deals->isNotEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h4 class="font-semibold text-slate-800">Deals</h4>
                <ul class="mt-3 divide-y divide-slate-100 text-sm">
                    @foreach ($company->deals as $deal)
                        <li class="flex items-center justify-between py-2">
                            <span>{{ $deal->title }}</span>
                            <span class="text-slate-500">{{ $deal->stage->name }} · PKR {{ number_format((float) $deal->value) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('activities.section', ['record' => $company])
    </div>
</x-app-layout>
