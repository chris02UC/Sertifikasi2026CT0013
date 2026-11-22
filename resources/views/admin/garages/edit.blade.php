@extends('layouts.app')
@section('title', 'Ubah Garasi '.$garage->code)

@section('content')
<div class="mx-auto max-w-xl">
    <h1 class="mb-1 text-2xl font-bold">Ubah Garasi {{ $garage->code }}</h1>
    <p class="mb-6 text-slate-500">Kode dan tipe garasi tidak bisa diubah. Harga baru berlaku untuk sewa berikutnya; sewa yang sudah ada tetap memakai harganya sendiri.</p>

    <form method="POST" action="{{ route('admin.garages.update', $garage) }}" class="card space-y-4">
        @csrf @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="code">Kode</label>
                <input class="input" id="code" value="{{ $garage->code }}" disabled>
            </div>
            <div>
                <label class="label" for="type">Tipe</label>
                <input class="input" id="type" value="{{ $garage->type->label() }}" disabled>
            </div>
        </div>
        <div>
            <label class="label" for="daily_price">Harga sewa harian (Rp)</label>
            <input class="input" id="daily_price" name="daily_price" type="number" min="1" value="{{ old('daily_price', $garage->daily_price) }}" required>
            <p class="mt-1 text-sm text-slate-500">Harga bulanan otomatis 25 × harga harian.</p>
            @error('daily_price') <p class="error">{{ $message }}</p> @enderror
        </div>
        @if ($garage->isElectric())
            <div>
                <label class="label" for="kwh_price">Harga isi listrik per kWh (Rp)</label>
                <input class="input" id="kwh_price" name="kwh_price" type="number" min="1" value="{{ old('kwh_price', $garage->kwh_price) }}" required>
                @error('kwh_price') <p class="error">{{ $message }}</p> @enderror
            </div>
        @endif
        <div class="flex gap-3">
            <button class="btn btn-primary">Simpan</button>
            <a href="{{ route('admin.garages.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
