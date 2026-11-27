@extends('layouts.app')

@section('title', 'Sesi Ujian')

@section('header-actions')
<a href="{{ route('ujian.pengaturan') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px; text-decoration:none;">
    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="3"></circle>
        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
    </svg>
    <span>Pengaturan Jam Sesi</span>
</a>
@endsection

@section('content')
<style>
    .sesi-card-box {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 16px;
        padding: 24px 28px;
        width: 100%;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .sesi-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px 20px;
    }
    @media (max-width: 768px) {
        .sesi-form-grid {
            grid-template-columns: 1fr;
        }
    }
    .form-group-sesi label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: var(--text-primary, #1e293b);
        margin-bottom: 6px;
    }
    .input-sesi {
        width: 100%;
        padding: 10px 14px;
        font-size: 13.5px;
        border: 1px solid var(--border-light, #cbd5e1);
        border-radius: 10px;
        background: var(--bg-surface, #ffffff);
        color: var(--text-primary, #0f172a);
        box-sizing: border-box;
        transition: all 0.2s;
    }
    .input-sesi:focus {
        outline: none;
        border-color: #0052FF;
        box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.14);
    }
    .active-exam-preview {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        padding: 14px 18px;
        margin: 18px 0 4px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }
</style>

<div>
    <div class="sesi-card-box">
        <div style="text-align:center; margin-bottom:22px;">
            <div style="width:50px; height:50px; background:#eff6ff; color:#0052FF; border-radius:14px; display:inline-flex; align-items:center; justify-content:center; margin-bottom:10px;">
                <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                </svg>
            </div>
            <h2 style="font-size:19px; font-weight:800; color:var(--text-primary, #0f172a); margin:0 0 4px 0;">Persiapan Sesi Ujian di Kelas</h2>
            <p style="font-size:13px; color:var(--text-muted, #64748b); margin:0;">
                Pilih kelas, mata pelajaran, dan jam sesi untuk menampilkan soal per nomor di layar proyektor.
            </p>
        </div>

        <form id="formSesiUjian" onsubmit="handleStartSession(event)">
            <div class="sesi-form-grid">
                {{-- 1. Pilih Kelas --}}
                <div class="form-group-sesi">
                    <label for="selectKelas">1. Pilih Kelas</label>
                    <select id="selectKelas" class="input-sesi" onchange="filterAvailableExams()" required>
                        <option value="">-- Pilih Kelas Ujian --</option>
                        @foreach($kelases as $k)
                            <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 2. Pilih Mata Pelajaran --}}
                <div class="form-group-sesi">
                    <label for="selectMapel">2. Pilih Mata Pelajaran</label>
                    <select id="selectMapel" class="input-sesi" onchange="filterAvailableExams()" required>
                        <option value="">-- Pilih Mata Pelajaran --</option>
                        @foreach($mapels as $m)
                            <option value="{{ $m->id }}">
                                {{ $m->mata_pelajaran }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 3. Guru Pengampu (Otomatis Terisi) --}}
                <div class="form-group-sesi">
                    <label for="inputGuruName">3. Guru Pengampu</label>
                    <input type="text" id="inputGuruName" class="input-sesi" placeholder="Otomatis terisi saat naskah ujian cocok" readonly style="background:#f8fafc; font-weight:600; color:#1e293b;">
                </div>

                {{-- 4. Pilih Jam Ujian --}}
                <div class="form-group-sesi">
                    <label for="selectJam">4. Pilih Jam Sesi Ujian</label>
                    <select id="selectJam" class="input-sesi" required>
                        <option value="">-- Pilih Jam Sesi Ujian --</option>
                        @if(isset($sesiJams) && $sesiJams->isNotEmpty())
                            @foreach($sesiJams as $sj)
                                <option value="{{ $sj->label_lengkap }}">
                                    {{ $sj->label_lengkap }}
                                </option>
                            @endforeach
                        @else
                            <option value="Sesi 1 (07:30 - 09:00)">Sesi 1 (07:30 - 09:00)</option>
                            <option value="Sesi 2 (09:30 - 11:00)">Sesi 2 (09:30 - 11:00)</option>
                            <option value="Sesi 3 (12:30 - 14:00)">Sesi 3 (12:30 - 14:00)</option>
                        @endif
                    </select>
                </div>
            </div>

            {{-- Info Ujian Yang Cocok --}}
            <div id="examMatchBox" class="active-exam-preview" style="display:none;">
                <div>
                    <div style="font-size:11px; font-weight:700; color:#0052FF; text-transform:uppercase; letter-spacing:0.04em;">Naskah Ujian Siap</div>
                    <div style="font-size:15.5px; font-weight:800; color:#1e293b; margin:2px 0;" id="matchExamTitle">-</div>
                    <div style="font-size:12.5px; color:#475569;" id="matchExamMeta">-</div>
                </div>
                <div style="text-align:right;">
                    <span id="matchExamBadge" style="background:#0052FF; color:#fff; font-weight:700; font-size:12px; padding:5px 11px; border-radius:6px;">Siap Dimulai</span>
                </div>
            </div>

            <div id="noExamWarning" style="display:none; background:#fffbeb; border:1px solid #fde68a; color:#92400e; padding:12px 16px; border-radius:10px; margin:16px 0 4px; font-size:13px;">
                Belum ada naskah ujian untuk kombinasi Kelas dan Mata Pelajaran ini. Silakan buat terlebih dahulu di menu <strong>Soal Ujian</strong>.
            </div>

            <button type="submit" id="btnStartExam" class="btn btn-primary" style="width:100%; padding:13px; font-size:14.5px; font-weight:700; border-radius:10px; margin-top:16px; display:flex; align-items:center; justify-content:center; gap:8px;">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
                <span>Mulai Sesi Ujian</span>
            </button>
        </form>
    </div>

    {{-- Tabel Sesi Ujian Siap Ditampilkan --}}
    <div class="table-card">
        <div style="padding:15px 20px; border-bottom:1px solid var(--border-light); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
            <h3 style="font-size:15px; font-weight:700; color:var(--text-primary); margin:0;">Daftar Ujian Siap Ditampilkan</h3>
            <span style="font-size:12px; font-weight:600; color:var(--text-secondary);">Total: {{ $ujians->count() }} Ujian</span>
        </div>

        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width:46px; text-align:center;">No</th>
                        <th>Nama Ujian</th>
                        <th>Kelas &amp; Mapel</th>
                        <th>Guru Pengampu</th>
                        <th style="text-align:center; white-space:nowrap;">Soal &amp; Timer</th>
                        <th style="width:130px; text-align:center; white-space:nowrap;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ujians as $idx => $u)
                    <tr>
                        <td style="text-align:center; color:var(--text-muted); font-weight:600;">{{ $idx + 1 }}</td>
                        <td class="cell-nama">
                            <div style="font-weight:700; color:var(--text-primary); font-size:13.5px;">{{ $u->nama_ujian }}</div>
                            <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">
                                <span style="font-family:monospace; font-weight:700; color:#1d4ed8;">{{ $u->kode_ujian }}</span>
                                &bull; {{ $u->tanggal_ujian?->format('d M Y') ?? '-' }}
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:600; color:var(--text-primary); font-size:13px;">{{ $u->mapel?->mata_pelajaran ?? '-' }}</div>
                            <div style="font-size:12px; color:var(--text-muted);">Kelas {{ $u->kelas?->nama_kelas ?? '-' }}</div>
                        </td>
                        <td style="font-size:13px; color:var(--text-secondary);">
                            {{ $u->guru?->name ?? '-' }}
                        </td>
                        <td style="text-align:center; white-space:nowrap;">
                            <div style="font-weight:700; color:var(--text-primary); font-size:13px;">{{ $u->ringkasan_soal }}</div>
                            <div style="font-size:11.5px; color:var(--text-muted);">
                                @if($u->hasPg() && $u->hasEssay())
                                    PG {{ $u->timer_default }}d &bull; Essay {{ $u->timer_essay_default ?? 180 }}d
                                @elseif($u->hasEssay())
                                    {{ $u->timer_essay_default ?? 180 }} dtk/soal
                                @else
                                    {{ $u->timer_default }} dtk/soal
                                @endif
                            </div>
                        </td>
                        <td style="text-align:center; white-space:nowrap;">
                            <a href="{{ route('ujian.proyektor', $u->id) }}"
                               data-base-url="{{ route('ujian.proyektor', $u->id) }}"
                               onclick="return openProyektorRow(this)"
                               class="btn btn-sm btn-primary"
                               style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; font-size:12px; font-weight:600; text-decoration:none; border-radius:8px;">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                </svg>
                                <span>Mulai Sesi</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="cell-empty">
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
    let matchedUjian = null;

    function filterAvailableExams() {
        const kelasId = document.getElementById('selectKelas').value;
        const mapelId = document.getElementById('selectMapel').value;
        const guruNameInput = document.getElementById('inputGuruName');
        const matchBox = document.getElementById('examMatchBox');
        const noBox = document.getElementById('noExamWarning');

        matchedUjian = null;

        if (!kelasId || !mapelId) {
            matchBox.style.display = 'none';
            noBox.style.display = 'none';
            // Jika baru memilih mapel, coba tampilkan guru dari ujian mapel terkait
            if (mapelId) {
                const byMapel = ujianList.find(u => String(u.mapel_id) === String(mapelId) && u.guru && u.guru.name);
                guruNameInput.value = byMapel ? byMapel.guru.name : '';
            } else {
                guruNameInput.value = '';
            }
            return;
        }

        const match = ujianList.find(u => String(u.kelas_id) === String(kelasId) && String(u.mapel_id) === String(mapelId));

        if (match) {
            matchedUjian = match;
            guruNameInput.value = (match.guru && match.guru.name) ? match.guru.name : '-';
            const essayCount = parseInt(match.jumlah_soal_essay || 0, 10);
            const pgCount = parseInt(match.jumlah_soal || 0, 10);
            const komposisi = essayCount > 0 && pgCount > 0
                ? `${pgCount} PG + ${essayCount} Essay`
                : (essayCount > 0 ? `${essayCount} Soal Essay` : `${pgCount} Butir Soal`);
            document.getElementById('matchExamTitle').textContent = match.nama_ujian;
            document.getElementById('matchExamMeta').textContent = `${komposisi} • Timer PG: ${match.timer_default} dtk${essayCount > 0 ? ` • Essay: ${match.timer_essay_default || 180} dtk` : ''} • Kode: ${match.kode_ujian}`;
            matchBox.style.display = 'flex';
            noBox.style.display = 'none';
        } else {
            guruNameInput.value = '';
            matchBox.style.display = 'none';
            noBox.style.display = 'block';
        }
    }

    function handleStartSession(e) {
        e.preventDefault();
        if (!matchedUjian) {
            alert('Silakan pilih kombinasi Kelas dan Mata Pelajaran yang memiliki naskah ujian.');
            return;
        }

        const jam = document.getElementById('selectJam').value;
        const base = `/ujian/${matchedUjian.id}/proyektor`;
        window.location.href = jam ? `${base}?jam_ujian=${encodeURIComponent(jam)}` : base;
    }

    function openProyektorRow(linkEl) {
        const jam = document.getElementById('selectJam').value;
        if (jam) {
            const base = linkEl.getAttribute('data-base-url') || linkEl.href;
            linkEl.href = `${base}?jam_ujian=${encodeURIComponent(jam)}`;
        }
        return true;
    }
</script>
@endsection
