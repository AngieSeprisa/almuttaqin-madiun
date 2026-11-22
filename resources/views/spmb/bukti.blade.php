@extends('spmb.layout')

@section('title', 'Bukti Pendaftaran - ' . $pendaftaran->no_induk)

@section('content')
<div class="spmb-card" style="width:100%;">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; padding:16px 18px; border-radius:12px; background:#EFF6FF; border:1px solid #BFDBFE; margin-bottom:20px;">
        <div>
            <div style="font-size:11px; font-weight:700; color:#1E40AF; text-transform:uppercase; letter-spacing:0.05em;">Nomor Registrasi</div>
            <div style="font-size:22px; font-weight:800; color:#0052FF; font-family:monospace; margin-top:2px; letter-spacing:0.02em;">{{ $pendaftaran->no_induk }}</div>
        </div>
        <span class="status-pill status-{{ \Illuminate\Support\Str::slug($pendaftaran->status_pendaftaran) }}" style="font-size:12.5px; padding:6px 14px;">
            {{ $pendaftaran->status_pendaftaran }}
        </span>
    </div>

    <div class="detail-grid">
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
        @if(!empty($lokasiSeleksi))
        <div class="detail-item">
            <span class="detail-label">Lokasi Seleksi</span>
            <span class="detail-value">{{ $lokasiSeleksi }}</span>
        </div>
        @endif
    </div>

    <div class="spmb-actions-stack" style="display:flex; gap:10px; flex-wrap:wrap; margin-top:22px; padding-top:18px; border-top:1px solid #E2E8F0;">
        <a href="{{ route('spmb.cetak', ['slug' => $penerimaan->slug, 'token' => $pendaftaran->public_token]) }}" target="_blank" class="btn btn-primary">
            Cetak Bukti &amp; Formulir (2 Hal)
        </a>
        @if($waConfirmUrl)
            <a href="{{ $waConfirmUrl }}" target="_blank" class="btn btn-success">
                Konfirmasi WA Panitia {{ $pendaftaran->jenjang }}
            </a>
        @endif
        <a href="{{ route('spmb.cek-status', ['slug' => $penerimaan->slug, 'kode' => $pendaftaran->no_induk]) }}" class="btn btn-secondary">
            Cek Status
        </a>
    </div>
</div>
@endsection
