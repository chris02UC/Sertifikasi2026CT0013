@extends('layouts.app')
@section('title', 'Verifikasi Pendaftar')

@section('content')
<h1 class="mb-1 text-2xl font-bold">Verifikasi Pendaftar Baru</h1>
<p class="mb-6 text-slate-500">Periksa data KTP pendaftar secara manual, lalu terima atau tolak.</p>

<div class="card mb-8 overflow-x-auto p-0">
    <table class="table">
        <thead><tr><th>NIK</th><th>Nama</th><th>Telepon</th><th>Email</th><th>Tanggal Daftar</th><th class="text-right">Aksi</th></tr></thead>
        <tbody>
            @forelse ($pending as $tenant)
                <tr>
                    <td class="font-mono">{{ $tenant->nik }}</td>
                    <td>{{ $tenant->name }}</td>
                    <td>{{ $tenant->phone }}</td>
                    <td>{{ $tenant->email }}</td>
                    <td>{{ $tenant->created_at->translatedFormat('d M Y H:i') }}</td>
                    <td class="text-right whitespace-nowrap">
                        <form method="POST" action="{{ route('admin.tenants.approve', $tenant) }}" class="inline">
                            @csrf @method('PATCH')
                            <button class="btn btn-primary">Terima</button>
                        </form>
                        <form method="POST" action="{{ route('admin.tenants.reject', $tenant) }}" class="inline"
                              onsubmit="return confirm(@js('Tolak pendaftaran '.$tenant->name.'?'))">
                            @csrf @method('PATCH')
                            <button class="btn btn-danger">Tolak</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-8 text-center text-slate-500">Tidak ada pendaftar yang menunggu verifikasi.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 class="mb-3 text-lg font-semibold">Riwayat Verifikasi</h2>
<div class="card overflow-x-auto p-0">
    <table class="table">
        <thead><tr><th>NIK</th><th>Nama</th><th>Email</th><th>Status</th><th>Diverifikasi</th></tr></thead>
        <tbody>
            @forelse ($processed as $tenant)
                <tr>
                    <td class="font-mono">{{ $tenant->nik }}</td>
                    <td>{{ $tenant->name }}</td>
                    <td>{{ $tenant->email }}</td>
                    <td><span class="badge {{ $tenant->isApproved() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $tenant->status->label() }}</span></td>
                    <td>{{ $tenant->verified_at?->translatedFormat('d M Y H:i') ?? '–' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-6 text-center text-slate-500">Belum ada.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
