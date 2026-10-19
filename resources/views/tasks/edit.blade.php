<x-app-layout>
    <x-slot name="header">
        <h1 class="min-w-0 truncate text-xl font-bold tracking-tight text-slate-900">Edit task — {{ $task->title }}</h1>
    </x-slot>

    <div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @include('tasks.form', ['task' => $task, 'users' => $users, 'parents' => $parents, 'parentKey' => $parentKey, 'action' => route('tasks.update', $task), 'method' => 'PUT'])
    </div>
</x-app-layout>
