@extends('layouts.app')
@section('title', 'Laporan Sewa')

@php
    $tabs = ['monthly' => 'Per Bulan', 'daily' => 'Per Hari', 'type' => 'Per Tipe Garasi', 'tenant' => 'Per Penyewa'];
    $headings = ['monthly' => "Bulan (tahun $year)", 'daily' => 'Tanggal', 'type' => 'Tipe Garasi', 'tenant' => 'Penyewa'];
@endphp

@section('content')
<h1 class="mb-1 text-2xl font-bold">Laporan Sewa Garasi</h1>
<p class="mb-6 text-slate-500">Dikelompokkan berdasarkan tanggal mulai sewa. Pendapatan = biaya sewa + isi listrik + perpanjangan.</p>

<div class="mb-4 flex flex-wrap gap-2">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.reports.index', ['view' => $key, 'year' => $year, 'month' => $month]) }}"
           class="btn {{ $view === $key ? 'btn-primary' : 'btn-secondary' }}">{{ $label }}</a>
    @endforeach
</div>

@if (in_array($view, ['monthly', 'daily']))
    <form method="GET" class="card mb-6 flex flex-wrap items-end gap-3">
        <input type="hidden" name="view" value="{{ $view }}">
        <div>
            <label class="label" for="year">Tahun</label>
            <input class="input w-28" id="year" type="number" name="year" value="{{ $year }}" min="2000" max="2100">
        </div>
        @if ($view === 'daily')
            <div>
                <label class="label" for="month">Bulan</label>
                <select class="input" id="month" name="month">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" @selected($m === $month)>{{ \Carbon\Carbon::create(2000, $m, 1)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <button class="btn btn-primary">Tampilkan</button>
    </form>
@endif

<div class="grid gap-6 lg:grid-cols-5">
    <div class="card lg:col-span-3">
        <canvas id="report-chart" height="140"></canvas>
    </div>
    <div class="card overflow-x-auto p-0 lg:col-span-2">
        <table class="table">
            <thead><tr><th>{{ $headings[$view] }}</th><th class="text-right">Jumlah Sewa</th><th class="text-right">Pendapatan</th></tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="{{ $row['rentals'] === 0 ? 'text-slate-400' : '' }}">
                        <td>{{ $row['label'] }}</td>
                        <td class="text-right">{{ $row['rentals'] }}</td>
                        <td class="text-right">@rupiah($row['revenue'])</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-6 text-center text-slate-500">Belum ada data.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="font-bold"><td class="px-3 py-2">Total</td><td class="px-3 py-2 text-right">{{ $rows->sum('rentals') }}</td><td class="px-3 py-2 text-right">@rupiah($rows->sum('revenue'))</td></tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

@push('scripts')
{{-- Chart.js 4.4.1 (MIT) from the jsDelivr CDN: third-party library for the report chart. Docs: https://www.chartjs.org/docs/ --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('report-chart'), {
        type: 'bar',
        data: {
            labels: @json($rows->pluck('label')),
            datasets: [{ label: 'Pendapatan (Rp)', data: @json($rows->pluck('revenue')), backgroundColor: '#0f766e' }],
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: (v) => 'Rp ' + Number(v).toLocaleString('id-ID') } } },
        },
    });
</script>
@endpush
