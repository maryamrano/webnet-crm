<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Add company</h1>
    </x-slot>

    <div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @include('companies.form', ['company' => $company, 'users' => $users, 'action' => route('companies.store'), 'method' => 'POST'])
    </div>
</x-app-layout>
