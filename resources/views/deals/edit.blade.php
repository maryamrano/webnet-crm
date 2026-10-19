<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Edit deal — {{ $deal->title }}</h1>
    </x-slot>

    <div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @include('deals.form', ['deal' => $deal, 'companies' => $companies, 'stages' => $stages, 'users' => $users, 'action' => route('deals.update', $deal), 'method' => 'PUT'])
    </div>
</x-app-layout>
