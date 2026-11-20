@extends('layouts.app')

@section('title', 'Sesi Ujian (Mode Proyektor)')

@section('content')
<style>
    .sesi-card-box {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 16px;
        padding: 26px 30px;
        width: 100%;
        margin-bottom: 30px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.04);
    }
    .sesi-form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 18px;
    }
    .form-group-sesi {
        margin-bottom: 6px;
    }
    .form-group-sesi label {
        display: block;
        font-size: 13.5px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 6px;
    }
    .input-sesi {
        width: 100%;
        padding: 10px 14px;
        font-size: 14px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: #ffffff;
        color: #0f172a;
        transition: all 0.2s;
    }
    .input-sesi:focus {
        outline: none;
        border-color: #0052FF;
        box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.15);
    }
    .active-exam-preview {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 16px 20px;
        margin: 20px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }
</style>

<div>
    <div class="sesi-card-box">
        <div style="text-align:center; margin-bottom:24px;">
            <div style="width:54px; height:54px; background:#eff6ff; color:#0052FF; border-radius:14px; display:inline-flex; align-items:center; justify-content:center; margin-bottom:10px;">
                <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
            </div>
            <h2 style="font-size:20px; font-weight:800; color:#0f172a; margin:0 0 6px 0;">Persiapan Sesi Ujian di Kelas</h2>
            <p style="font-size:13.5px; color:#64748b; margin:0;">
                Pilih kelas dan mata pelajaran. Sistem akan menampilkan soal ujian nomor per nomor di layar proyektor kelas dengan timer hitung mundur.
            </p>
        </div>

        <form id="formSesiUjian" onsubmit="handleStartSession(event)">
            <div class="sesi-form-grid">
                <!-- 1. Pilih Kelas -->
                <div class="form-group-sesi">
                    <label>1. Pilih Kelas</label>
                    <select id="selectKelas" class="input-sesi" onchange="filterAvailableExams()" required>
                        <option value="">-- Pilih Kelas Ujian --</option>
                        @foreach($kelases as $k)
                            <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Pilih Mata Pelajaran -->
                <div class="form-group-sesi">
                    <label>2. Pilih Mata Pelajaran</label>
                    <select id="selectMapel" class="input-sesi" onchange="onMapelChanged()" required>
                        <option value="">-- Pilih Mata Pelajaran --</option>
                        @foreach($mapels as $m)
                            <option value="{{ $m->id }}" data-guru-id="{{ $m->guru_id }}" data-guru-name="{{ $m->guru?->name ?? '' }}">
                                {{ $m->mata_pelajaran }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. Guru Pengampu (Otomatis Terisi) -->
                <div class="form-group-sesi">
                    <label>3. Guru Pengampu (Terisi Otomatis)</label>
                    <div style="position:relative;">
                        <input type="text" id="inputGuruName" class="input-sesi" placeholder="Guru otomatis terdeteksi dari mata pelajaran" readonly style="background:#f8fafc; font-weight:600; color:#1e293b;">
                        <input type="hidden" id="inputGuruId">
                    </div>
                </div>

                <!-- 4. Pilih Jam Ujian -->
                <div class="form-group-sesi">
                    <label>4. Pilih Jam Ujian</label>
                    <select id="selectJam" class="input-sesi" required>
                        <option value="">-- Pilih Jam Sesi Ujian --</option>
                        @if($timeSlots->isNotEmpty())
                            @foreach($timeSlots as $ts)
                                <option value="Jam Ke-{{ $ts->jam_ke }} ({{ $ts->mulai }} - {{ $ts->selesai }})">
                                    Jam Ke-{{ $ts->jam_ke }} ({{ $ts->mulai }} - {{ $ts->selesai }})
                                </option>
                            @endforeach
                        @else
                            <option value="Sesi Pagi 1 (07:30 - 09:00)">Sesi Pagi 1 (07:30 - 09:00)</option>
                            <option value="Sesi Pagi 2 (09:30 - 11:00)">Sesi Pagi 2 (09:30 - 11:00)</option>
                            <option value="Sesi Siang (12:30 - 14:00)">Sesi Siang (12:30 - 14:00)</option>
                        @endif
                    </select>
                </div>
            </div>

            <!-- Info Ujian Yang Cocok -->
            <div id="examMatchBox" class="active-exam-preview" style="display:none;">
                <div>
                    <div style="font-size:11px; font-weight:700; color:#0052FF; text-transform:uppercase; letter-spacing:0.04em;">Naskah Ujian Ditemukan</div>
                    <div style="font-size:16px; font-weight:800; color:#1e293b; margin:2px 0;" id="matchExamTitle">-</div>
                    <div style="font-size:12.5px; color:#475569;" id="matchExamMeta">-</div>
                </div>
                <div style="text-align:right;">
                    <span id="matchExamBadge" style="background:#0052FF; color:#fff; font-weight:700; font-size:12px; padding:4px 10px; border-radius:6px;">Siap Dimulai</span>
                </div>
            </div>

            <div id="noExamWarning" style="display:none; background:#fffbeb; border:1px solid #fde68a; color:#92400e; padding:12px 16px; border-radius:10px; margin:16px 0; font-size:13px;">
                Belum ada data ujian yang cocok untuk kombinasi Kelas dan Mata Pelajaran ini. Anda dapat membuat ujian terlebih dahulu di menu <strong>Soal Ujian</strong>.
            </div>

            <button type="submit" id="btnStartExam" class="btn btn-primary" style="width:100%; padding:14px; font-size:15px; font-weight:800; border-radius:10px; margin-top:10px; display:flex; align-items:center; justify-content:center; gap:8px;">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
                <span>Masuk Mode Layar Penuh (Mulai Sesi Ujian)</span>
            </button>
        </form>
    </div>

    {{-- Tabel Sesi Ujian Siap Ditampilkan --}}
    <div class="table-card">
        <div style="padding:16px 20px; border-bottom:1px solid var(--border-light); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <div>
                <h3 style="font-size:15px; font-weight:700; color:var(--text-primary); margin:0;">Daftar Ujian Siap Ditampilkan di Layar Proyektor</h3>
                <p style="font-size:12.5px; color:var(--text-muted); margin:2px 0 0;">Pilih salah satu ujian di bawah atau gunakan formulir di atas untuk langsung membuka sesi ujian kelas.</p>
            </div>
            <span style="font-size:12px; font-weight:600; color:var(--text-secondary);">Total: {{ $ujians->count() }} Ujian</span>
        </div>

        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width:50px; text-align:center;">No</th>
                        <th style="width:130px;">Kode Ujian</th>
                        <th>Nama Ujian</th>
                        <th>Kelas & Mapel</th>
                        <th>Guru Pengampu</th>
                        <th style="width:120px; text-align:center;">Soal & Timer</th>
                        <th style="width:140px; text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ujians as $idx => $u)
                    <tr>
                        <td style="text-align:center; color:var(--text-muted); font-weight:600;">{{ $idx + 1 }}</td>
                        <td>
                            <span style="display:inline-flex; align-items:center; padding:3px 9px; border-radius:6px; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; font-family:monospace; font-size:12px; font-weight:700;">
                                {{ $u->kode_ujian }}
                            </span>
                        </td>
                        <td class="cell-nama">
                            <div style="font-weight:700; color:var(--text-primary); font-size:13.5px;">{{ $u->nama_ujian }}</div>
                            <div style="font-size:12px; color:var(--text-muted);">{{ $u->tanggal_ujian?->format('d M Y') ?? '-' }}</div>
                        </td>
                        <td>
                            <div style="font-weight:600; color:var(--text-primary);">{{ $u->mapel?->mata_pelajaran ?? '-' }}</div>
                            <div style="font-size:12px; color:var(--text-muted);">Kelas: {{ $u->kelas?->nama_kelas ?? '-' }}</div>
                        </td>
                        <td style="font-size:13px; color:var(--text-secondary);">
                            {{ $u->guru?->name ?? '-' }}
                        </td>
                        <td style="text-align:center;">
                            <div style="font-weight:700; color:var(--text-primary); font-size:13px;">{{ $u->jumlah_soal }} Butir</div>
                            <div style="font-size:11.5px; color:var(--text-muted);">{{ $u->timer_default }} dtk/soal</div>
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('ujian.proyektor', $u->id) }}" class="btn btn-sm btn-primary" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; font-size:12px; font-weight:600; text-decoration:none;">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                </svg>
                                <span>Mulai Sesi</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="cell-empty">
                            Belum ada naskah ujian yang dibuat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const ujianList = @json($ujians);
    const guruList = @json($gurus);
    let matchedUjian = null;

    function onMapelChanged() {
        const selectMapel = document.getElementById('selectMapel');
        const selectedOpt = selectMapel.options[selectMapel.selectedIndex];
        const guruNameInput = document.getElementById('inputGuruName');
        const guruIdInput = document.getElementById('inputGuruId');

        if (!selectedOpt || !selectedOpt.value) {
            guruNameInput.value = '';
            guruIdInput.value = '';
            filterAvailableExams();
            return;
        }

        const guruId = selectedOpt.getAttribute('data-guru-id');
        let guruName = selectedOpt.getAttribute('data-guru-name');

        if (guruId) {
            const found = guruList.find(g => g.id == guruId);
            if (found) guruName = found.name;
        }

        if (!guruName) {
            guruName = 'Guru Mata Pelajaran';
        }

        guruNameInput.value = guruName;
        guruIdInput.value = guruId || '';

        filterAvailableExams();
    }

    function filterAvailableExams() {
        const kelasId = document.getElementById('selectKelas').value;
        const mapelId = document.getElementById('selectMapel').value;
        const matchBox = document.getElementById('examMatchBox');
        const noBox = document.getElementById('noExamWarning');
        const btn = document.getElementById('btnStartExam');

        matchedUjian = null;

        if (!kelasId || !mapelId) {
            matchBox.style.display = 'none';
            noBox.style.display = 'none';
            return;
        }

        // Cari ujian yang cocok
        const match = ujianList.find(u => u.kelas_id == kelasId && u.mapel_id == mapelId);

        if (match) {
            matchedUjian = match;
            document.getElementById('matchExamTitle').textContent = match.nama_ujian;
            document.getElementById('matchExamMeta').textContent = `${match.jumlah_soal} Butir Soal • Timer: ${match.timer_default} detik/soal • Kode: ${match.kode_ujian}`;
            matchBox.style.display = 'flex';
            noBox.style.display = 'none';
            btn.disabled = false;
        } else {
            matchBox.style.display = 'none';
            noBox.style.display = 'block';
        }
    }

    function handleStartSession(e) {
        e.preventDefault();
        if (!matchedUjian) {
            alert('Silakan pilih kombinasi Kelas dan Mapel yang memiliki naskah ujian.');
            return;
        }

        const jam = encodeURIComponent(document.getElementById('selectJam').value);
        const url = `/ujian/${matchedUjian.id}/proyektor?jam_ujian=${jam}`;
        window.location.href = url;
    }
</script>
@endsection

