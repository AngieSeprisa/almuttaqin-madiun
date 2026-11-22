@extends('spmb.layout')

@section('title', 'Bukti Pendaftaran - ' . $pendaftaran->no_induk)

@section('content')
<div class="spmb-card" style="max-width:700px; margin:0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:14px; flex-wrap:wrap; padding-bottom:18px; border-bottom:1px solid #E5E7EB;">
        <div>
            <div style="font-size:12px; font-weight:700; color:#64748B; text-transform:uppercase; letter-spacing:0.05em;">Nomor Registrasi</div>
            <div style="font-size:24px; font-weight:800; color:#0052FF; font-family:monospace; margin-top:2px;">{{ $pendaftaran->no_induk }}</div>
        </div>
        <span class="status-pill status-{{ \Illuminate\Support\Str::slug($pendaftaran->status_pendaftaran) }}" style="font-size:13px; padding:6px 14px;">
            {{ $pendaftaran->status_pendaftaran }}
        </span>
    </div>

    <div class="detail-grid" style="margin-top:20px;">
        <div class="detail-item">
            <span class="detail-label">Nama Lengkap</span>
            <span class="detail-value">{{ $pendaftaran->nama_lengkap }}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Jenjang</span>
            <span class="detail-value">{{ $pendaftaran->jenjang ?? '-' }}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Jenis Kelamin</span>
            <span class="detail-value">{{ $pendaftaran->jenis_kelamin ?? '-' }}</span>
        </div>
        <div class="detail-item">
            <span class="detail-label">No. WhatsApp</span>
            <span class="detail-value">{{ $pendaftaran->pj_biaya_hp ?? '-' }}</span>
        </div>
        @if($pendaftaran->gelombang)
        <div class="detail-item">
            <span class="detail-label">Gelombang</span>
            <span class="detail-value">{{ $pendaftaran->gelombang->nama_gelombang }}</span>
        </div>
        @endif
        <div class="detail-item">
            <span class="detail-label">Tanggal Daftar</span>
            <span class="detail-value">{{ $pendaftaran->created_at?->translatedFormat('d F Y, H:i') }} WIB</span>
        </div>
        @if($penerimaan->jadwal_seleksi)
        <div class="detail-item">
            <span class="detail-label">Jadwal Seleksi</span>
            <span class="detail-value">{{ $penerimaan->jadwal_seleksi }}</span>
        </div>
        @endif
        @if($penerimaan->lokasi_seleksi)
        <div class="detail-item">
            <span class="detail-label">Lokasi Seleksi</span>
            <span class="detail-value">{{ $penerimaan->lokasi_seleksi }}</span>
        </div>
        @endif
    </div>

    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:24px; padding-top:18px; border-top:1px solid #E5E7EB;">
        <a href="{{ route('spmb.cetak', ['slug' => $penerimaan->slug, 'token' => $pendaftaran->public_token]) }}" target="_blank" class="btn btn-primary">
            Cetak Kartu Peserta
        </a>
        @if($waConfirmUrl)
            <a href="{{ $waConfirmUrl }}" target="_blank" class="btn btn-success">
                Konfirmasi WhatsApp
            </a>
        @endif
        <a href="{{ route('spmb.cek-status', ['slug' => $penerimaan->slug, 'kode' => $pendaftaran->no_induk]) }}" class="btn btn-secondary">
            Cek Status
        </a>
    </div>
</div>
@endsection
