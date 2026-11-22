@extends('layouts.app')
@section('title', 'Garasi')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold">Daftar Garasi</h1>
    <a href="{{ route('admin.garages.create') }}" class="btn btn-primary">+ Buat Garasi</a>
</div>

<div class="card overflow-x-auto p-0">
    <table class="table">
        <thead><tr><th>Kode</th><th>Tipe</th><th class="text-right">Harga Harian</th><th class="text-right">Harga Bulanan (25×)</th><th class="text-right">Listrik / kWh</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
        <tbody>
            @forelse ($garages as $garage)
                <tr>
                    <td class="font-semibold">{{ $garage->code }}</td>
                    <td>{{ $garage->type->label() }}</td>
                    <td class="text-right">@rupiah($garage->daily_price)</td>
                    <td class="text-right">@rupiah($garage->daily_price * \App\Services\PricingService::MONTHLY_MULTIPLIER)</td>
                    <td class="text-right">@if ($garage->kwh_price) @rupiah($garage->kwh_price) @else – @endif</td>
                    <td>
                        <span class="badge {{ $garage->status === \App\Enums\GarageStatus::USED ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800' }}">
                            {{ $garage->status->label() }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap text-right">
                        <a href="{{ route('admin.garages.edit', $garage) }}" class="font-semibold text-teal-700 hover:underline">Ubah</a>
                        <form method="POST" action="{{ route('admin.garages.destroy', $garage) }}" class="ml-3 inline"
                              onsubmit="return confirm(@js('Hapus garasi '.$garage->code.'?'))">
                            @csrf @method('DELETE')
                            <button class="font-semibold text-red-600 hover:underline">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-8 text-center text-slate-500">Belum ada garasi.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $garages->links() }}</div>
@endsection
