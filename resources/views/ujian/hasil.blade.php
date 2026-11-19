@extends('layouts.app')

@section('title', 'Hasil & Analisis Ujian: ' . $ujian->nama_ujian)

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
    <a href="{{ route('ujian.export-excel', $ujian->id) }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px; background:#ecfdf5; border-color:#a7f3d0; color:#065f46;">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        <span>Ekspor Excel (.xlsx)</span>
    </a>

    <a href="{{ route('ujian.upload-scan', $ujian->id) }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px;">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
        </svg>
        <span>Koreksi LJK (Scan)</span>
    </a>

    <a href="{{ route('ujian.nilai') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px;">
        &larr; Rekap Nilai
    </a>
</div>
@endsection

@section('content')
<style>
    .hasil-container {
        width: 100%;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .stat-val {
        font-size: 22px;
        font-weight: 800;
        line-height: 1.1;
        color: #1e293b;
    }
    .stat-lbl {
        font-size: 12px;
        color: #64748b;
        margin-top: 3px;
    }

    /* Tabs Navigation */
    .tabs-nav {
        display: flex;
        gap: 8px;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 20px;
    }
    .tab-btn {
        padding: 10px 18px;
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: -2px;
        transition: all 0.15s ease;
    }
    .tab-btn:hover {
        color: #0284c7;
    }
    .tab-btn.active {
        color: #0284c7;
        border-bottom-color: #0284c7;
        background: #f0f9ff;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }
    .tab-pane {
        display: none;
    }
    .tab-pane.active {
        display: block;
    }

    .table-container {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .hasil-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .hasil-table th {
        background: #f8fafc;
        padding: 12px 14px;
        text-align: left;
        font-weight: 600;
        color: #475569;
        border-bottom: 1px solid #e2e8f0;
    }
    .hasil-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .hasil-table tr:hover {
        background: #fafafa;
    }

    /* Distribution badge */
    .dist-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        padding: 2px 6px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
    }
    .dist-badge.correct {
        background: #dcfce7;
        color: #166534;
        font-weight: 700;
    }

    /* Modal Koreksi Manual */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.55);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1050;
        padding: 20px;
    }
    .modal-card {
        background: #ffffff;
        border-radius: 14px;
        width: 100%;
        max-width: 840px;
        max-height: 88vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
    }
    .modal-card > form {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        max-height: 88vh;
        overflow: hidden;
        margin: 0;
    }
    .modal-head {
        padding: 16px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
        background: #ffffff;
    }
    .modal-body {
        padding: 18px 24px;
        overflow-y: auto;
        flex: 1 1 auto;
        min-height: 0;
    }
    .modal-foot {
        padding: 14px 24px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        background: #f8fafc;
        border-radius: 0 0 14px 14px;
        flex-shrink: 0;
    }

    .opt-circle-btn {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }
    .opt-circle-btn.selected {
        background: #0284c7;
        color: #ffffff;
        border-color: #0284c7;
    }
</style>

<div class="hasil-container">
    @if(session('success'))
        <div style="background:#ecfdf5; border-left:4px solid #10b981; color:#065f46; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:13px;">
            {{ session('success') }}
        </div>
    @endif

    @if(isset($ujians) && $ujians->count() > 1)
    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 18px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:10px;">
            <label for="selectUjianHasil" style="font-size:13px; font-weight:700; color:#334155; margin:0;">Pilih Ujian:</label>
            <select id="selectUjianHasil" class="form-control" style="font-size:13px; padding:6px 12px; border-radius:8px; border:1px solid #cbd5e1; min-width:280px;" onchange="window.location.href='{{ route('ujian.nilai') }}?ujian_id=' + this.value">
                @foreach($ujians as $u)
                    <option value="{{ $u->id }}" {{ $ujian->id == $u->id ? 'selected' : '' }}>
                        {{ $u->nama_ujian }} ({{ $u->kelas->nama_kelas ?? '-' }} - {{ $u->mapel->nama_mapel ?? '-' }})
                    </option>
                @endforeach
            </select>
        </div>
        <div style="font-size:12px; color:#64748b;">
            Menampilkan data untuk ujian <strong>{{ $ujian->nama_ujian }}</strong>
        </div>
    </div>
    @endif

    {{-- Info Card Ujian --}}
    <div style="background:linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color:#ffffff; border-radius:14px; padding:20px 24px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
            <div style="font-size:12px; color:#94a3b8; text-transform:uppercase; letter-spacing:0.5px;">Rekapitulasi Nilai & Analisis Soal</div>
            <h2 style="font-size:20px; font-weight:800; margin:4px 0 6px 0; color:#f8fafc;">{{ $ujian->nama_ujian }}</h2>
            <div style="font-size:13px; color:#cbd5e1; display:flex; gap:16px; flex-wrap:wrap;">
                <span><strong>Kelas:</strong> {{ $ujian->kelas->nama_kelas ?? '-' }}</span>
                <span><strong>Mata Pelajaran:</strong> {{ $ujian->mapel->nama_mapel ?? '-' }}</span>
                <span><strong>Guru:</strong> {{ $ujian->guru->nama_guru ?? '-' }}</span>
                <span><strong>Jumlah Soal:</strong> {{ $ujian->jumlah_soal }} Butir</span>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="{{ route('ujian.kunci', $ujian->id) }}" class="btn btn-secondary" style="background:rgba(255,255,255,0.1); border-color:rgba(255,255,255,0.2); color:#ffffff; font-size:12px;">
                Lihat Kunci ({{ $ujian->kuncis->count() }})
            </a>
            <a href="{{ route('ujian.cetak-ljk', $ujian->id) }}" target="_blank" class="btn btn-secondary" style="background:rgba(255,255,255,0.1); border-color:rgba(255,255,255,0.2); color:#ffffff; font-size:12px;">
                Cetak LJK PDF
            </a>
        </div>
    </div>

    {{-- Ringkasan Statistik --}}
    @php
        $stat = $ujian->statistik;
    @endphp
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div>
                <div class="stat-val">{{ $stat['total_peserta'] }}</div>
                <div class="stat-lbl">Total Siswa Terkoreksi</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
            </div>
            <div>
                <div class="stat-val">{{ number_format($stat['rata_rata'], 1) }}</div>
                <div class="stat-lbl">Rata-Rata Nilai</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#fefce8; color:#ca8a04;">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="7"></circle>
                    <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                </svg>
            </div>
            <div>
                <div class="stat-val" style="color:#16a34a;">{{ number_format($stat['nilai_tertinggi'], 1) }}</div>
                <div class="stat-lbl">Nilai Tertinggi</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#fff1f2; color:#e11d48;">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline>
                    <polyline points="17 18 23 18 23 12"></polyline>
                </svg>
            </div>
            <div>
                <div class="stat-val" style="color:#e11d48;">{{ number_format($stat['nilai_terendah'], 1) }}</div>
                <div class="stat-lbl">Nilai Terendah</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:{{ $stat['perlu_cek'] > 0 ? '#fff7ed' : '#f8fafc' }}; color:{{ $stat['perlu_cek'] > 0 ? '#ea580c' : '#94a3b8' }};">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>
            <div>
                <div class="stat-val" style="color:{{ $stat['perlu_cek'] > 0 ? '#ea580c' : '#64748b' }};">{{ $stat['perlu_cek'] }}</div>
                <div class="stat-lbl">Perlu Cek Manual</div>
            </div>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <div class="tabs-nav">
        <button type="button" class="tab-btn active" onclick="switchTab('tab-nilai', this)">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
            </svg>
            <span>Daftar Nilai Siswa ({{ $ujian->hasils->count() }})</span>
        </button>

        <button type="button" class="tab-btn" onclick="switchTab('tab-analisis', this)">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            <span>Analisis Butir Soal & Daya Pembeda</span>
        </button>
    </div>

    {{-- Tab 1: Nilai Siswa --}}
    <div id="tab-nilai" class="tab-pane active">
        <div class="table-container">
            <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                <div style="font-weight:700; color:#1e293b; font-size:15px;">Daftar Hasil Pengerjaan Siswa</div>
                <div style="font-size:12px; color:#64748b;">
                    Klik tombol <strong>Koreksi Manual</strong> untuk memeriksa atau mengubah jawaban siswa.
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table class="hasil-table">
                    <thead>
                        <tr>
                            <th style="width:45px; text-align:center;">No</th>
                            <th>NIS / NISN</th>
                            <th>Nama Siswa</th>
                            <th style="text-align:center;">Benar</th>
                            <th style="text-align:center;">Salah</th>
                            <th style="text-align:center;">Kosong</th>
                            <th style="text-align:center;">Nilai Akhir</th>
                            <th style="text-align:center;">Status</th>
                            <th>Waktu Scan</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ujian->hasils as $idx => $h)
                            <tr>
                                <td style="text-align:center; color:#64748b;">{{ $idx + 1 }}</td>
                                <td style="font-weight:600; font-family:monospace;">{{ $h->siswa->nisn ?? $h->siswa->nis ?? '-' }}</td>
                                <td style="font-weight:600; color:#1e293b;">{{ $h->siswa->nama_lengkap ?? 'Siswa ID ' . $h->siswa_id }}</td>
                                <td style="text-align:center; font-weight:700; color:#16a34a;">{{ $h->jumlah_benar }}</td>
                                <td style="text-align:center; font-weight:700; color:#dc2626;">{{ $h->jumlah_salah }}</td>
                                <td style="text-align:center; color:#64748b;">{{ $h->jumlah_kosong }}</td>
                                <td style="text-align:center;">
                                    <span style="font-size:16px; font-weight:800; color:{{ $h->nilai_akhir >= 75 ? '#16a34a' : ($h->nilai_akhir >= 60 ? '#ca8a04' : '#dc2626') }};">
                                        {{ number_format($h->nilai_akhir, 1) }}
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    @if($h->status_scan == 'perlu_cek_manual')
                                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:999px; font-size:11px; font-weight:700; background:#fff7ed; color:#ea580c; border:1px solid #fed7aa;" title="{{ $h->catatan_scan }}">
                                            Perlu Cek
                                        </span>
                                    @else
                                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:999px; font-size:11px; font-weight:700; background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0;">
                                            Valid
                                        </span>
                                    @endif
                                </td>
                                <td style="font-size:12px; color:#64748b;">{{ $h->waktu_scan ? $h->waktu_scan->format('d/m/Y H:i') : '-' }}</td>
                                <td style="text-align:center; white-space:nowrap;">
                                    <div style="display:inline-flex; gap:6px; align-items:center;">
                                        <button type="button" class="btn btn-secondary" style="padding:4px 10px; font-size:11px; border-radius:6px;" onclick="openEditModal({{ $h->id }}, {{ $h->siswa_id }}, '{{ addslashes($h->siswa->nama_lengkap ?? 'Siswa') }}', {{ json_encode($h->jawaban_siswa) }}, '{{ addslashes($h->catatan_scan ?? '') }}')">
                                            Koreksi Manual
                                        </button>
                                        <form method="POST" action="{{ route('ujian.destroy-hasil', [$ujian->id, $h->id]) }}" onsubmit="return confirm('Yakin ingin menghapus hasil scan {{ addslashes($h->siswa->nama_lengkap ?? 'siswa ini') }}?')" style="margin:0; display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" style="padding:4px 8px; font-size:11px; border-radius:6px; background:#fef2f2; border:1px solid #fecaca; color:#dc2626;" title="Hapus Hasil Scan">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" style="text-align:center; padding:40px 20px; color:#94a3b8;">
                                    Belum ada lembar LJK yang dikoreksi untuk ujian ini.<br>
                                    <a href="{{ route('ujian.upload-scan', $ujian->id) }}" class="btn btn-primary" style="margin-top:10px; display:inline-inline-flex; font-size:12px;">
                                        Unggah PDF Scan LJK
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab 2: Analisis Butir Soal --}}
    <div id="tab-analisis" class="tab-pane">
        @php
            $analisis = $ujian->analisis_soal;
            $soalSukar = collect($analisis)->where('tingkat_kesukaran', 'Sukar')->pluck('nomor_soal')->implode(', ');
            $soalMudah = collect($analisis)->where('tingkat_kesukaran', 'Mudah')->pluck('nomor_soal')->implode(', ');
        @endphp

        {{-- Highlight Card Soal Sukar & Mudah --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
            <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:16px 20px;">
                <div style="font-size:12px; font-weight:700; color:#991b1b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
                    Soal Paling Sukar (&lt; 35% Benar)
                </div>
                <div style="font-size:14px; color:#7f1d1d; font-weight:600;">
                    {{ $soalSukar ?: 'Tidak ada soal berkategori sukar.' }}
                </div>
                <div style="font-size:12px; color:#b91c1c; margin-top:4px;">
                    Nomor-nomor ini memerlukan review dan pembahasan lebih mendalam di kelas.
                </div>
            </div>

            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:16px 20px;">
                <div style="font-size:12px; font-weight:700; color:#166534; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
                    Soal Paling Mudah (&gt; 75% Benar)
                </div>
                <div style="font-size:14px; color:#14532d; font-weight:600;">
                    {{ $soalMudah ?: 'Tidak ada soal berkategori sangat mudah.' }}
                </div>
                <div style="font-size:12px; color:#15803d; margin-top:4px;">
                    Mayoritas siswa berhasil menjawab butir soal ini dengan benar.
                </div>
            </div>
        </div>

        <div class="table-container">
            <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                <div style="font-weight:700; color:#1e293b; font-size:15px;">Tabel Sebaran Pilihan Jawaban & Tingkat Kesukaran</div>
                <div style="font-size:12px; color:#64748b;">
                    Badge hijau pada kolom A-E menunjukkan kunci jawaban yang benar.
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table class="hasil-table">
                    <thead>
                        <tr>
                            <th style="width:50px; text-align:center;">No</th>
                            <th style="text-align:center; width:60px;">Kunci</th>
                            <th style="text-align:center; width:120px;">% Benar</th>
                            <th style="text-align:center; width:100px;">Kategori</th>
                            <th style="text-align:center; width:70px;">Benar</th>
                            <th style="text-align:center; width:70px;">Salah</th>
                            <th style="text-align:center;">Pilihan Siswa: A</th>
                            <th style="text-align:center;">B</th>
                            <th style="text-align:center;">C</th>
                            <th style="text-align:center;">D</th>
                            @if($ujian->jumlah_opsi == 5)
                                <th style="text-align:center;">E</th>
                            @endif
                            <th style="text-align:center; width:70px;">Kosong</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($analisis as $no => $item)
                            <tr>
                                <td style="text-align:center; font-weight:700; color:#334155;">{{ $no }}</td>
                                <td style="text-align:center;">
                                    <span style="display:inline-block; width:26px; height:26px; line-height:26px; border-radius:50%; background:#0284c7; color:#ffffff; font-weight:800; font-size:12px;">
                                        {{ $item['kunci'] }}
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div style="flex:1; background:#e2e8f0; height:8px; border-radius:4px; overflow:hidden;">
                                            <div style="background:{{ $item['persen_benar'] >= 75 ? '#16a34a' : ($item['persen_benar'] >= 35 ? '#0284c7' : '#dc2626') }}; height:100%; width:{{ $item['persen_benar'] }}%;"></div>
                                        </div>
                                        <span style="font-size:12px; font-weight:700; min-width:38px; text-align:right;">{{ $item['persen_benar'] }}%</span>
                                    </div>
                                </td>
                                <td style="text-align:center;">
                                    @if($item['tingkat_kesukaran'] == 'Mudah')
                                        <span style="padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; background:#dcfce7; color:#166534;">Mudah</span>
                                    @elseif($item['tingkat_kesukaran'] == 'Sedang')
                                        <span style="padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; background:#e0f2fe; color:#0369a1;">Sedang</span>
                                    @else
                                        <span style="padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; background:#fee2e2; color:#991b1b;">Sukar</span>
                                    @endif
                                </td>
                                <td style="text-align:center; font-weight:700; color:#16a34a;">{{ $item['jumlah_benar'] }}</td>
                                <td style="text-align:center; font-weight:700; color:#dc2626;">{{ $item['jumlah_salah'] }}</td>
                                
                                @foreach(['A', 'B', 'C', 'D'] as $opt)
                                    <td style="text-align:center;">
                                        <span class="dist-badge {{ $item['kunci'] == $opt ? 'correct' : '' }}">
                                            {{ $item['distribusi'][$opt] ?? 0 }}
                                        </span>
                                    </td>
                                @endforeach

                                @if($ujian->jumlah_opsi == 5)
                                    <td style="text-align:center;">
                                        <span class="dist-badge {{ $item['kunci'] == 'E' ? 'correct' : '' }}">
                                            {{ $item['distribusi']['E'] ?? 0 }}
                                        </span>
                                    </td>
                                @endif

                                <td style="text-align:center; color:#94a3b8; font-size:12px;">{{ $item['jumlah_kosong'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Koreksi Manual --}}
<div id="modalEdit" class="modal-overlay">
    <div class="modal-card">
        <form id="formManual" method="POST" action="">
            @csrf
            <div class="modal-head">
                <div>
                    <h3 style="font-size:16px; font-weight:700; margin:0;" id="modalSiswaName">Koreksi Manual Siswa</h3>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;" id="modalCatatan">Catatan scan</div>
                </div>
                <button type="button" onclick="closeEditModal()" style="border:none; background:none; font-size:22px; cursor:pointer; color:#94a3b8;">&times;</button>
            </div>

            <div class="modal-body">
                <div style="margin-bottom:14px; background:#f0f6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 14px;">
                    <label for="modalSelectSiswa" style="font-size:12px; font-weight:700; color:#1e40af; display:block; margin-bottom:4px;">Nama Siswa Pemilik Lembar Ini:</label>
                    <select name="siswa_id" id="modalSelectSiswa" class="form-control" style="font-size:13px; font-weight:600; padding:6px 10px; border-radius:6px; border:1px solid #93c5fd; width:100%;">
                        @foreach($siswaList as $sw)
                            <option value="{{ $sw->id }}">{{ $sw->nama_lengkap }} (ID: {{ $sw->id }})</option>
                        @endforeach
                    </select>
                    <div style="font-size:11px; color:#3b82f6; margin-top:3px;">Jika scanner keliru mencocokkan identitas siswa (misal QR tertutup atau out-of-order), Anda dapat memindahkan lembar ini ke nama siswa yang benar di sini.</div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(130px, 1fr)); gap:10px;" id="modalJawabanGrid">
                    {{-- Dinamis diisi JavaScript --}}
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan & Hitung Ulang Nilai</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchTab(tabId, el) {
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        el.classList.add('active');
    }

    const jumlahSoal = {{ $ujian->jumlah_soal }};
    const jumlahOpsi = {{ $ujian->jumlah_opsi }};
    const opsiLetters = jumlahOpsi == 5 ? ['A','B','C','D','E'] : ['A','B','C','D'];
    const updateUrlPattern = "{{ route('ujian.update-hasil-manual', [$ujian->id, ':hasil_id']) }}";

    function openEditModal(hasilId, siswaId, siswaName, jawabanMap, catatanScan) {
        document.getElementById('modalSiswaName').innerText = 'Koreksi Manual: ' + siswaName;
        document.getElementById('modalCatatan').innerText = catatanScan ? 'Catatan OMR: ' + catatanScan : 'Semua nomor terdeteksi normal.';
        document.getElementById('formManual').action = updateUrlPattern.replace(':hasil_id', hasilId);

        const sel = document.getElementById('modalSelectSiswa');
        if (sel && siswaId) {
            sel.value = siswaId;
        }

        const container = document.getElementById('modalJawabanGrid');
        container.innerHTML = '';

        for (let i = 1; i <= jumlahSoal; i++) {
            const currentAns = (jawabanMap && (jawabanMap[i] || jawabanMap[i.toString()])) || '';
            const box = document.createElement('div');
            box.style.border = '1px solid #e2e8f0';
            box.style.borderRadius = '8px';
            box.style.padding = '8px 10px';
            box.style.background = '#f8fafc';

            let labelHtml = `<div style="font-weight:700; font-size:12px; margin-bottom:4px; display:flex; justify-content:space-between;">
                <span>No. ${i}</span>
                <span style="font-size:11px; color:#dc2626;">${currentAns === 'GANDA' ? 'GANDA' : ''}</span>
            </div>`;

            let optHtml = `<div style="display:flex; gap:4px; justify-content:center;">`;
            opsiLetters.forEach(opt => {
                const isChecked = currentAns === opt ? 'selected' : '';
                optHtml += `<button type="button" class="opt-circle-btn ${isChecked}" onclick="selectAnswer(${i}, '${opt}', this)">${opt}</button>`;
            });
            optHtml += `</div>`;
            optHtml += `<input type="hidden" name="jawaban[${i}]" id="ans_input_${i}" value="${currentAns}">`;

            box.innerHTML = labelHtml + optHtml;
            container.appendChild(box);
        }

        document.getElementById('modalEdit').style.display = 'flex';
    }

    function selectAnswer(no, opt, btn) {
        const parent = btn.parentElement;
        const currentInput = document.getElementById('ans_input_' + no);
        if (currentInput.value === opt) {
            currentInput.value = '';
            btn.classList.remove('selected');
        } else {
            parent.querySelectorAll('.opt-circle-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            currentInput.value = opt;
        }
    }

    function closeEditModal() {
        document.getElementById('modalEdit').style.display = 'none';
    }
</script>
@endsection
