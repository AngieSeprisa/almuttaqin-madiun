@extends('layouts.app')

@section('title', 'Soal Ujian')

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
    <a href="{{ route('ujian.download-template') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px;">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        <span>Unduh Template Word (.docx)</span>
    </a>

    @if(!auth()->check() || auth()->user()->canDo('ujian', 'create'))
    <a href="{{ route('ujian.create') }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 16px; font-size:13px; border-radius:8px; font-weight:600;">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>+ Buat Ujian Baru</span>
    </a>
    @endif
</div>
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:20px; width:100%; max-width:100%; min-width:0;">

    {{-- Feedback Alerts --}}
    @if(session('success'))
    <div class="alert-banner alert-success" style="display:flex; align-items:center; gap:10px; padding:12px 18px; border-radius:12px; background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.25); color:#065F46;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0; color:#10B981;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div style="font-weight:600; font-size:13.5px;">{{ session('success') }}</div>
    </div>
    @endif

    @if(session('error'))
    <div class="alert-banner alert-danger" style="display:flex; align-items:center; gap:10px; padding:12px 18px; border-radius:12px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div style="font-weight:600; font-size:13.5px;">{{ session('error') }}</div>
    </div>
    @endif

    {{-- Filter & Search Bar Card (Standard Menu Card) --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('ujian.soal') }}" method="GET" class="filter-form">
            <div class="filter-group-left">
                {{-- Filter Kelas --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        </svg>
                    </div>
                    <select name="kelas_id" id="filter_kelas_id" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Kelas</option>
                        @foreach($kelases as $k)
                            <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                {{-- Filter Mapel --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>
                    <select name="mapel_id" id="filter_mapel_id" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Mapel</option>
                        @foreach($mapels as $m)
                            <option value="{{ $m->id }}" {{ request('mapel_id') == $m->id ? 'selected' : '' }}>{{ $m->mata_pelajaran }}</option>
                        @endforeach
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                @if(request('search') || request('kelas_id') || request('mapel_id'))
                    <a href="{{ route('ujian.soal') }}" class="btn btn-sm btn-secondary filter-btn-reset" title="Reset Filter">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        <span>Reset</span>
                    </a>
                @endif
            </div>

            {{-- Pencarian --}}
            <div class="filter-search-wrap">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control filter-search-input" placeholder="Cari nama ujian atau kode ujian...">
                <button type="submit" class="btn btn-secondary filter-search-btn" title="Cari">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    {{-- Tabel Soal Ujian (Standard Modern Table) --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width:50px; text-align:center;">No</th>
                        <th style="width:130px;">Kode Ujian</th>
                        <th style="min-width:180px;">Nama Ujian</th>
                        <th style="min-width:140px;">Mapel & Kelas</th>
                        <th style="min-width:140px;">Guru Pengampu</th>
                        <th style="width:110px; text-align:center;">Soal & Timer</th>
                        <th style="width:130px; text-align:center;">Naskah Word</th>
                        <th style="width:125px; text-align:center;">Kunci Jawaban</th>
                        <th style="text-align:center; width:150px; white-space:nowrap;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ujians as $idx => $ujian)
                    <tr>
                        <td style="text-align:center; color:var(--text-muted); font-weight:600;">
                            {{ $ujians->firstItem() ? ($ujians->firstItem() + $idx) : ($idx + 1) }}
                        </td>
                        <td>
                            <span style="display:inline-flex; align-items:center; padding:3px 9px; border-radius:6px; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; font-family:monospace; font-size:12px; font-weight:700;">
                                {{ $ujian->kode_ujian }}
                            </span>
                        </td>
                        <td class="cell-nama">
                            <div style="font-weight:700; color:var(--text-primary); font-size:13.5px;">{{ $ujian->nama_ujian }}</div>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">
                                Tanggal: {{ $ujian->tanggal_ujian?->format('d/m/Y') ?? '-' }}
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:600; color:var(--text-primary); font-size:13px;">{{ $ujian->mapel?->mata_pelajaran ?? '-' }}</div>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">
                                Kelas: <span style="font-weight:600; color:var(--text-secondary);">{{ $ujian->kelas?->nama_kelas ?? '-' }}</span>
                            </div>
                        </td>
                        <td style="font-size:13px; color:var(--text-secondary);">
                            {{ $ujian->guru?->name ?? '-' }}
                        </td>
                        <td style="text-align:center;">
                            <div style="font-weight:700; color:var(--text-primary); font-size:13px;">{{ $ujian->jumlah_soal }} Butir</div>
                            <div style="font-size:11.5px; color:var(--text-muted);">{{ $ujian->timer_default }} dtk/soal</div>
                        </td>
                        <td style="text-align:center;">
                            @if($ujian->soals_count > 0)
                                <span class="status-pill status-sudah_kembali" style="font-size:11px; padding:3px 8px;">
                                    {{ $ujian->soals_count }} Soal
                                </span>
                                @if($ujian->file_soal)
                                    <div style="margin-top:3px;">
                                        <a href="{{ asset('storage/' . $ujian->file_soal) }}" target="_blank" style="font-size:11px; color:var(--electric-blue); font-weight:600; text-decoration:none;">Unduh Word</a>
                                    </div>
                                @endif
                            @else
                                <span class="status-pill status-melewati_batas" style="font-size:11px; padding:3px 8px;">
                                    Belum Upload
                                </span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($ujian->kuncis_count >= $ujian->jumlah_soal && $ujian->jumlah_soal > 0)
                                <span class="status-pill status-sudah_kembali" style="font-size:11px; padding:3px 8px;">
                                    Lengkap ({{ $ujian->kuncis_count }})
                                </span>
                            @elseif($ujian->kuncis_count > 0)
                                <span class="status-pill status-pending" style="font-size:11px; padding:3px 8px;">
                                    {{ $ujian->kuncis_count }}/{{ $ujian->jumlah_soal }} Kunci
                                </span>
                            @else
                                <span class="status-pill status-melewati_batas" style="font-size:11px; padding:3px 8px;">
                                    Kosong (0)
                                </span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group">
                                {{-- Tombol Upload Word --}}
                                <button type="button" class="action-btn action-btn-success" onclick="openUploadModal({{ $ujian->id }}, '{{ addslashes($ujian->nama_ujian) }}', {{ $ujian->timer_default }})" title="Upload Naskah Word" aria-label="Upload Word {{ $ujian->nama_ujian }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                </button>

                                {{-- Tombol Kunci Jawaban --}}
                                <a href="{{ route('ujian.kunci', $ujian->id) }}" class="action-btn action-btn-view" title="Kunci Jawaban" aria-label="Kunci Jawaban {{ $ujian->nama_ujian }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="7.5" cy="15.5" r="5.5"></circle>
                                        <path d="M21 2l-9.6 9.6"></path>
                                        <path d="M15.5 7.5l3 3L22 7l-3-3"></path>
                                    </svg>
                                </a>

                                {{-- Tombol Timer --}}
                                <button type="button" class="action-btn action-btn-edit" onclick="openTimerModal({{ $ujian->id }}, '{{ addslashes($ujian->nama_ujian) }}', {{ $ujian->timer_default }})" title="Atur Timer Soal" aria-label="Atur Timer {{ $ujian->nama_ujian }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                </button>

                                {{-- Tombol Ubah Ujian --}}
                                @if(!auth()->check() || auth()->user()->canDo('ujian', 'edit'))
                                <a href="{{ route('ujian.edit', $ujian->id) }}" class="action-btn action-btn-edit" title="Ubah Ujian" aria-label="Ubah Ujian {{ $ujian->nama_ujian }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                @endif

                                {{-- Tombol Hapus Ujian --}}
                                @if(!auth()->check() || auth()->user()->canDo('ujian', 'delete'))
                                <form action="{{ route('ujian.destroy', $ujian->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus ujian {{ addslashes($ujian->nama_ujian) }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn action-btn-delete" title="Hapus Ujian" aria-label="Hapus Ujian {{ $ujian->nama_ujian }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="cell-empty">
                            Belum ada data ujian yang dibuat. Silakan klik tombol <strong>"+ Buat Ujian Baru"</strong> di pojok kanan atas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ujians->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border-light);">
            {{ $ujians->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal Upload Word Soal -->
<div class="modal-overlay" id="uploadModal" style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15, 23, 42, 0.55); display:none; align-items:center; justify-content:center; z-index:1050; padding:20px;">
    <div class="modal-box" style="background:#ffffff; border-radius:14px; width:100%; max-width:540px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1); overflow:hidden;">
        <form method="POST" id="formUploadDocx" enctype="multipart/form-data">
            @csrf
            <div style="padding:18px 22px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:16px; font-weight:700; color:#1e293b;" id="uploadModalTitle">Upload Naskah Soal Word</h3>
                <button type="button" onclick="closeUploadModal()" style="border:none; background:none; font-size:20px; cursor:pointer; color:#94a3b8;">&times;</button>
            </div>
            <div style="padding:20px 22px;">
                <div style="margin-bottom:14px;">
                    <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px; color:#1e293b;">Pilih Berkas Word (.docx)</label>
                    <input type="file" name="file_word" accept=".docx" required style="width:100%; font-size:13px; padding:8px; border:1px dashed #cbd5e1; border-radius:8px; background:#f8fafc;">
                    <span style="font-size:11.5px; color:#64748b; margin-top:4px; display:block;">
                        Format harus .docx. Sistem akan mengekstrak nomor soal, opsi A-E, dan baris Kunci secara otomatis.
                    </span>
                </div>
                <div style="margin-bottom:14px;">
                    <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px; color:#1e293b;">Batas Waktu Timer per Soal (Detik)</label>
                    <input type="number" name="timer_per_soal" id="uploadTimerInput" min="10" max="1800" value="90" style="width:100%; padding:8px 12px; font-size:13px; border:1px solid #cbd5e1; border-radius:8px;">
                    <span style="font-size:11.5px; color:#64748b; margin-top:4px; display:block;">
                        Contoh: 60 = 1 menit per soal, 90 = 1.5 menit per soal.
                    </span>
                </div>
            </div>
            <div style="padding:14px 22px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn btn-secondary" onclick="closeUploadModal()" style="padding:7px 14px; font-size:13px; border-radius:8px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding:7px 16px; font-size:13px; border-radius:8px;">Mulai Proses Ekstraksi</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Atur Timer -->
<div class="modal-overlay" id="timerModal" style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15, 23, 42, 0.55); display:none; align-items:center; justify-content:center; z-index:1050; padding:20px;">
    <div class="modal-box" style="background:#ffffff; border-radius:14px; width:100%; max-width:540px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1); overflow:hidden;">
        <form method="POST" id="formTimer">
            @csrf
            <div style="padding:18px 22px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:16px; font-weight:700; color:#1e293b;" id="timerModalTitle">Pengaturan Timer Soal</h3>
                <button type="button" onclick="closeTimerModal()" style="border:none; background:none; font-size:20px; cursor:pointer; color:#94a3b8;">&times;</button>
            </div>
            <div style="padding:20px 22px;">
                <div style="margin-bottom:14px;">
                    <label style="font-size:13px; font-weight:600; display:block; margin-bottom:6px; color:#1e293b;">Timer Default Seluruh Soal (Detik)</label>
                    <input type="number" name="timer_default" id="timerDefaultInput" min="10" max="1800" required style="width:100%; padding:8px 12px; font-size:13px; border:1px solid #cbd5e1; border-radius:8px;">
                    <span style="font-size:11.5px; color:#64748b; margin-top:4px; display:block;">
                        Waktu hitung mundur per nomor soal saat ditampilkan di layar proyektor.
                    </span>
                </div>
            </div>
            <div style="padding:14px 22px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn btn-secondary" onclick="closeTimerModal()" style="padding:7px 14px; font-size:13px; border-radius:8px;">Tutup</button>
                <button type="submit" class="btn btn-primary" style="padding:7px 16px; font-size:13px; border-radius:8px;">Simpan Timer</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openUploadModal(ujianId, namaUjian, timerDefault) {
        document.getElementById('uploadModalTitle').textContent = 'Upload Soal: ' + namaUjian;
        document.getElementById('uploadTimerInput').value = timerDefault || 90;
        document.getElementById('formUploadDocx').action = '/ujian/' + ujianId + '/upload-word';
        document.getElementById('uploadModal').style.display = 'flex';
    }
    function closeUploadModal() {
        document.getElementById('uploadModal').style.display = 'none';
    }

    function openTimerModal(ujianId, namaUjian, timerDefault) {
        document.getElementById('timerModalTitle').textContent = 'Atur Timer: ' + namaUjian;
        document.getElementById('timerDefaultInput').value = timerDefault || 90;
        document.getElementById('formTimer').action = '/ujian/' + ujianId + '/timer';
        document.getElementById('timerModal').style.display = 'flex';
    }
    function closeTimerModal() {
        document.getElementById('timerModal').style.display = 'none';
    }
</script>
@endsection
