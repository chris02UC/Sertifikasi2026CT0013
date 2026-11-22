@extends('layouts.app')
@section('title', 'Daftar Penyewa')

@section('content')
<div class="mx-auto mt-6 max-w-lg">
    <h1 class="mb-1 text-2xl font-bold">Daftar sebagai Penyewa</h1>
    <p class="mb-6 text-slate-500">Akun akan aktif setelah admin memverifikasi data KTP Anda.</p>

    <form method="POST" action="{{ route('register') }}" class="card space-y-4">
        @csrf
        @php
            $fields = [
                ['nik', 'NIK (Nomor KTP)', 'text', '16 digit angka'],
                ['name', 'Nama lengkap', 'text', 'Sesuai KTP'],
                ['phone', 'Nomor telepon', 'tel', 'Contoh: 081234567890'],
                ['email', 'Email (dipakai sebagai username)', 'email', 'nama@email.com'],
            ];
        @endphp
        @foreach ($fields as [$name, $label, $type, $placeholder])
            <div>
                <label class="label" for="{{ $name }}">{{ $label }}</label>
                <input class="input" id="{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ old($name) }}"
                       placeholder="{{ $placeholder }}" @if ($name === 'nik') inputmode="numeric" maxlength="16" @endif required>
                @error($name) <p class="error">{{ $message }}</p> @enderror
            </div>
        @endforeach
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="password">Password</label>
                <input class="input" id="password" type="password" name="password" minlength="8" required>
                @error('password') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password_confirmation">Ulangi password</label>
                <input class="input" id="password_confirmation" type="password" name="password_confirmation" required>
            </div>
        </div>
        <button class="btn btn-primary w-full">Daftar</button>
        <p class="text-center text-sm text-slate-600">
            Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-teal-700 hover:underline">Login</a>
        </p>
    </form>
</div>
@endsection
