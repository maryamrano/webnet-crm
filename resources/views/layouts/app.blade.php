<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'WebNet CRM') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-indigo-600 focus:px-4 focus:py-2 focus:text-white">Skip to main content</a>
        <div class="min-h-screen bg-slate-100 lg:flex" x-data="{ sidebarOpen: false }">

            {{-- Mobile overlay --}}
            <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" x-cloak></div>

            {{-- Sidebar --}}
            <aside id="app-sidebar" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                   class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-900 text-slate-200 transition-transform duration-200 lg:static lg:translate-x-0">
                <div class="flex items-center gap-3 px-5 h-16 border-b border-white/10">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-500 font-bold text-white shadow-sm ring-1 ring-white/25">W</div>
                    <div>
                        <p class="text-[15px] font-semibold leading-tight tracking-tight text-white">WebNet CRM</p>
                        <p class="text-xs text-slate-400 leading-tight">Sales workspace</p>
                    </div>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4 text-sm">
                    <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Menu</p>
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <svg aria-hidden="true" class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg> Dashboard
                    </a>

                    <a href="{{ route('leads.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors {{ request()->routeIs('leads.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <svg aria-hidden="true" class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="10" cy="8" r="3.4"/><path stroke-linecap="round" d="M4.6 19.2c.6-3 2.8-4.6 5.4-4.6 1.5 0 2.9.5 3.9 1.5M18.6 8.2v5.4M15.9 10.9h5.4"/></svg> Leads
                    </a>

                    <a href="{{ route('companies.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors {{ request()->routeIs('companies.*') || request()->routeIs('contacts.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <svg aria-hidden="true" class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="5" y="3.5" width="14" height="17" rx="1"/><path stroke-linecap="round" d="M9 7.5h1.6M13.4 7.5H15M9 11h1.6M13.4 11H15M9 14.5h1.6M13.4 14.5H15M10.2 20.5v-2.8h3.6v2.8"/></svg> Companies
                    </a>

                    <a href="{{ route('pipeline.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors {{ request()->routeIs('pipeline.*') || request()->routeIs('deals.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <svg aria-hidden="true" class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="3.5" y="4" width="4.6" height="13" rx="1"/><rect x="9.7" y="4" width="4.6" height="8.5" rx="1"/><rect x="15.9" y="4" width="4.6" height="10.5" rx="1"/></svg> Pipeline
                    </a>
                    <a href="{{ route('tasks.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors {{ request()->routeIs('tasks.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <svg aria-hidden="true" class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.6 12.4l2.4 2.4 4.4-5"/></svg> Tasks
                    </a>

                    <a href="{{ route('reports.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors {{ request()->routeIs('reports.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <svg aria-hidden="true" class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 4v15.5h16"/><path stroke-linecap="round" d="M8.5 15.5v-4M12.5 15.5V8M16.5 15.5v-6.5"/></svg> Reports
                    </a>

                    @if (auth()->user()?->isAdmin())
                        <p class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Administration</p>
                        <a href="{{ route('admin.users.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors {{ request()->routeIs('admin.users.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <svg aria-hidden="true" class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="9" cy="8.2" r="3.2"/><path stroke-linecap="round" d="M3.8 19.4c.6-3 2.7-4.7 5.2-4.7 1.9 0 3.5.9 4.5 2.5M16.3 5.5a3.2 3.2 0 1 1 1 6.2M17.8 14.9c1.4.6 2.3 1.9 2.6 3.7"/></svg> Users
                    </a>
                        <a href="{{ route('admin.audit-logs.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition-colors {{ request()->routeIs('admin.audit-logs.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        <svg aria-hidden="true" class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><rect x="6" y="4.5" width="12" height="16.5" rx="2"/><path stroke-linecap="round" d="M9.5 10h5M9.5 13.5h5M9.5 17h2.8"/><rect x="9.6" y="2.8" width="4.8" height="3.2" rx="1"/></svg> Audit log
                    </a>
                    @endif
                </nav>

                <div class="border-t border-white/10 px-5 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">{{ str(auth()->user()?->name ?? '?')->substr(0, 1)->upper() }}</div>
                        <div class="min-w-0">
                            <p class="truncate font-medium leading-tight text-white">{{ auth()->user()?->name }}</p>
                            <p class="truncate text-xs leading-tight text-slate-400">{{ auth()->user()?->roleLabel() }} · {{ auth()->user()?->email }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex gap-4 text-sm">
                        <a href="{{ route('profile.edit') }}" class="inline-block py-1 text-slate-300 hover:text-white">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="py-1 text-slate-300 hover:text-white">Log out</button>
                        </form>
                    </div>
                </div>
            </aside>

            {{-- Main column --}}
            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 shadow-sm sm:px-6">
                    <button @click="sidebarOpen = true" class="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Open menu" :aria-expanded="sidebarOpen" aria-controls="app-sidebar">☰</button>
                    @isset($header)
                        <div class="min-w-0 flex-1">{{ $header }}</div>
                    @endisset
                </header>

                @if (session('status'))
                    <div role="status" class="mx-4 mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 sm:mx-6">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div role="alert" class="mx-4 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 sm:mx-6">{{ session('error') }}</div>
                @endif

                <main id="main-content" class="flex-1 px-4 py-6 sm:px-6">
                    {{ $slot }}
                </main>

                <footer class="mx-4 border-t border-slate-200 px-4 py-5 text-center text-xs text-slate-500 sm:mx-6 sm:px-6">
                    WebNet CRM · Laravel 13 · Built for the WebNet Pakistan assignment
                </footer>
            </div>
        </div>
    </body>
</html>
