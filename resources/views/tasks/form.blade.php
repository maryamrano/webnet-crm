<form method="POST" action="{{ $action }}" class="space-y-5" x-data="{ pkey: '{{ old('taskable_key', $parentKey ?? '') }}' }">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-input-label for="title" value="Task title *" />
        <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title', $task->title)" required />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $task->description) }}</textarea>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <x-input-label for="taskable_key" value="Linked record type (optional)" />
            <select id="taskable_key" name="taskable_key" x-model="pkey" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">— No linked record —</option>
                <option value="lead" @selected(old('taskable_key', $parentKey ?? '') === 'lead')>Lead</option>
                <option value="company" @selected(old('taskable_key', $parentKey ?? '') === 'company')>Company</option>
                <option value="contact" @selected(old('taskable_key', $parentKey ?? '') === 'contact')>Contact</option>
                <option value="deal" @selected(old('taskable_key', $parentKey ?? '') === 'deal')>Deal</option>
            </select>
            <x-input-error :messages="$errors->get('taskable_key')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="taskable_id" value="Linked record" />
            <select id="taskable_id" name="taskable_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">— Select —</option>
                @foreach ($parents as $key => $records)
                    @foreach ($records as $record)
                        <option value="{{ $record->id }}" x-show="pkey == '{{ $key }}'" @selected((string) old('taskable_id', $task->taskable_id) === (string) $record->id && old('taskable_key', $parentKey ?? '') === $key)>{{ $record->name ?? $record->title }}</option>
                    @endforeach
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('taskable_id')" class="mt-2" />
            <p class="mt-1 text-xs text-slate-500">Only records you can access are listed — the server enforces this too.</p>
        </div>
        <div>
            <x-input-label for="assigned_to" value="Assigned to" />
            <select id="assigned_to" name="assigned_to" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">— Unassigned —</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected((string) old('assigned_to', $task->assigned_to) === (string) $u->id)>{{ $u->name }} ({{ $u->roleLabel() }})</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('assigned_to')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="priority" value="Priority *" />
            <select id="priority" name="priority" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach (\App\Models\Task::PRIORITIES as $priority)
                    <option value="{{ $priority }}" @selected(old('priority', $task->priority) === $priority)>{{ str($priority)->title() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="due_at" value="Due date & time" />
            <x-text-input id="due_at" name="due_at" type="datetime-local" class="mt-1 block w-full" :value="old('due_at', $task->due_at?->format('Y-m-d\TH:i'))" />
            <x-input-error :messages="$errors->get('due_at')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="reminder_at" value="Reminder date & time" />
            <x-text-input id="reminder_at" name="reminder_at" type="datetime-local" class="mt-1 block w-full" :value="old('reminder_at', $task->reminder_at?->format('Y-m-d\TH:i'))" />
            <x-input-error :messages="$errors->get('reminder_at')" class="mt-2" />
            <p class="mt-1 text-xs text-slate-500">In-app only: the task appears on the Reminders page once this time is reached.</p>
        </div>
        <div>
            <x-input-label for="status" value="Status *" />
            <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach (\App\Models\Task::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('status', $task->status) === $status)>{{ str($status)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <x-primary-button>{{ $task->exists ? 'Save changes' : 'Create task' }}</x-primary-button>
        <a href="{{ $task->exists ? route('tasks.show', $task) : route('tasks.index') }}" class="text-sm text-slate-500 hover:underline">Cancel</a>
    </div>
</form>
