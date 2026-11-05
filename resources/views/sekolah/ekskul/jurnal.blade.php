@extends('layouts.app')

@section('title', 'Jurnal Ekstrakurikuler')

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px;">
    <a href="{{ route('sekolah.ekskul.rekap-jurnal') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
        </svg>
        <span>Rekap Jurnal</span>
    </a>
</div>
@endsection

@push('styles')
<style>
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .status-btn-group { display: inline-flex; gap: 4px; background: #F1F5F9; padding: 3px; border-radius: 8px; }
    .dark-mode .status-btn-group { background: #1E293B; }
    .status-btn {
        padding: 4px 10px; font-size: 11.5px; font-weight: 700; border: none; border-radius: 6px;
        background: transparent; color: #64748B; cursor: pointer; transition: all 0.15s ease;
    }
    .status-btn:hover { color: #0F172A; }
    .status-btn.active.is-hadir { background: #10B981; color: #FFFFFF; }
    .status-btn.active.is-sakit { background: #F59E0B; color: #FFFFFF; }
    .status-btn.active.is-izin { background: #2563EB; color: #FFFFFF; }
    .status-btn.active.is-alpa { background: #EF4444; color: #FFFFFF; }
</style>
@endpush

@section('content')
<div style="display:flex; flex-direction:column; gap:20px; width:100%; max-width:1200px;">

    {{-- Feedback Alerts --}}
    @if(session('success'))
    <div class="alert-banner alert-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    @if(session('error'))
    <div class="alert-banner alert-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>{{ session('error') }}</div>
    </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="alert-banner alert-error">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <div>
                <strong>Terjadi kesalahan saat menyimpan jurnal:</strong>
                <ul style="margin: 4px 0 0 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('sekolah.ekskul.store') }}" method="POST" id="formJurnalEkskul" style="display:flex; flex-direction:column; gap:20px;">
        @csrf

        {{-- Kartu Form Data Jurnal Kegiatan --}}
        <div class="form-section-card">
            <div class="form-section-header">
                <div class="form-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </div>
                <div class="form-section-title-wrap">
                    <h2 class="form-section-title">Form Input Jurnal Kegiatan Ekstrakurikuler</h2>
                    <span style="font-size:12.5px; color:var(--text-secondary);">Catat materi latihan, waktu pelaksanaan, dan pembina kegiatan ekskul</span>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:18px;">

                {{-- Pilih Ekstrakurikuler --}}
                <div class="form-group" style="grid-column: span 1;">
                    <label for="select_ekskul" class="form-label">Pilih Ekstrakurikuler <span class="required-mark">*</span></label>
                    <select name="ekstrakurikuler_id" id="select_ekskul" class="form-select" required onchange="onEkskulChange(this.value)">
                        @foreach($ekskuls as $ek)
                            <option value="{{ $ek->id }}"
                                    data-pembina-id="{{ $ek->pembina_id }}"
                                    data-pembina="{{ $ek->nama_pembina }}"
                                    data-hari="{{ $ek->hari }}"
                                    data-mulai="{{ $ek->jam_mulai ? substr($ek->jam_mulai, 0, 5) : '' }}"
                                    data-selesai="{{ $ek->jam_selesai ? substr($ek->jam_selesai, 0, 5) : '' }}"
                                    data-tempat="{{ $ek->tempat }}"
                                    {{ ($selectedEkskul && $selectedEkskul->id == $ek->id) ? 'selected' : '' }}>
                                {{ $ek->nama_ekskul }} ({{ $ek->hari ?: 'Jadwal belum diatur' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tanggal --}}
                <div class="form-group">
                    <label for="input_tanggal" class="form-label">Tanggal Pelaksanaan <span class="required-mark">*</span></label>
                    <input type="date" name="tanggal" id="input_tanggal" class="form-control"
                           value="{{ old('tanggal', $periode['tanggal']) }}" required onchange="updatePeriodeOtomatis(this.value)">
                </div>

                {{-- Hari (Otomatis) --}}
                <div class="form-group">
                    <label for="input_hari" class="form-label">Hari (Otomatis)</label>
                    <input type="text" name="hari" id="input_hari" class="form-control"
                           value="{{ old('hari', $periode['hari']) }}" readonly style="background:var(--bg-card-header); cursor:not-allowed;">
                </div>

                {{-- Semester (Otomatis) --}}
                <div class="form-group">
                    <label for="input_semester" class="form-label">Semester (Otomatis)</label>
                    <input type="text" name="semester" id="input_semester" class="form-control"
                           value="{{ old('semester', $periode['semester']) }}" readonly style="background:var(--bg-card-header); cursor:not-allowed;">
                </div>

                {{-- Tahun Pelajaran (Otomatis) --}}
                <div class="form-group">
                    <label for="input_tahun_pelajaran" class="form-label">Tahun Pelajaran (Otomatis)</label>
                    <input type="text" name="tahun_pelajaran" id="input_tahun_pelajaran" class="form-control"
                           value="{{ old('tahun_pelajaran', $periode['tahun_pelajaran']) }}" readonly style="background:var(--bg-card-header); cursor:not-allowed;">
                </div>

                {{-- Jam Latihan --}}
                <div class="form-group" style="display:flex; gap:10px;">
                    <div style="flex:1;">
                        <label for="input_jam_mulai" class="form-label">Jam Mulai</label>
                        <input type="time" name="jam_mulai" id="input_jam_mulai" class="form-control"
                               value="{{ old('jam_mulai', $selectedEkskul?->jam_mulai ? substr($selectedEkskul->jam_mulai, 0, 5) : '') }}">
                    </div>
                    <div style="flex:1;">
                        <label for="input_jam_selesai" class="form-label">Jam Selesai</label>
                        <input type="time" name="jam_selesai" id="input_jam_selesai" class="form-control"
                               value="{{ old('jam_selesai', $selectedEkskul?->jam_selesai ? substr($selectedEkskul->jam_selesai, 0, 5) : '') }}">
                    </div>
                </div>

                {{-- Pembina / Pelatih --}}
                <div class="form-group">
                    <label for="select_pembina" class="form-label">Guru Pembina / Pelatih</label>
                    <select name="pembina_id" id="select_pembina" class="form-select">
                        <option value="">-- Pilih Guru Pembina --</option>
                        @foreach($gurus as $guru)
                            <option value="{{ $guru->id }}" {{ (old('pembina_id', $selectedEkskul?->pembina_id) == $guru->id) ? 'selected' : '' }}>
                                {{ $guru->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tempat Latihan --}}
                <div class="form-group">
                    <label for="input_tempat" class="form-label">Tempat / Lokasi</label>
                    <input type="text" name="tempat" id="input_tempat" class="form-control"
                           value="{{ old('tempat', $selectedEkskul?->tempat) }}" placeholder="Contoh: Lapangan Olahraga, Aula Utama">
                </div>

                {{-- Materi / Aktivitas Latihan --}}
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="textarea_materi" class="form-label">Materi / Aktivitas Latihan <span class="required-mark">*</span></label>
                    <textarea name="materi" id="textarea_materi" class="form-control" rows="3" required
                              placeholder="Uraikan materi, agenda latihan, atau kegiatan yang dilakukan hari ini...">{{ old('materi') }}</textarea>
                </div>

                {{-- Catatan / Evaluasi --}}
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="textarea_catatan" class="form-label">Catatan & Evaluasi Perkembangan (Opsional)</label>
                    <textarea name="catatan" id="textarea_catatan" class="form-control" rows="2"
                              placeholder="Catatan khusus, kedisiplinan peserta, atau persiapan event/lomba...">{{ old('catatan') }}</textarea>
                </div>

            </div>
        </div>

        {{-- Kartu Presensi Anggota Ekskul --}}
        <div class="form-section-card">
            <div class="form-section-header" style="justify-content:space-between; flex-wrap:wrap; gap:12px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="form-section-icon" style="background:rgba(0, 82, 255, 0.1); color:#0052FF;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="form-section-title-wrap">
                        <h2 class="form-section-title">Absensi Anggota: <span id="label_nama_ekskul">{{ $selectedEkskul?->nama_ekskul ?: '-' }}</span></h2>
                        <span style="font-size:12.5px; color:var(--text-secondary);">Klik tombol status (Hadir, Sakit, Izin, Alpa) untuk setiap siswa</span>
                    </div>
                </div>

                {{-- Stat Ringkasan Absensi --}}
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span style="font-size:12px; font-weight:700; padding:4px 10px; border-radius:12px; background:rgba(16, 185, 129, 0.15); color:#059669;">
                        Hadir: <span id="count_hadir">0</span>
                    </span>
                    <span style="font-size:12px; font-weight:700; padding:4px 10px; border-radius:12px; background:rgba(245, 158, 11, 0.15); color:#D97706;">
                        Sakit: <span id="count_sakit">0</span>
                    </span>
                    <span style="font-size:12px; font-weight:700; padding:4px 10px; border-radius:12px; background:rgba(37, 99, 235, 0.15); color:#2563EB;">
                        Izin: <span id="count_izin">0</span>
                    </span>
                    <span style="font-size:12px; font-weight:700; padding:4px 10px; border-radius:12px; background:rgba(239, 68, 68, 0.15); color:#DC2626;">
                        Alpa: <span id="count_alpa">0</span>
                    </span>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="setSemuaStatus('hadir')" style="font-size:11.5px; padding:4px 10px;">
                        Set Semua Hadir
                    </button>
                    <a href="{{ route('plotting-ekskul.index') }}" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size:11.5px; padding:4px 10px; display:inline-flex; align-items:center; gap:6px;" title="Kelola pembagian siswa per ekskul di menu Plotting Ekstrakurikuler">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                        <span>Plotting Ekskul</span>
                    </a>
                </div>
            </div>

            {{-- Tabel Daftar Siswa Anggota --}}
            <div class="table-card" style="box-shadow:none; border:1px solid var(--border-light); margin-top:8px;">
                <div class="table-wrapper">
                    <table class="modern-table" id="tableSiswaEkskul">
                        <thead>
                            <tr>
                                <th style="width:50px; text-align:center;">No</th>
                                <th>Nama Siswa</th>
                                <th style="width:120px;">Kelas</th>
                                <th style="width:220px; text-align:center;">Status Kehadiran</th>
                                <th>Keterangan (Opsional)</th>
                            </tr>
                        </thead>
                        <tbody id="siswaEkskulTbody">
                            {{-- Baris siswa dirender secara dinamis oleh JS --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tombol Aksi Simpan --}}
        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:4px;">
            <a href="{{ route('sekolah.ekskul.rekap-jurnal') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" id="btnSubmitJurnal" style="min-width:180px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>Simpan Jurnal & Presensi</span>
            </button>
        </div>

    </form>

</div>

<script>
let currentAnggotaList = [];

// Fungsi otomatisasi hitung Hari, Semester & Tahun Pelajaran saat ganti tanggal
function updatePeriodeOtomatis(tglVal) {
    if (!tglVal) return;
    const dateObj = new Date(tglVal);
    if (isNaN(dateObj.getTime())) return;

    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    document.getElementById('input_hari').value = days[dateObj.getDay()];

    const month = dateObj.getMonth() + 1;
    const year = dateObj.getFullYear();
    if (month >= 7) {
        document.getElementById('input_semester').value = 'Ganjil';
        document.getElementById('input_tahun_pelajaran').value = `${year}/${year + 1}`;
    } else {
        document.getElementById('input_semester').value = 'Genap';
        document.getElementById('input_tahun_pelajaran').value = `${year - 1}/${year}`;
    }
}

// Handler saat dropdown ekskul diganti
function onEkskulChange(ekskulId) {
    if (!ekskulId) return;

    const sel = document.getElementById('select_ekskul');
    const opt = sel.options[sel.selectedIndex];

    document.getElementById('label_nama_ekskul').textContent = opt.textContent.split('(')[0].trim();

    if (opt.dataset.pembinaId) {
        document.getElementById('select_pembina').value = opt.dataset.pembinaId;
    }
    if (opt.dataset.mulai) {
        document.getElementById('input_jam_mulai').value = opt.dataset.mulai;
    }
    if (opt.dataset.selesai) {
        document.getElementById('input_jam_selesai').value = opt.dataset.selesai;
    }
    if (opt.dataset.tempat) {
        document.getElementById('input_tempat').value = opt.dataset.tempat;
    }

    loadAnggotaByEkskul(ekskulId);
}

// Fetch siswa anggota via AJAX
function loadAnggotaByEkskul(ekskulId) {
    const tbody = document.getElementById('siswaEkskulTbody');
    tbody.innerHTML = `
        <tr>
            <td colspan="5" style="text-align:center; padding:28px 16px; color:var(--text-secondary);">
                <div style="display:inline-flex; align-items:center; gap:8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;animation:spin 1s linear infinite;">
                        <circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="16"></circle>
                    </svg>
                    <span>Memuat data anggota ekstrakurikuler...</span>
                </div>
            </td>
        </tr>
    `;

    fetch(`/sekolah/ekskul/siswa/${ekskulId}`)
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                currentAnggotaList = res.siswa || [];
                renderSiswaRows(currentAnggotaList);
            }
        })
        .catch(err => {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" style="text-align:center; padding:24px 16px; color:#DC2626;">
                        Gagal memuat daftar anggota. Silakan muat ulang halaman.
                    </td>
                </tr>
            `;
        });
}

// Render baris siswa
function renderSiswaRows(list) {
    const tbody = document.getElementById('siswaEkskulTbody');
    tbody.innerHTML = '';

    if (!list || list.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align:center; padding:32px 16px; color:var(--text-secondary);">
                    <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:36px;height:36px;opacity:0.4;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span style="font-size:13.5px; font-weight:500;">Belum ada siswa terdaftar di ekstrakurikuler ini.</span>
                        <a href="{{ route('plotting-ekskul.index') }}" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size:12px; margin-top:4px;">
                            Buka Plotting Ekstrakurikuler &rarr;
                        </a>
                    </div>
                </td>
            </tr>
        `;
        recalcStats();
        return;
    }

    let html = '';
    list.forEach((s, idx) => {
        const sid = s.id;
        const initial = s.nama_lengkap.substring(0, 2).toUpperCase();

        html += `
            <tr>
                <td style="text-align:center; font-weight:600; color:var(--text-secondary);">${idx + 1}</td>
                <td>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:32px; height:32px; border-radius:50%; background:rgba(0, 82, 255, 0.1); color:#0052FF; font-weight:700; font-size:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            ${initial}
                        </div>
                        <div>
                            <div style="font-weight:600; font-size:13px; color:var(--text-primary);">${s.nama_lengkap}</div>
                            <div style="font-size:11.5px; color:var(--text-secondary);">NISN: ${s.nisn || '-'}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span style="font-size:11.5px; font-weight:700; padding:2px 8px; border-radius:6px; background:#F1F5F9; color:#475569;">
                        Kelas ${s.kelas || '-'}
                    </span>
                </td>
                <td style="text-align:center;">
                    <div class="status-btn-group" id="grp_${sid}">
                        <input type="hidden" name="absensi[${sid}][status]" id="status_${sid}" value="hadir">
                        <button type="button" class="status-btn active is-hadir" onclick="setStatus(${sid}, 'hadir')">Hadir</button>
                        <button type="button" class="status-btn is-sakit" onclick="setStatus(${sid}, 'sakit')">Sakit</button>
                        <button type="button" class="status-btn is-izin" onclick="setStatus(${sid}, 'izin')">Izin</button>
                        <button type="button" class="status-btn is-alpa" onclick="setStatus(${sid}, 'alpa')">Alpa</button>
                    </div>
                </td>
                <td>
                    <input type="text" name="absensi[${sid}][keterangan]" class="form-control"
                           placeholder="Catatan..." style="font-size:12px; height:32px; padding:4px 8px;">
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    recalcStats();
}

// Ubah status per siswa
function setStatus(siswaId, newStatus) {
    const input = document.getElementById(`status_${siswaId}`);
    if (!input) return;
    input.value = newStatus;

    const grp = document.getElementById(`grp_${siswaId}`);
    if (!grp) return;

    grp.querySelectorAll('.status-btn').forEach(b => {
        b.classList.remove('active');
        if (b.classList.contains(`is-${newStatus}`)) {
            b.classList.add('active');
        }
    });

    recalcStats();
}

// Set semua status siswa sekaligus (misal: "Set Semua Hadir")
function setSemuaStatus(targetStatus) {
    document.querySelectorAll('[id^="status_"]').forEach(input => {
        const sid = input.id.replace('status_', '');
        setStatus(sid, targetStatus);
    });
}

// Hitung rekap jumlah status
function recalcStats() {
    let h = 0, s = 0, i = 0, a = 0;
    document.querySelectorAll('[id^="status_"]').forEach(inp => {
        const val = inp.value;
        if (val === 'hadir') h++;
        else if (val === 'sakit') s++;
        else if (val === 'izin') i++;
        else if (val === 'alpa') a++;
    });

    document.getElementById('count_hadir').textContent = h;
    document.getElementById('count_sakit').textContent = s;
    document.getElementById('count_izin').textContent = i;
    document.getElementById('count_alpa').textContent = a;
}

// Initial load
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('select_ekskul');
    if (sel && sel.value) {
        onEkskulChange(sel.value);
    }
});
</script>
@endsection
