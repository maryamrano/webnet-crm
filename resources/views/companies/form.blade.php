<form method="POST" action="{{ $action }}" class="space-y-5">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <x-input-label for="name" value="Company name *" />
            <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $company->name)" required />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="industry" value="Industry" />
            <x-text-input id="industry" name="industry" class="mt-1 block w-full" :value="old('industry', $company->industry)" />
        </div>
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $company->email)" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="phone" value="Phone" />
            <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $company->phone)" />
        </div>
        <div>
            <x-input-label for="website" value="Website" />
            <x-text-input id="website" name="website" class="mt-1 block w-full" :value="old('website', $company->website)" />
        </div>
        <div>
            <x-input-label for="city" value="City" />
            <x-text-input id="city" name="city" class="mt-1 block w-full" :value="old('city', $company->city)" />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="address" value="Address" />
            <x-text-input id="address" name="address" class="mt-1 block w-full" :value="old('address', $company->address)" />
        </div>
        <div>
            <x-input-label for="owner_id" value="Owner" />
            <select id="owner_id" name="owner_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">— Unassigned —</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected((string) old('owner_id', $company->owner_id) === (string) $u->id)>{{ $u->name }} ({{ $u->roleLabel() }})</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('owner_id')" class="mt-2" />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $company->exists ? 'Save changes' : 'Create company' }}</x-primary-button>
        <a href="{{ $company->exists ? route('companies.show', $company) : route('companies.index') }}" class="text-sm text-slate-500 hover:underline">Cancel</a>
    </div>
</form>
