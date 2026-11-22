@extends('layouts.app')
@section('title', 'Sewa Garasi Baru')

@section('content')
<h1 class="mb-1 text-2xl font-bold">Sewa Garasi Baru</h1>
<p class="mb-6 text-slate-500">Isi data kendaraan dan sewa, cek garasi tersedia, lalu upload bukti pelunasan.</p>

<form id="rental-form" method="POST" action="{{ route('rentals.store') }}" enctype="multipart/form-data" class="space-y-6"
      data-quote-url="{{ route('rentals.quote') }}"
      data-allowed='@json(collect($vehicleTypes)->mapWithKeys(fn ($t) => [$t->value => array_map(fn ($g) => $g->value, $t->allowedGarageTypes())]))'>
    @csrf

    {{-- 1. Vehicle --}}
    <section class="card space-y-4">
        <h2 class="text-lg font-semibold">1. Detail Kendaraan</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="vehicle_type">Tipe kendaraan</label>
                <select class="input" id="vehicle_type" name="vehicle_type">
                    <option value="">— Pilih —</option>
                    @foreach ($vehicleTypes as $type)
                        <option value="{{ $type->value }}" @selected(old('vehicle_type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('vehicle_type') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div data-show-for="plate">
                <label class="label" for="plate_number">Nomor plat</label>
                <input class="input uppercase" id="plate_number" name="plate_number" value="{{ old('plate_number') }}" placeholder="L 1234 AB" maxlength="15">
                @error('plate_number') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="brand_model">Merek dan tipe</label>
                <input class="input" id="brand_model" name="brand_model" value="{{ old('brand_model') }}" placeholder="Honda Beat / Toyota Avanza" maxlength="100">
                @error('brand_model') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div data-show-for="battery">
                <label class="label" for="battery_kwh">Kapasitas listrik (kWh)</label>
                <input class="input" id="battery_kwh" name="battery_kwh" type="number" step="0.01" min="1" value="{{ old('battery_kwh') }}">
                @error('battery_kwh') <p class="error">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- 2. Rental details --}}
    <section class="card space-y-4">
        <h2 class="text-lg font-semibold">2. Detail Sewa</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="label" for="rental_type">Tipe sewa</label>
                <select class="input" id="rental_type" name="rental_type" required>
                    @foreach ($rentalTypes as $type)
                        <option value="{{ $type->value }}" @selected(old('rental_type', 'daily') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('rental_type') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div data-show-for="daily">
                <label class="label" for="days">Jumlah hari (maks. <span id="max-days">{{ $maxDailyDays }}</span>)</label>
                <input class="input" id="days" name="days" type="number" min="1" max="{{ $maxDailyDays }}" value="{{ old('days', 1) }}">
                @error('days') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="start_date">Tanggal mulai sewa</label>
                <input class="input" id="start_date" name="start_date" type="date" min="{{ $today->toDateString() }}"
                       value="{{ old('start_date', $today->toDateString()) }}" required>
                <p class="mt-1 text-xs text-slate-500">Default hari ini; boleh tanggal mendatang.</p>
                @error('start_date') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="end_date">Tanggal selesai sewa</label>
                <input class="input" id="end_date" type="text" readonly placeholder="Otomatis">
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="garage_type">Tipe garasi (sesuai tipe kendaraan)</label>
                <select class="input" id="garage_type" name="garage_type" required>
                    @foreach ($garageTypes as $type)
                        <option value="{{ $type->value }}" @selected(old('garage_type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('garage_type') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-end">
                <button type="button" id="check-btn" class="btn btn-secondary w-full">Cek Garasi Tersedia &amp; Hitung Biaya</button>
            </div>
        </div>
        <p class="text-sm text-slate-500">Sewa bulanan = 1 bulan (jumlah hari mengikuti bulan tanggal mulai), harga 25 × harga harian.
            Mobil listrik di garasi mobil listrik wajib isi listrik penuh (kapasitas × harga per kWh).</p>
    </section>

    {{-- 3. Garage & cost --}}
    <section class="card space-y-4">
        <h2 class="text-lg font-semibold">3. Pilih Garasi</h2>
        <p id="quote-message" class="text-sm text-slate-500">Klik "Cek Garasi Tersedia" untuk melihat garasi yang kosong pada periode sewa.</p>
        <div id="garage-list" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"></div>
        @error('garage_id') <p class="error">{{ $message }}</p> @enderror
        <div id="cost-summary" class="hidden rounded-lg bg-teal-50 p-4">
            <div class="flex justify-between text-sm"><span>Biaya sewa</span><span id="cost-rent"></span></div>
            <div class="flex justify-between text-sm"><span>Biaya isi listrik</span><span id="cost-charging"></span></div>
            <div class="mt-2 flex justify-between border-t border-teal-200 pt-2 text-lg font-bold"><span>Total biaya sewa</span><span id="cost-total"></span></div>
        </div>
    </section>

    {{-- 4. Proof of payment --}}
    <section class="card space-y-4">
        <h2 class="text-lg font-semibold">4. Bukti Pelunasan Biaya</h2>
        <input class="input" type="file" name="payment_proof" accept="image/png,image/jpeg" required>
        @error('payment_proof') <p class="error">{{ $message }}</p> @enderror
        <button id="submit-btn" class="btn btn-primary" disabled>Simpan Sewa</button>
    </section>
</form>
@endsection

@push('scripts')
    @vite('resources/js/rental-form.js')
@endpush
