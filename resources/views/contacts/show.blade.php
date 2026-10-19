<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Contact — {{ $contact->name }}</h1>
    </x-slot>

    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <dl class="grid gap-4 text-sm sm:grid-cols-3">
                <div><dt class="text-slate-500">Company</dt><dd class="mt-0.5 font-medium"><a class="text-indigo-600 hover:underline" href="{{ route('companies.show', $contact->company) }}">{{ $contact->company->name }}</a></dd></div>
                <div><dt class="text-slate-500">Designation</dt><dd class="mt-0.5 font-medium">{{ $contact->designation ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Primary contact</dt><dd class="mt-0.5 font-medium">{{ $contact->is_primary ? 'Yes' : 'No' }}</dd></div>
                <div><dt class="text-slate-500">Email</dt><dd class="mt-0.5 font-medium">{{ $contact->email ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Phone</dt><dd class="mt-0.5 font-medium">{{ $contact->phone ?? '—' }}</dd></div>
            </dl>
            <div class="mt-6 flex flex-wrap gap-3">
                @can('update', $contact)
                    <a href="{{ route('contacts.edit', $contact) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500 hover:shadow active:bg-indigo-700">Edit contact</a>
                @endcan
                @can('delete', $contact)
                    <form method="POST" action="{{ route('contacts.destroy', $contact) }}" onsubmit="return confirm('Delete this contact?')">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50">Delete</button>
                    </form>
                @endcan
            </div>
        </div>

        @include('activities.section', ['record' => $contact])
    </div>
</x-app-layout>
