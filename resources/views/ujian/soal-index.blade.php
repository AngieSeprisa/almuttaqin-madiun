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
<style>
    .soal-card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 16px;
        margin-top: 20px;
    }
    .soal-card {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 12px;
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .soal-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    .soal-badge-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        flex-wrap: wrap;
    }
    .badge-code {
        background: #eff6ff;
        color: #0052FF;
        font-weight: 700;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 6px;
    }
    .filter-bar {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 12px;
        padding: 14px 18px;
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }
    /* Modal */
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.55);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1050;
        padding: 20px;
    }
    .modal-box {
        background: #ffffff;
        border-radius: 14px;
        width: 100%;
        max-width: 540px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        overflow: hidden;
    }
</style>

<div>
    @if(session('success'))
        <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:14px;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:14px;">
            {{ session('error') }}
        </div>
    @endif

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('ujian.soal') }}" class="filter-bar">
        <div style="flex:1; min-width:220px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau kode ujian..." style="width:100%; padding:7px 12px; font-size:13px; border:1px solid #cbd5e1; border-radius:8px;">
        </div>
        <div>
            <select name="kelas_id" style="padding:7px 12px; font-size:13px; border:1px solid #cbd5e1; border-radius:8px;">
                <option value="">-- Semua Kelas --</option>
                @foreach($kelases as $k)
                    <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="mapel_id" style="padding:7px 12px; font-size:13px; border:1px solid #cbd5e1; border-radius:8px;">
                <option value="">-- Semua Mapel --</option>
                @foreach($mapels as $m)
                    <option value="{{ $m->id }}" {{ request('mapel_id') == $m->id ? 'selected' : '' }}>{{ $m->mata_pelajaran }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-secondary" style="padding:7px 14px; font-size:13px; border-radius:8px;">Filter</button>
        @if(request()->anyFilled(['search', 'kelas_id', 'mapel_id']))
            <a href="{{ route('ujian.soal') }}" style="font-size:12px; color:#64748b; text-decoration:none;">Reset</a>
        @endif
    </form>

    <!-- Kartu Daftar Ujian & Soal -->
    <div class="soal-card-grid">
        @forelse($ujians as $ujian)
        <div class="soal-card">
            <div>
                <div class="soal-badge-row">
                    <span class="badge-code">{{ $ujian->kode_ujian }}</span>
                    {!! $ujian->status_badge !!}
                    <span style="font-size:11.5px; color:#64748b; margin-left:auto;">{{ $ujian->tanggal_ujian?->format('d/m/Y') }}</span>
                </div>

                <h3 style="font-size:16px; font-weight:700; color:#1e293b; margin:0 0 6px 0;">
                    {{ $ujian->nama_ujian }}
                </h3>

                <div style="font-size:12.5px; color:#64748b; line-height:1.5; margin-bottom:14px;">
                    <div><strong>Mapel:</strong> {{ $ujian->mapel?->mata_pelajaran ?? '-' }}</div>
                    <div><strong>Kelas:</strong> {{ $ujian->kelas?->nama_kelas ?? '-' }}</div>
                    <div><strong>Guru:</strong> {{ $ujian->guru?->name ?? '-' }}</div>
                    <div style="display:flex; gap:12px; margin-top:4px;">
                        <span><strong>Soal:</strong> {{ $ujian->jumlah_soal }} Butir</span>
                        <span><strong>Timer:</strong> {{ $ujian->timer_default }} dtk/soal</span>
                    </div>
                </div>

                <!-- Status Naskah Word -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; margin-bottom:14px; font-size:12px; display:flex; align-items:center; justify-content:space-between;">
                    <div style="display:flex; align-items:center; gap:6px;">
                        @if($ujian->soals_count > 0)
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span style="color:#065f46; font-weight:600;">Naskah Word Terbaca: <strong>{{ $ujian->soals_count }}</strong> soal</span>
                        @else
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#d97706" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            <span style="color:#92400e;">Belum Upload Naskah Word</span>
                        @endif
                    </div>

                    @if($ujian->file_soal)
                    <a href="{{ asset('storage/' . $ujian->file_soal) }}" target="_blank" style="color:#0052FF; font-weight:600; text-decoration:none;">Unduh Word</a>
                    @endif
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div style="border-top:1px solid #f1f5f9; padding-top:12px; display:flex; gap:6px; flex-wrap:wrap;">
                <button type="button" class="btn btn-primary" onclick="openUploadModal({{ $ujian->id }}, '{{ addslashes($ujian->nama_ujian) }}', {{ $ujian->timer_default }})" style="font-size:12px; padding:6px 12px; border-radius:7px; display:inline-flex; align-items:center; gap:4px;">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    <span>Upload Word</span>
                </button>

                <a href="{{ route('ujian.kunci', $ujian->id) }}" class="btn btn-secondary" style="font-size:12px; padding:6px 10px; border-radius:7px;">
                    Kunci Jawaban
                </a>

                <button type="button" class="btn btn-secondary" onclick="openTimerModal({{ $ujian->id }}, '{{ addslashes($ujian->nama_ujian) }}', {{ $ujian->timer_default }})" style="font-size:12px; padding:6px 10px; border-radius:7px;">
                    Timer
                </button>

                <a href="{{ route('ujian.edit', $ujian->id) }}" class="btn btn-secondary" style="font-size:12px; padding:6px 8px; border-radius:7px;" title="Ubah Ujian">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </a>
            </div>
        </div>
        @empty
        <div style="grid-column: 1 / -1; background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:40px; text-align:center; color:#94a3b8;">
            Belum ada data ujian yang dibuat. Silakan klik tombol <strong>+ Buat Ujian Baru</strong> di pojok kanan atas.
        </div>
        @endforelse
    </div>

    @if($ujians->hasPages())
    <div style="margin-top:20px;">
        {{ $ujians->links() }}
    </div>
    @endif
</div>

<!-- Modal Upload Word Soal -->
<div class="modal-overlay" id="uploadModal">
    <div class="modal-box">
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
<div class="modal-overlay" id="timerModal">
    <div class="modal-box">
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

