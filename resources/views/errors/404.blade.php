<x-guest-layout>
    <div class="text-center">
        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">404</p>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-800">Page not found</h1>
        <p class="mt-3 text-sm text-slate-500">The page you are looking for doesn't exist or may have been moved.</p>
        @auth
            <a href="{{ route('dashboard') }}" class="mt-6 inline-flex items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Back to dashboard</a>
        @else
            <a href="{{ route('login') }}" class="mt-6 inline-flex items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Go to sign in</a>
        @endauth
    </div>
</x-guest-layout>
