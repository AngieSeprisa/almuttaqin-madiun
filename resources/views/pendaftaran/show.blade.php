@extends('layouts.app')

@section('title', 'Verifikasi Pendaftaran: ' . $pendaftaran->nama_lengkap)

@section('header-actions')
<div style="display:flex; gap:10px; align-items:center;">
    <a href="{{ route('pendaftaran.index') }}" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
            <line x1="19" y1="12" x2="5" y2="12"></line>
            <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Kembali
    </a>
    @if(!auth()->check() || auth()->user()->canDo('pendaftaran', 'edit'))
    <a href="{{ route('pendaftaran.edit', $pendaftaran->id) }}" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
        </svg>
        Edit Formulir
    </a>
    @endif
    <a href="{{ route('pendaftaran.cetak', $pendaftaran->id) }}" target="_blank" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        Cetak / Unduh Formulir
    </a>
</div>
@endsection

@section('content')
<div class="form-container">

    {{-- Feedback Banner --}}
    @if (session('success'))
    <div class="alert-banner alert-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:22px;height:22px;flex-shrink:0;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    {{-- Header Status & Verifikasi Card --}}
    <div class="form-section-card" style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:20px; background:linear-gradient(135deg, var(--card-bg) 0%, var(--bg-surface) 100%);">
        <div>
            <div style="font-size:12px; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <span>Nomor Induk: {{ $pendaftaran->no_induk ?? '-' }}</span>
                <span>•</span>
                <span>NIK: {{ $pendaftaran->nik }}</span>
                @if($pendaftaran->nisn)
                    <span>•</span>
                    <span style="color:var(--electric-blue); font-weight:800;">NISN: {{ $pendaftaran->nisn }}</span>
                @endif
            </div>
            <div style="display:flex; align-items:center; gap:10px; margin:4px 0 8px 0; flex-wrap:wrap;">
                <h1 style="font-size:22px; font-weight:800; color:var(--text-primary); margin:0;">
                    {{ $pendaftaran->nama_lengkap }}
                </h1>
                @php
                    $jenjangClass = match($pendaftaran->jenjang ?? 'Reguler') {
                        'SMP' => 'jenjang-pill-smp',
                        'SMA' => 'jenjang-pill-sma',
                        default => 'jenjang-pill-reguler',
                    };
                @endphp
                <span class="jenjang-pill {{ $jenjangClass }}" style="font-size:12px; padding:4px 10px;">
                    Jenjang: {{ $pendaftaran->jenjang ?? 'Reguler' }}
                </span>
                @if($pendaftaran->penerima_kip)
                    <span class="badge-kip" style="font-size:11px; padding:3px 8px;">
                        Penerima KIP / PIP
                    </span>
                @endif
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <span class="status-pill status-{{ strtolower($pendaftaran->status_pendaftaran) }}" style="font-size:13px; padding:6px 14px;">
                    Status: {{ $pendaftaran->status_pendaftaran }}
                </span>
                <span style="font-size:13px; color:var(--text-secondary);">
                    Terdaftar pada {{ $pendaftaran->created_at ? $pendaftaran->created_at->format('d F Y, H:i') : '-' }} WIB
                </span>
            </div>
        </div>

        {{-- Form Ubah Status --}}
        <div style="display:flex; flex-direction:column; gap:8px;">
            <span style="font-size:12px; font-weight:700; color:var(--text-secondary);">Tindakan Verifikasi:</span>
            <form action="{{ route('pendaftaran.updateStatus', $pendaftaran->id) }}" method="POST" style="display:flex; gap:8px;">
                @csrf
                @method('PATCH')

                @if($pendaftaran->status_pendaftaran !== 'Diterima')
                <button type="button" name="status_pendaftaran" value="Diterima" class="btn btn-success js-swal-confirm"
                    data-title="Terima Calon Santri?"
                    data-html="Konfirmasi penerimaan santri <strong>{{ $pendaftaran->nama_lengkap }}</strong>.<br><span style='font-size:13px; color:var(--text-muted);'>Status pendaftaran akan diubah menjadi <strong>Diterima</strong>.</span>"
                    data-icon="question"
                    data-confirm-text="Ya, Terima Santri"
                    data-confirm-color="#10B981"
                    data-cancel-text="Batal"
                    style="padding:8px 16px; font-size:13px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Terima Santri
                </button>
                @endif

                @if($pendaftaran->status_pendaftaran !== 'Ditolak')
                <button type="button" name="status_pendaftaran" value="Ditolak" class="btn btn-danger js-swal-confirm"
                    data-title="Tolak Pendaftaran?"
                    data-html="Apakah Anda yakin ingin menolak pendaftaran calon santri <strong>{{ $pendaftaran->nama_lengkap }}</strong>?<br><span style='font-size:13px; color:var(--text-muted);'>Status pendaftaran akan diubah menjadi <strong>Ditolak</strong>.</span>"
                    data-icon="warning"
                    data-confirm-text="Ya, Tolak"
                    data-confirm-color="#DC2626"
                    data-cancel-text="Batal"
                    style="padding:8px 16px; font-size:13px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Tolak
                </button>
                @endif

                @if($pendaftaran->status_pendaftaran !== 'Pending')
                <button type="submit" name="status_pendaftaran" value="Pending" class="btn btn-secondary" style="padding:8px 16px; font-size:13px;">
                    Kembalikan ke Pending
                </button>
                @endif
            </form>
        </div>
    </div>

    {{-- SEKSI 1: IDENTITAS PRIBADI --}}
    <div class="form-section-card">
        <div class="form-section-header">
            <div class="form-section-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <div class="form-section-title-wrap">
                <h2 class="form-section-title">1. Identitas Calon Santri & Asal Sekolah</h2>
            </div>
        </div>

        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Jenjang Pendidikan Dipilih</span>
                <span class="detail-value">
                    <span class="jenjang-pill {{ $jenjangClass }}">{{ $pendaftaran->jenjang ?? 'Reguler' }}</span>
                </span>
            </div>
            <div class="detail-item">
                <span class="detail-label">NISN (Nomor Induk Siswa Nasional)</span>
                <span class="detail-value" style="font-family:monospace; font-weight:700;">{{ $pendaftaran->nisn ?? '-' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Nama Lengkap</span>
                <span class="detail-value">{{ $pendaftaran->nama_lengkap }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Nama Panggilan</span>
                <span class="detail-value">{{ $pendaftaran->nama_panggilan }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Tempat & Tanggal Lahir</span>
                <span class="detail-value">{{ $pendaftaran->tempat_lahir }}, {{ $pendaftaran->tanggal_lahir ? $pendaftaran->tanggal_lahir->format('d F Y') : '-' }} ({{ $pendaftaran->tanggal_lahir ? $pendaftaran->tanggal_lahir->age . ' Tahun' : '-' }})</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Jenis Kelamin</span>
                <span class="detail-value">{{ $pendaftaran->jenis_kelamin }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Urutan Kelahiran</span>
                <span class="detail-value">Anak ke-{{ $pendaftaran->anak_ke }} dari {{ $pendaftaran->jumlah_saudara }} bersaudara</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Tinggal Bersama</span>
                <span class="detail-value">{{ $pendaftaran->tinggal_bersama }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Mulai Sambung Pengajian</span>
                <span class="detail-value">{{ $pendaftaran->mulai_pengajian ?? '-' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Status Mondok</span>
                <span class="detail-value">{{ $pendaftaran->status_mondok }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Pendidikan Terakhir</span>
                <span class="detail-value">{{ $pendaftaran->pendidikan_terakhir }} {{ $pendaftaran->jurusan ? '(' . $pendaftaran->jurusan . ')' : '' }} {{ $pendaftaran->tahun_lulus ? '- Lulus ' . $pendaftaran->tahun_lulus : '' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">
                    @if(($pendaftaran->jenjang ?? '') === 'SMP')
                        Nama SD / MI Asal
                    @elseif(($pendaftaran->jenjang ?? '') === 'SMA')
                        Nama SMP / MTs Asal
                    @else
                        Nama Sekolah Asal
                    @endif
                </span>
                <span class="detail-value" style="font-weight:700;">{{ $pendaftaran->nama_sekolah_asal ?? '-' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Penanggung Jawab Biaya</span>
                <span class="detail-value">{{ $pendaftaran->pj_biaya_nama }} ({{ $pendaftaran->pj_biaya_hp }})</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Bantuan Sosial (KIP / PIP / PKH)</span>
                <span class="detail-value">
                    @if($pendaftaran->penerima_kip)
                        <span class="badge-kip" style="margin-right:6px;">Penerima KIP / PIP</span>
                        <span>Nomor: <strong style="font-family:monospace;">{{ $pendaftaran->nomor_kip ?? '-' }}</strong></span>
                    @else
                        <span style="color:var(--text-secondary);">Bukan Penerima Bantuan</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- SEKSI 2: DOMISILI & SAMBUNG --}}
    <div class="form-section-card">
        <div class="form-section-header">
            <div class="form-section-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
            </div>
            <div class="form-section-title-wrap">
                <h2 class="form-section-title">2. Domisili & Struktur Sambung Pengajian</h2>
            </div>
        </div>

        <div class="detail-grid">
            <div class="detail-item" style="grid-column: 1 / -1;">
                <span class="detail-label">Alamat Lengkap</span>
                <span class="detail-value">{{ $pendaftaran->alamat_jalan }}, RT/RW {{ $pendaftaran->rt_rw }}, Desa/Kel. {{ $pendaftaran->desa_kelurahan }}, Kec. {{ $pendaftaran->kecamatan }}, {{ $pendaftaran->kab_kota }}, {{ $pendaftaran->provinsi }} (Kode Pos: {{ $pendaftaran->kode_pos }})</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Kewarganegaraan</span>
                <span class="detail-value">{{ $pendaftaran->warga_negara }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Struktur Sambung Pengajian</span>
                <span class="detail-value">Kelompok: {{ $pendaftaran->kelompok }} | Desa: {{ $pendaftaran->desa }} | Daerah: {{ $pendaftaran->daerah }}</span>
            </div>
        </div>
    </div>

    {{-- SEKSI 3: KESEHATAN & FISIK --}}
    <div class="form-section-card">
        <div class="form-section-header">
            <div class="form-section-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                </svg>
            </div>
            <div class="form-section-title-wrap">
                <h2 class="form-section-title">3. Riwayat Kesehatan & Fisik</h2>
            </div>
        </div>

        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Penyakit yang Pernah/Sedang Diderita</span>
                <span class="detail-value">{{ $pendaftaran->penyakit_diderita ?? 'Tidak Ada' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Alergi Makanan / Obat</span>
                <span class="detail-value">{{ $pendaftaran->alergi ?? 'Tidak Ada' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Golongan Darah</span>
                <span class="detail-value">{{ $pendaftaran->gol_darah ?? '-' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Tinggi & Berat Badan</span>
                <span class="detail-value">{{ $pendaftaran->tinggi_badan ? $pendaftaran->tinggi_badan . ' cm' : '-' }} / {{ $pendaftaran->berat_badan ? $pendaftaran->berat_badan . ' kg' : '-' }}</span>
            </div>
            <div class="detail-item" style="grid-column: 1 / -1;">
                <span class="detail-label">Keahlian Khusus & Prestasi</span>
                <span class="detail-value">{{ $pendaftaran->keahlian_prestasi ?? '-' }}</span>
            </div>
        </div>
    </div>

    {{-- SEKSI 4: BIODATA ORANG TUA --}}
    <div class="form-section-card">
        <div class="form-section-header">
            <div class="form-section-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                </svg>
            </div>
            <div class="form-section-title-wrap">
                <h2 class="form-section-title">4. Biodata Orang Tua (Ayah & Ibu)</h2>
            </div>
        </div>

        <div class="parents-container">
            {{-- Data Ayah --}}
            <div class="parent-subcard">
                <div class="parent-subcard-title">
                    Ayah: {{ $pendaftaran->ayah_nama }} ({{ $pendaftaran->ayah_status }})
                </div>
                <div class="detail-item">
                    <span class="detail-label">TTL Ayah</span>
                    <span class="detail-value">{{ $pendaftaran->ayah_ttl ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Pendidikan & Pekerjaan</span>
                    <span class="detail-value">{{ $pendaftaran->ayah_pendidikan ?? '-' }} • {{ $pendaftaran->ayah_pekerjaan ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Penghasilan</span>
                    <span class="detail-value">{{ $pendaftaran->ayah_penghasilan ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">No. HP</span>
                    <span class="detail-value">{{ $pendaftaran->ayah_hp ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Alamat</span>
                    <span class="detail-value">{{ $pendaftaran->ayah_alamat ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Sambung Pengajian Ayah</span>
                    <span class="detail-value">{{ $pendaftaran->ayah_kelompok ?? '-' }} / {{ $pendaftaran->ayah_desa ?? '-' }} / {{ $pendaftaran->ayah_daerah ?? '-' }}</span>
                </div>
            </div>

            {{-- Data Ibu --}}
            <div class="parent-subcard">
                <div class="parent-subcard-title">
                    Ibu: {{ $pendaftaran->ibu_nama }} ({{ $pendaftaran->ibu_status }})
                </div>
                <div class="detail-item">
                    <span class="detail-label">TTL Ibu</span>
                    <span class="detail-value">{{ $pendaftaran->ibu_ttl ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Pendidikan & Pekerjaan</span>
                    <span class="detail-value">{{ $pendaftaran->ibu_pendidikan ?? '-' }} • {{ $pendaftaran->ibu_pekerjaan ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Penghasilan</span>
                    <span class="detail-value">{{ $pendaftaran->ibu_penghasilan ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">No. HP</span>
                    <span class="detail-value">{{ $pendaftaran->ibu_hp ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Alamat</span>
                    <span class="detail-value">{{ $pendaftaran->ibu_alamat ?? '-' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Sambung Pengajian Ibu</span>
                    <span class="detail-value">{{ $pendaftaran->ibu_kelompok ?? '-' }} / {{ $pendaftaran->ibu_desa ?? '-' }} / {{ $pendaftaran->ibu_daerah ?? '-' }}</span>
                </div>
            </div>
        </div>

        {{-- Checklist Berkas --}}
        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-light);">
            <div class="detail-label" style="margin-bottom: 12px;">Kelengkapan Dokumen / Berkas Fisik:</div>
            <div class="form-grid form-grid-4">
                <div style="display:flex; align-items:center; gap:8px;">
                    @if($pendaftaran->berkas_akta)
                        <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" style="width:20px;height:20px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span style="font-size:13px; font-weight:600; color:#059669;">Akta Kelahiran</span>
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" style="width:20px;height:20px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        <span style="font-size:13px; color:var(--text-muted);">Akta Kelahiran (Belum)</span>
                    @endif
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                    @if($pendaftaran->berkas_kk)
                        <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" style="width:20px;height:20px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span style="font-size:13px; font-weight:600; color:#059669;">Kartu Keluarga (KK)</span>
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" style="width:20px;height:20px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        <span style="font-size:13px; color:var(--text-muted);">Kartu Keluarga (Belum)</span>
                    @endif
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                    @if($pendaftaran->berkas_ktp_ortu)
                        <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" style="width:20px;height:20px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span style="font-size:13px; font-weight:600; color:#059669;">KTP Orang Tua</span>
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" style="width:20px;height:20px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        <span style="font-size:13px; color:var(--text-muted);">KTP Orang Tua (Belum)</span>
                    @endif
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                    @if($pendaftaran->berkas_surat_pernyataan)
                        <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" style="width:20px;height:20px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span style="font-size:13px; font-weight:600; color:#059669;">Surat Pernyataan</span>
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" style="width:20px;height:20px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        <span style="font-size:13px; color:var(--text-muted);">Surat Pernyataan (Belum)</span>
                    @endif
                </div>
            </div>

            @if (collect(array_keys(\App\Models\Pendaftaran::FOTO_BERKAS))->contains(fn ($f) => $pendaftaran->{$f}))
            <div class="detail-label" style="margin: 20px 0 12px;">Foto Berkas:</div>
            <div class="berkas-photo-grid">
                @foreach (\App\Models\Pendaftaran::FOTO_BERKAS as $field => [$flag, $label])
                    <div class="berkas-photo-item">
                        @if ($pendaftaran->{$field})
                            <a href="{{ $pendaftaran->fotoUrl($field) }}" target="_blank" rel="noopener" class="berkas-photo-thumb" title="Buka ukuran penuh">
                                <img src="{{ $pendaftaran->fotoUrl($field) }}" alt="Foto {{ $label }}" loading="lazy">
                            </a>
                        @else
                            <div class="berkas-photo-empty">Belum diunggah</div>
                        @endif
                        <span style="font-size:12px; font-weight:600; color:var(--text-secondary);">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>

</div>
@endsection
