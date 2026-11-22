<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sewa Garasi') · Sewa Garasi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    @auth
        <nav class="bg-teal-800 text-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
                <a href="{{ route('home') }}" class="text-lg font-bold">Sewa Garasi</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.garages.index') }}" class="hover:underline">Garasi</a>
                    <a href="{{ route('admin.tenants.index') }}" class="hover:underline">Verifikasi Pendaftar</a>
                    <a href="{{ route('admin.extensions.index') }}" class="hover:underline">Perpanjang Sewa</a>
                    <a href="{{ route('admin.reports.index') }}" class="hover:underline">Laporan</a>
                @else
                    <a href="{{ route('rentals.index') }}" class="hover:underline">Sewa Saya</a>
                    <a href="{{ route('rentals.create') }}" class="hover:underline">Sewa Garasi Baru</a>
                @endif
                <div class="ml-auto flex items-center gap-3 text-sm">
                    <span>{{ auth()->user()->name }} ({{ auth()->user()->role->label() }})</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded bg-white/15 px-3 py-1 hover:bg-white/25">Logout</button>
                    </form>
                </div>
            </div>
        </nav>
    @endauth

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('success'))
            <div class="mb-6 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
        @endif
        @if ($errors->hasAny(['rental', 'tenant', 'garage']))
            <div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800">
                {{ $errors->first('rental') ?: ($errors->first('tenant') ?: $errors->first('garage')) }}
            </div>
        @endif

        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
