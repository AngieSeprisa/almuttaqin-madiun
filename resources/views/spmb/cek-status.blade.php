@extends('spmb.layout')

@section('title', 'Cek Status - ' . $penerimaan->judul)

@section('content')
<div class="spmb-card" style="max-width:640px; margin:0 auto;">
    <h1 style="font-size:18px; font-weight:800; color:#0F172A; margin:0 0 16px 0;">Cek Status Pendaftaran</h1>

    <form action="{{ route('spmb.cek-status', $penerimaan->slug) }}" method="GET" style="display:flex; gap:10px; flex-wrap:wrap;">
        <input type="text" name="kode" value="{{ $kode }}" class="wizard-input" style="flex:1; min-width:220px;"
               placeholder="Nomor Registrasi atau NIK" required>
        <button type="submit" class="btn btn-primary">Cari</button>
    </form>

    @if($searched)
        <div style="margin-top:22px; padding-top:18px; border-top:1px solid #E5E7EB;">
            @if($pendaftaran)
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
                    <div>
                        <div style="font-size:12px; color:#64748B; font-family:monospace;">{{ $pendaftaran->no_induk }}</div>
                        <div style="font-size:18px; font-weight:800; color:#0F172A;">{{ $pendaftaran->nama_lengkap }}</div>
                    </div>
                    <span class="status-pill status-{{ \Illuminate\Support\Str::slug($pendaftaran->status_pendaftaran) }}" style="font-size:13px; padding:6px 14px;">
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
                <div style="margin-top:18px;">
                    <a href="{{ route('spmb.bukti', ['slug' => $penerimaan->slug, 'token' => $pendaftaran->public_token]) }}" class="btn btn-secondary btn-sm">
                        Lihat Bukti Pendaftaran
                    </a>
                </div>
                @endif
            @else
                <div class="alert-banner alert-error" style="margin-bottom:0;">
                    <div>Data tidak ditemukan.</div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
