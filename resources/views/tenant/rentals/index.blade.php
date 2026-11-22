@extends('layouts.app')
@section('title', 'Sewa Saya')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold">Sewa Saya</h1>
        <p class="text-slate-500">Untuk perpanjangan sewa, hubungi admin (mis. via WhatsApp).</p>
    </div>
    <a href="{{ route('rentals.create') }}" class="btn btn-primary">+ Sewa Garasi Baru</a>
</div>

<div class="card overflow-x-auto p-0">
    <table class="table">
        <thead>
            <tr>
                <th>Garasi</th><th>Kendaraan</th><th>Tipe Sewa</th><th>Periode</th>
                <th class="text-right">Total Biaya</th><th>Status</th><th>Bukti</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rentals as $rental)
                @php
                    $status = $rental->end_date->lt($today) ? ['Selesai', 'bg-slate-200 text-slate-700']
                        : ($rental->start_date->gt($today) ? ['Akan datang', 'bg-amber-100 text-amber-800']
                        : ['Aktif', 'bg-green-100 text-green-800']);
                    $extra = $rental->extensions->sum('amount_due');
                @endphp
                <tr>
                    <td><span class="font-semibold">{{ $rental->garage->code }}</span><br><span class="text-slate-500">{{ $rental->garage->type->label() }}</span></td>
                    <td>{{ $rental->vehicle->displayName() }}</td>
                    <td>{{ $rental->rental_type->label() }}</td>
                    <td>
                        {{ $rental->start_date->translatedFormat('d M Y') }} – {{ $rental->end_date->translatedFormat('d M Y') }}
                        <br><span class="text-slate-500">{{ $rental->duration_days }} hari
                        @if ($rental->extensions->isNotEmpty()) (diperpanjang {{ $rental->extensions->count() }}x) @endif</span>
                    </td>
                    <td class="text-right">
                        @rupiah($rental->total_cost)
                        @if ($rental->charging_cost > 0)<br><span class="text-xs text-slate-500">termasuk isi listrik @rupiah($rental->charging_cost)</span>@endif
                        @if ($extra > 0)<br><span class="text-xs text-slate-500">+ perpanjangan @rupiah($extra)</span>@endif
                    </td>
                    <td><span class="badge {{ $status[1] }}">{{ $status[0] }}</span></td>
                    <td><a class="text-teal-700 hover:underline" href="{{ asset('storage/'.$rental->payment_proof_path) }}" target="_blank">Lihat</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-8 text-center text-slate-500">Belum ada sewa. Klik "Sewa Garasi Baru" untuk mulai.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
