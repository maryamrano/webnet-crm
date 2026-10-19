<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Edit user — {{ $user->name }}</h1>
    </x-slot>

    <div class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @include('admin.users.form', [
            'user' => $user,
            'roles' => $roles,
            'action' => route('admin.users.update', $user),
            'method' => 'PUT',
        ])
    </div>
</x-app-layout>
