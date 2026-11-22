@extends('layouts.app')
@section('title', 'Buat Garasi')

@section('content')
<div class="mx-auto max-w-xl">
    <h1 class="mb-1 text-2xl font-bold">Buat Garasi Baru</h1>
    <p class="mb-6 text-slate-500">Kode garasi dibuat otomatis melanjutkan nomor terakhir: S001 (sepeda), C001 (mobil), E001 (mobil listrik).</p>

    <form method="POST" action="{{ route('admin.garages.store') }}" class="card space-y-4">
        @csrf
        <div>
            <label class="label" for="type">Tipe garasi</label>
            <select class="input" id="type" name="type" required>
                @foreach ($garageTypes as $type)
                    <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }} (kode {{ $type->codePrefix() }}xxx)</option>
                @endforeach
            </select>
            @error('type') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="count">Jumlah garasi (maks. 999)</label>
            <input class="input" id="count" name="count" type="number" min="1" max="999" value="{{ old('count', 1) }}" required>
            @error('count') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="label" for="daily_price">Harga sewa harian (Rp)</label>
            <input class="input" id="daily_price" name="daily_price" type="number" min="1" value="{{ old('daily_price') }}" required>
            <p class="mt-1 text-sm text-slate-500">Harga bulanan otomatis 25 × harga harian.</p>
            @error('daily_price') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div id="kwh-field">
            <label class="label" for="kwh_price">Harga isi listrik per kWh (Rp)</label>
            <input class="input" id="kwh_price" name="kwh_price" type="number" min="1" value="{{ old('kwh_price') }}">
            @error('kwh_price') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div class="flex gap-3">
            <button class="btn btn-primary">Buat Garasi</button>
            <a href="{{ route('admin.garages.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // The price per kWh only applies to electric-car garages.
    const typeSelect = document.getElementById('type');
    const kwhField = document.getElementById('kwh-field');
    const toggleKwh = () => {
        const electric = typeSelect.value === 'electric';
        kwhField.classList.toggle('hidden', !electric);
        kwhField.querySelector('input').disabled = !electric;
        kwhField.querySelector('input').required = electric;
    };
    typeSelect.addEventListener('change', toggleKwh);
    toggleKwh();
</script>
@endpush
