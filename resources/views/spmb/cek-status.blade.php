@extends('spmb.layout')

@section('title', 'Cek Status - ' . $penerimaan->judul)

@section('content')
<div class="spmb-card" style="max-width:640px; margin:0 auto;">
    <h1 style="font-size:17px; font-weight:800; color:#0F172A; margin:0 0 14px 0;">Cek Status Pendaftaran</h1>

    <form action="{{ route('spmb.cek-status', $penerimaan->slug) }}" method="GET" style="display:flex; gap:10px; flex-wrap:wrap;">
        <input type="text" name="kode" value="{{ $kode }}" class="wizard-input" style="flex:1; min-width:200px;"
               placeholder="Nomor Registrasi atau NIK" required>
        <button type="submit" class="btn btn-primary" style="min-height:46px; padding:0 22px; border-radius:11px;">Cari</button>
    </form>

    @if($searched)
        <div style="margin-top:20px; padding-top:18px; border-top:1px solid #E2E8F0;">
            @if($pendaftaran)
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; padding:14px 16px; border-radius:12px; background:#F8FAFC; border:1px solid #E2E8F0; margin-bottom:16px;">
                    <div>
                        <div style="font-size:12px; color:#0052FF; font-family:monospace; font-weight:700;">{{ $pendaftaran->no_induk }}</div>
                        <div style="font-size:17px; font-weight:800; color:#0F172A; margin-top:2px;">{{ $pendaftaran->nama_lengkap }}</div>
                    </div>
                    <span class="status-pill status-{{ \Illuminate\Support\Str::slug($pendaftaran->status_pendaftaran) }}" style="font-size:12.5px; padding:6px 14px;">
                        {{ $pendaftaran->status_pendaftaran }}
                    </span>
                </div>

                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Jenjang</span>
                        <span class="detail-value">{{ $pendaftaran->jenjang ?? '-' }}</span>
                    </div>
                    @if($pendaftaran->gelombang)
                    <div class="detail-item">
                        <span class="detail-label">Gelombang</span>
                        <span class="detail-value">{{ $pendaftaran->gelombang->nama_gelombang }}</span>
                    </div>
                    @endif
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

                @if($pendaftaran->public_token)
                <div class="spmb-actions-stack" style="margin-top:18px;">
                    <a href="{{ route('spmb.bukti', ['slug' => $penerimaan->slug, 'token' => $pendaftaran->public_token]) }}" class="btn btn-primary">
                        Lihat Bukti Pendaftaran
                    </a>
                </div>
                @endif
            @else
                <div class="alert-banner alert-error" style="margin-bottom:0; border-radius:12px;">
                    <div>Data tidak ditemukan.</div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
