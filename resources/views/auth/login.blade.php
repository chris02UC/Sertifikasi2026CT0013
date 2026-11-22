@extends('layouts.app')
@section('title', 'Login')

@section('content')
<div class="mx-auto mt-10 max-w-md">
    <h1 class="mb-1 text-center text-3xl font-bold text-teal-800">Sewa Garasi</h1>
    <p class="mb-6 text-center text-slate-500">Masuk sebagai admin atau penyewa</p>

    <form method="POST" action="{{ route('login') }}" class="card space-y-4">
        @csrf
        <div>
            <label class="label" for="email">Email</label>
            <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="password">Password</label>
            <input class="input" id="password" type="password" name="password" required>
            @error('password') <p class="error">{{ $message }}</p> @enderror
        </div>
        <button class="btn btn-primary w-full">Login</button>
        <p class="text-center text-sm text-slate-600">
            Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-teal-700 hover:underline">Daftar sebagai penyewa</a>
        </p>
    </form>
</div>
@endsection
