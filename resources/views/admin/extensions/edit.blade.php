@extends('layouts.app')
@section('title', 'Perpanjang Sewa '.$rental->garage->code)

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <a href="{{ route('admin.extensions.index') }}" class="text-sm text-teal-700 hover:underline">← Kembali</a>
        <h1 class="mt-2 text-2xl font-bold">Perpanjang Sewa Garasi {{ $rental->garage->code }}</h1>
    </div>

    <div class="card grid gap-2 text-sm sm:grid-cols-2">
        <p><span class="text-slate-500">Penyewa:</span> {{ $rental->vehicle->owner->name }} ({{ $rental->vehicle->owner->phone }})</p>
        <p><span class="text-slate-500">Kendaraan:</span> {{ $rental->vehicle->displayName() }}</p>
        <p><span class="text-slate-500">Mulai sewa:</span> {{ $rental->start_date->translatedFormat('d F Y') }}</p>
        <p><span class="text-slate-500">Selesai sewa saat ini:</span> <b>{{ $rental->end_date->translatedFormat('d F Y') }}</b></p>
        <p><span class="text-slate-500">Harga harian:</span> @rupiah($rental->daily_price)</p>
        <p><span class="text-slate-500">Harga bulanan:</span> @rupiah($rental->daily_price * \App\Services\PricingService::MONTHLY_MULTIPLIER)</p>
    </div>

    {{-- Step 1: change the end date; the app calculates the amount due --}}
    <form method="GET" action="{{ route('admin.extensions.edit', $rental) }}" class="card space-y-3">
        <label class="label" for="new_end_date_check">Tanggal selesai sewa baru</label>
        <div class="flex gap-3">
            <input class="input" id="new_end_date_check" type="date" name="new_end_date"
                   min="{{ $rental->end_date->copy()->addDay()->toDateString() }}" value="{{ $newEnd }}" required>
            <button class="btn btn-secondary whitespace-nowrap">Hitung Sisa Biaya</button>
        </div>
        @if ($error) <p class="error">{{ $error }}</p> @endif
        @if ($amountDue !== null)
            <div class="rounded-lg bg-teal-50 p-4">
                <p class="text-sm">Tambahan {{ $rental->end_date->diffInDays(\Carbon\Carbon::parse($newEnd)) }} hari
                    ({{ $rental->end_date->copy()->addDay()->translatedFormat('d M Y') }} – {{ \Carbon\Carbon::parse($newEnd)->translatedFormat('d M Y') }})</p>
                <p class="text-lg font-bold">Sisa biaya yang harus dibayar: @rupiah($amountDue)</p>
                <p class="text-xs text-slate-500">Setiap bulan kalender penuh dihitung 25 × harga harian, sisa hari dihitung harian.</p>
            </div>
        @endif
    </form>

    {{-- Step 2: upload proof of payment and save --}}
    @if ($amountDue !== null)
        <form method="POST" action="{{ route('admin.extensions.update', $rental) }}" enctype="multipart/form-data" class="card space-y-4">
            @csrf @method('PUT')
            <input type="hidden" name="new_end_date" value="{{ $newEnd }}">
            <div>
                <label class="label">Upload bukti bayar</label>
                <input class="input" type="file" name="payment_proof" accept="image/png,image/jpeg" required>
                @error('payment_proof') <p class="error">{{ $message }}</p> @enderror
                @error('new_end_date') <p class="error">{{ $message }}</p> @enderror
            </div>
            <button class="btn btn-primary">Simpan Perpanjangan</button>
        </form>
    @endif

    @if ($rental->extensions->isNotEmpty())
        <div class="card p-0">
            <h2 class="px-4 pt-4 font-semibold">Riwayat Perpanjangan</h2>
            <table class="table mt-2">
                <thead><tr><th>Dari</th><th>Menjadi</th><th>Hari</th><th class="text-right">Dibayar</th><th>Admin</th></tr></thead>
                <tbody>
                    @foreach ($rental->extensions as $ext)
                        <tr>
                            <td>{{ $ext->old_end_date->translatedFormat('d M Y') }}</td>
                            <td>{{ $ext->new_end_date->translatedFormat('d M Y') }}</td>
                            <td>{{ $ext->added_days }}</td>
                            <td class="text-right">@rupiah($ext->amount_due)</td>
                            <td>{{ $ext->admin->name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
