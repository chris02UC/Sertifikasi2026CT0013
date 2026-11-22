@extends('layouts.app')
@section('title', 'Perpanjang Sewa')

@section('content')
<h1 class="mb-1 text-2xl font-bold">Perpanjang Sewa Garasi</h1>
<p class="mb-6 text-slate-500">Garasi yang sedang disewa hari ini ({{ $today->translatedFormat('d F Y') }}).
    Penyewa mengajukan perpanjangan di luar sistem (mis. WhatsApp), admin yang menginput.</p>

<div class="card overflow-x-auto p-0">
    <table class="table">
        <thead><tr><th>Garasi</th><th>Penyewa</th><th>Kendaraan</th><th>Periode</th><th class="text-right">Harga Harian</th><th></th></tr></thead>
        <tbody>
            @forelse ($rentals as $rental)
                <tr>
                    <td><span class="font-semibold">{{ $rental->garage->code }}</span><br><span class="text-slate-500">{{ $rental->garage->type->label() }}</span></td>
                    <td>{{ $rental->vehicle->owner->name }}<br><span class="text-slate-500">{{ $rental->vehicle->owner->phone }}</span></td>
                    <td>{{ $rental->vehicle->displayName() }}</td>
                    <td>{{ $rental->start_date->translatedFormat('d M Y') }} – <b>{{ $rental->end_date->translatedFormat('d M Y') }}</b></td>
                    <td class="text-right">@rupiah($rental->daily_price)</td>
                    <td class="text-right"><a href="{{ route('admin.extensions.edit', $rental) }}" class="btn btn-primary">Perpanjang</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-8 text-center text-slate-500">Tidak ada garasi yang sedang disewa hari ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
