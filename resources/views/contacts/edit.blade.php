<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Edit contact — {{ $contact->name }}</h1>
    </x-slot>

    <div class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('contacts.update', $contact) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <x-input-label for="name" value="Full name *" />
                <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $contact->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="designation" value="Designation" />
                <x-text-input id="designation" name="designation" class="mt-1 block w-full" :value="old('designation', $contact->designation)" />
            </div>
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $contact->email)" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="phone" value="Phone" />
                <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $contact->phone)" />
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="is_primary" value="1" class="rounded border-slate-300" @checked(old('is_primary', $contact->is_primary))> Primary contact for {{ $contact->company->name }}
            </label>
            <div class="flex items-center gap-3">
                <x-primary-button>Save changes</x-primary-button>
                <a href="{{ route('contacts.show', $contact) }}" class="text-sm text-slate-500 hover:underline">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
