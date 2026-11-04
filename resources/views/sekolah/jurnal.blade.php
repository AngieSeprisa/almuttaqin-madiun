@extends('layouts.app')

@section('title', 'Jurnal Pembelajaran')

@section('header-actions')
<a href="{{ route('sekolah.rekap-jurnal') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
        <polyline points="14 2 14 8 20 8"></polyline>
        <line x1="16" y1="13" x2="8" y2="13"></line>
        <line x1="16" y1="17" x2="8" y2="17"></line>
    </svg>
    <span>Rekap Jurnal</span>
</a>
@endsection

@push('styles')
<style>
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
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

    <form action="{{ route('sekolah.jurnal.store') }}" method="POST" id="formJurnal" style="display:flex; flex-direction:column; gap:20px;">
        @csrf

        {{-- Kartu Form Data Jurnal --}}
        <div class="form-section-card">
            <div class="form-section-header">
                <div class="form-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </div>
                <div class="form-section-title-wrap">
                    <h2 class="form-section-title">Form Input Jurnal Pembelajaran</h2>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:18px;">
                
                {{-- 1. Guru memilih kelas --}}
                <div class="form-group">
                    <label for="select_kelas" class="form-label">Pilih Kelas <span class="required-mark">*</span></label>
                    <select name="kelas" id="select_kelas" class="form-select" required onchange="renderSiswaByKelas(this.value)">
                        @foreach($kelasList as $kelasOption)
                            @php
                                $isSel = old('kelas', $kelasList[0] ?? '') === $kelasOption
                                    || (old('kelas') && \App\Http\Controllers\PlottingKelasController::isSameClass(old('kelas'), $kelasOption));
                                $displayK = \App\Http\Controllers\PlottingKelasController::toRomanDisplay($kelasOption);
                            @endphp
                            <option value="{{ $kelasOption }}" {{ $isSel ? 'selected' : '' }}>
                                Kelas {{ $displayK }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 4. Hari & Tanggal --}}
                <div class="form-group">
                    <label for="input_tanggal" class="form-label">Tanggal <span class="required-mark">*</span></label>
                    <input type="date" name="tanggal" id="input_tanggal" class="form-control"
                           value="{{ old('tanggal', $periode['tanggal']) }}" required onchange="updatePeriodeOtomatis(this.value)">
                </div>

                {{-- Hari (Otomatis) --}}
                <div class="form-group">
                    <label for="input_hari" class="form-label">Hari (Otomatis)</label>
                    <input type="text" name="hari" id="input_hari" class="form-control"
                           value="{{ old('hari', $periode['hari']) }}" readonly style="background:var(--bg-card-header); cursor:not-allowed;">
                </div>

                {{-- 2. Semester (Otomatis) --}}
                <div class="form-group">
                    <label for="input_semester" class="form-label">Semester (Otomatis)</label>
                    <input type="text" name="semester" id="input_semester" class="form-control"
                           value="{{ old('semester', $periode['semester']) }}" readonly style="background:var(--bg-card-header); cursor:not-allowed;">
                </div>

                {{-- 3. Tahun Pelajaran (Otomatis) --}}
                <div class="form-group">
                    <label for="input_tahun_pelajaran" class="form-label">Tahun Pelajaran (Otomatis)</label>
                    <input type="text" name="tahun_pelajaran" id="input_tahun_pelajaran" class="form-control"
                           value="{{ old('tahun_pelajaran', $periode['tahun_pelajaran']) }}" readonly style="background:var(--bg-card-header); cursor:not-allowed;">
                </div>

                {{-- 7. Jam Pelajaran --}}
                <div class="form-group">
                    <label for="jam_ke" class="form-label">Jam Pelajaran <span class="required-mark">*</span></label>
                    <input type="text" name="jam_ke" id="jam_ke" class="form-control"
                           value="{{ old('jam_ke') }}" placeholder="Contoh: Jam 1 - 2 (07:30 - 09:00)" required>
                </div>

                {{-- 5. Nama Guru Pengajar --}}
                <div class="form-group" style="grid-column: span 1;">
                    <label for="guru_id" class="form-label">Nama Guru Pengajar <span class="required-mark">*</span></label>
                    <select name="guru_id" id="guru_id" class="form-select" required onchange="updateMataPelajaran(this)">
                        <option value="">-- Pilih Guru Pengajar --</option>
                        @foreach($gurus as $guru)
                            @php
                                $isSelected = old('guru_id') == $guru->id || (!old('guru_id') && auth()->id() == $guru->id);
                                $guruMapel = $guru->mata_pelajaran ?? '';
                                $guruMapels = !empty($guru->daftar_mata_pelajaran) ? implode('|', $guru->daftar_mata_pelajaran) : $guruMapel;
                                $primaryKode = strtolower($guru->primaryLembaga?->kode ?? '');
                                $pivotKodes = $guru->lembagas ? $guru->lembagas->pluck('kode')->map(fn($k) => strtolower((string)$k))->toArray() : [];
                                $isSmp = in_array('smp', array_merge([$primaryKode], $pivotKodes));
                                $isSma = in_array('sma', array_merge([$primaryKode], $pivotKodes));
                                $guruJenjang = ($isSmp && $isSma) ? 'both' : ($isSmp ? 'smp' : ($isSma ? 'sma' : 'all'));
                            @endphp
                            <option value="{{ $guru->id }}"
                                    data-mapel="{{ $guruMapel }}"
                                    data-mapels="{{ $guruMapels }}"
                                    data-jenjang="{{ $guruJenjang }}"
                                    {{ $isSelected ? 'selected' : '' }}>
                                {{ $guru->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 6. Mata Pelajaran (Otomatis sesuai guru) --}}
                <div class="form-group" style="grid-column: span 1;">
                    <label for="mata_pelajaran" class="form-label">Mata Pelajaran <span class="required-mark">*</span></label>
                    <input type="text" name="mata_pelajaran" id="mata_pelajaran" class="form-control"
                           value="{{ old('mata_pelajaran') }}" placeholder="Mata pelajaran guru (otomatis terisi)" required list="list_mata_pelajaran" autocomplete="off">
                    <datalist id="list_mata_pelajaran"></datalist>
                    <div id="mapel_pills_wrap" style="display:none; align-items:center; gap:6px; flex-wrap:wrap; margin-top:6px;">
                        <span style="font-size:11.5px; color:#64748b; font-weight:600;">Pilihan Mapel:</span>
                        <div id="mapel_pills_container" style="display:flex; gap:6px; flex-wrap:wrap;"></div>
                    </div>
                </div>

            </div>

            <div style="display:grid; grid-template-columns: 1fr; gap:16px; margin-top:16px;">
                {{-- 8. Materi Pembelajaran --}}
                <div class="form-group">
                    <label for="materi" class="form-label">Materi Pembelajaran <span class="required-mark">*</span></label>
                    <textarea name="materi" id="materi" class="form-control" rows="3"
                              placeholder="Ketik materi pembelajaran yang disampaikan..." required style="resize:vertical;">{{ old('materi') }}</textarea>
                </div>

                {{-- 9. Catatan --}}
                <div class="form-group">
                    <label for="catatan" class="form-label">Catatan</label>
                    <textarea name="catatan" id="catatan" class="form-control" rows="2"
                              placeholder="Ketik catatan tambahan / evaluasi pembelajaran (opsional)..." style="resize:vertical;">{{ old('catatan') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Kartu Absensi Siswa --}}
        <div class="form-section-card">
            <div class="form-section-header" style="justify-content:space-between; flex-wrap:wrap; gap:12px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="form-section-icon" style="background:rgba(16, 185, 129, 0.12); color:#059669;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="form-section-title-wrap">
                        <h2 class="form-section-title">Absensi Siswa Kelas <span id="label_nama_kelas"></span></h2>
                        <span style="font-size:12.5px; color:var(--text-secondary);">Klik tombol status untuk mengubah kehadiran siswa</span>
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
                    <a href="{{ route('plotting-kelas.index') }}" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size:11.5px; padding:4px 10px; display:inline-flex; align-items:center; gap:6px;" title="Kelola pembagian siswa per kelas di menu Plotting Kelas">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>Plotting Kelas</span>
                    </a>
                </div>
            </div>

            {{-- Tabel Daftar Siswa --}}
            <div class="table-card" style="box-shadow:none; border:1px solid var(--border-light); margin-top:8px;">
                <div class="table-wrapper">
                    <table class="modern-table" id="tableSiswaAbsensi">
                        <thead>
                            <tr>
                                <th style="width:50px; text-align:center;">No</th>
                                <th>Nama Siswa</th>
                                <th style="width:180px; text-align:center;">Status Kehadiran</th>
                                <th>Keterangan (Opsional)</th>
                            </tr>
                        </thead>
                        <tbody id="siswaTbody">
                            {{-- Baris siswa akan dimuat otomatis oleh JavaScript berdasarkan kelas terpilih --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tombol Aksi Simpan --}}
        <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:4px;">
            <a href="{{ route('sekolah.rekap-jurnal') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" id="btnSubmitJurnal" style="min-width:180px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>Simpan Jurnal</span>
            </button>
        </div>

    </form>

</div>

{{-- DATA SISWA PER KELAS & LOGIKA INTERAKTIF --}}
<script>
    const dataSiswasByKelas = @json($siswasByKelas);
    const statusCycle = ['hadir', 'sakit', 'izin', 'alpa'];
    const statusLabels = {
        'hadir': 'Hadir',
        'sakit': 'Sakit',
        'izin': 'Izin',
        'alpa': 'Alpa'
    };

    function toRomanDisplayJs(namaKelas) {
        if (!namaKelas) return '';
        let clean = String(namaKelas).replace(/^Kelas\s+/i, '').trim();
        const map = [
            [/^7([A-Za-z0-9\s_-].*|$)/, 'VII$1'],
            [/^8([A-Za-z0-9\s_-].*|$)/, 'VIII$1'],
            [/^9([A-Za-z0-9\s_-].*|$)/, 'IX$1'],
            [/^10([A-Za-z0-9\s_-].*|$)/, 'X$1'],
            [/^11([A-Za-z0-9\s_-].*|$)/, 'XI$1'],
            [/^12([A-Za-z0-9\s_-].*|$)/, 'XII$1']
        ];
        for (const [pattern, replacement] of map) {
            if (pattern.test(clean)) {
                let formatted = clean.replace(pattern, replacement);
                return formatted.replace(/^(VII|VIII|IX|X|XI|XII)([A-Za-z])$/, '$1 $2');
            }
        }
        return clean;
    }

    function findSiswaByKelas(kelasName) {
        if (!kelasName) return [];
        if (dataSiswasByKelas[kelasName] && dataSiswasByKelas[kelasName].length > 0) {
            return dataSiswasByKelas[kelasName];
        }
        const clean = String(kelasName).replace(/^Kelas\s+/i, '').trim();
        if (dataSiswasByKelas[clean] && dataSiswasByKelas[clean].length > 0) {
            return dataSiswasByKelas[clean];
        }
        const roman = toRomanDisplayJs(clean);
        if (dataSiswasByKelas[roman] && dataSiswasByKelas[roman].length > 0) {
            return dataSiswasByKelas[roman];
        }
        const normKey = (str) => {
            let s = String(str || '').replace(/^Kelas\s+/i, '').trim();
            s = s.replace(/\bVII\b/gi, '7')
                 .replace(/\bVIII\b/gi, '8')
                 .replace(/\bIX\b/gi, '9')
                 .replace(/\bX\b/gi, '10')
                 .replace(/\bXI\b/gi, '11')
                 .replace(/\bXII\b/gi, '12');
            return s.replace(/[\s\-_]/g, '').toUpperCase();
        };
        const targetNorm = normKey(kelasName);
        for (const [key, val] of Object.entries(dataSiswasByKelas)) {
            if (normKey(key) === targetNorm && val && val.length > 0) {
                return val;
            }
        }
        return dataSiswasByKelas[kelasName] || [];
    }

    // 1. Render Baris Siswa
    function renderRows(list) {
        const tbody = document.getElementById('siswaTbody');
        tbody.innerHTML = '';

        if (!list || list.length === 0) {
            const displayLabel = document.getElementById('label_nama_kelas').textContent || '-';
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" style="text-align:center; padding:32px 16px; color:var(--text-secondary);">
                        <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:36px;height:36px;opacity:0.4;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <span style="font-size:13.5px; font-weight:500;">Belum ada data siswa terdaftar di Kelas ${displayLabel} pada Plotting Kelas</span>
                            <a href="{{ route('plotting-kelas.index') }}" target="_blank" class="btn btn-sm btn-outline-primary" style="font-size:12px; margin-top:4px;">
                                Buka Menu Plotting Kelas &rarr;
                            </a>
                        </div>
                    </td>
                </tr>
            `;
            updateStatCounters();
            return;
        }

        list.forEach((siswa, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="text-align:center; font-weight:600; color:var(--text-secondary);">${index + 1}</td>
                <td class="cell-nama">
                    <div style="font-weight:600; color:var(--text-primary);">${escapeHtml(siswa.nama_lengkap)}</div>
                    <input type="hidden" name="absensi[${siswa.id}][nama]" value="${escapeHtml(siswa.nama_lengkap)}">
                </td>
                <td style="text-align:center;">
                    <input type="hidden" name="absensi[${siswa.id}][status]" id="status_${siswa.id}" value="hadir" class="input-status-val">
                    <button type="button" class="btn-att-toggle att-hadir" id="btn_toggle_${siswa.id}" onclick="toggleStatusSiswa(${siswa.id})">
                        <span class="dot-indicator" style="width:7px; height:7px; border-radius:50%; background:currentColor;"></span>
                        <span id="text_status_${siswa.id}">Hadir</span>
                    </button>
                </td>
                <td>
                    <input type="text" name="absensi[${siswa.id}][keterangan]" class="form-control"
                           placeholder="Keterangan (misal: surat dokter, izin keluarga)" style="font-size:12.5px; padding:6px 10px;">
                </td>
            `;
            tbody.appendChild(tr);
        });

        updateStatCounters();
    }

    // 2. Render Siswa saat kelas dipilih (Preload + Realtime AJAX fallback ke Plotting Kelas)
    function renderSiswaByKelas(kelasName) {
        const tbody = document.getElementById('siswaTbody');
        const labelKelas = document.getElementById('label_nama_kelas');
        const displayLabel = toRomanDisplayJs(kelasName) || kelasName || '-';
        labelKelas.textContent = displayLabel;

        const list = findSiswaByKelas(kelasName);

        if (list && list.length > 0) {
            renderRows(list);
            return;
        }

        // Tampilkan status memuat dari database Plotting Kelas
        tbody.innerHTML = `
            <tr>
                <td colspan="4" style="text-align:center; padding:28px 16px; color:var(--text-secondary);">
                    <div style="display:flex; align-items:center; justify-content:center; gap:10px;">
                        <span style="display:inline-block; width:14px; height:14px; border:2px solid currentColor; border-right-color:transparent; border-radius:50%; animation:spin 0.75s linear infinite;"></span>
                        <span style="font-size:13px;">Menghubungkan & memuat data dari Plotting Kelas...</span>
                    </div>
                </td>
            </tr>
        `;

        // Live AJAX fetch langsung ke Plotting Kelas
        fetch(`/sekolah/kelas-anggota/${encodeURIComponent(kelasName)}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(res => {
            if (res && res.success && res.anggota && res.anggota.length > 0) {
                dataSiswasByKelas[kelasName] = res.anggota;
                const roman = toRomanDisplayJs(kelasName);
                if (roman) dataSiswasByKelas[roman] = res.anggota;
                renderRows(res.anggota);
            } else {
                renderRows([]);
            }
        })
        .catch(() => {
            renderRows([]);
        });
    }

    // 2. Tombol toggle berganti status saat diklik: Hadir -> Sakit -> Izin -> Alpa -> Hadir
    function toggleStatusSiswa(siswaId) {
        const inputStatus = document.getElementById('status_' + siswaId);
        const btnToggle = document.getElementById('btn_toggle_' + siswaId);
        const textStatus = document.getElementById('text_status_' + siswaId);

        let current = inputStatus.value.toLowerCase();
        let currentIndex = statusCycle.indexOf(current);
        let nextIndex = (currentIndex + 1) % statusCycle.length;
        let nextStatus = statusCycle[nextIndex];

        // Terapkan status baru
        inputStatus.value = nextStatus;
        textStatus.textContent = statusLabels[nextStatus];

        // Perbarui class tombol
        btnToggle.className = 'btn-att-toggle att-' + nextStatus;

        updateStatCounters();
    }

    // 3. Set Semua Status sekaligus (misal set semua Hadir)
    function setSemuaStatus(targetStatus) {
        document.querySelectorAll('.input-status-val').forEach(input => {
            input.value = targetStatus;
            const siswaId = input.id.replace('status_', '');
            const btnToggle = document.getElementById('btn_toggle_' + siswaId);
            const textStatus = document.getElementById('text_status_' + siswaId);
            if (btnToggle && textStatus) {
                btnToggle.className = 'btn-att-toggle att-' + targetStatus;
                textStatus.textContent = statusLabels[targetStatus];
            }
        });
        updateStatCounters();
    }

    // 4. Update Ringkasan Stat Counter
    function updateStatCounters() {
        let hadir = 0, sakit = 0, izin = 0, alpa = 0;
        document.querySelectorAll('.input-status-val').forEach(input => {
            const val = input.value.toLowerCase();
            if (val === 'hadir') hadir++;
            else if (val === 'sakit') sakit++;
            else if (val === 'izin') izin++;
            else if (val === 'alpa') alpa++;
        });

        document.getElementById('count_hadir').textContent = hadir;
        document.getElementById('count_sakit').textContent = sakit;
        document.getElementById('count_izin').textContent = izin;
        document.getElementById('count_alpa').textContent = alpa;
    }

    // 5. Update Periode Otomatis (Hari, Semester, Tahun Pelajaran) saat tanggal diubah
    function updatePeriodeOtomatis(tanggalStr) {
        if (!tanggalStr) return;
        const d = new Date(tanggalStr + 'T00:00:00');
        const hariList = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const hari = hariList[d.getDay()] || 'Senin';

        const month = d.getMonth() + 1; // 1-12
        const year = d.getFullYear();

        let semester = '';
        let tahunPelajaran = '';

        if (month >= 7) {
            semester = 'Semester Ganjil';
            tahunPelajaran = year + '/' + (year + 1);
        } else {
            semester = 'Semester Genap';
            tahunPelajaran = (year - 1) + '/' + year;
        }

        document.getElementById('input_hari').value = hari;
        document.getElementById('input_semester').value = semester;
        document.getElementById('input_tahun_pelajaran').value = tahunPelajaran;
    }

    // 6. Update Mata Pelajaran Otomatis saat Guru dipilih
    function updateMataPelajaran(selectElem) {
        if (!selectElem) return;
        const selectedOption = selectElem.selectedIndex >= 0 ? selectElem.options[selectElem.selectedIndex] : null;
        const mapelInput = document.getElementById('mata_pelajaran');
        if (!mapelInput) return;

        const datalist = document.getElementById('list_mata_pelajaran');
        if (datalist) datalist.innerHTML = '';

        const pillsWrap = document.getElementById('mapel_pills_wrap');
        const pillsContainer = document.getElementById('mapel_pills_container');
        if (pillsContainer) pillsContainer.innerHTML = '';

        if (selectedOption && selectedOption.value) {
            const mapel = selectedOption.getAttribute('data-mapel') || '';
            const rawMapels = selectedOption.getAttribute('data-mapels') || mapel;
            const mapelList = rawMapels
                ? rawMapels.split(/[|,]/).map(s => s.trim()).filter(Boolean)
                : (mapel ? [mapel.trim()] : []);

            if (mapelList.length > 0) {
                // Isi otomatis dengan mapel utama guru
                mapelInput.value = mapelList[0];

                // Daftarkan ke datalist
                if (datalist) {
                    mapelList.forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = m;
                        datalist.appendChild(opt);
                    });
                }

                // Jika guru memiliki lebih dari 1 mapel pengampu, tampilkan pills pilihan cepat
                if (mapelList.length > 1 && pillsWrap && pillsContainer) {
                    pillsWrap.style.display = 'flex';
                    mapelList.forEach((m, idx) => {
                        const pill = document.createElement('button');
                        pill.type = 'button';
                        const isFirst = (idx === 0);
                        pill.style.cssText = 'padding:3px 10px; font-size:11.5px; border-radius:6px; font-weight:600; cursor:pointer; transition:all 0.15s; border:1px solid ' +
                            (isFirst ? '#2563eb; background:#2563eb; color:#ffffff;' : '#bfdbfe; background:#eff6ff; color:#1d4ed8;');
                        pill.textContent = m;
                        pill.onclick = function(e) {
                            e.preventDefault();
                            mapelInput.value = m;
                            Array.from(pillsContainer.children).forEach(p => {
                                p.style.background = '#eff6ff';
                                p.style.color = '#1d4ed8';
                                p.style.borderColor = '#bfdbfe';
                            });
                            pill.style.background = '#2563eb';
                            pill.style.color = '#ffffff';
                            pill.style.borderColor = '#2563eb';
                        };
                        pillsContainer.appendChild(pill);
                    });
                } else if (pillsWrap) {
                    pillsWrap.style.display = 'none';
                }
            } else if (mapel) {
                mapelInput.value = mapel;
                if (pillsWrap) pillsWrap.style.display = 'none';
            } else {
                mapelInput.value = '';
                if (pillsWrap) pillsWrap.style.display = 'none';
            }
        } else {
            mapelInput.value = '';
            if (pillsWrap) pillsWrap.style.display = 'none';
        }
    }

    // Filter daftar guru jika berada pada mode Yayasan (lintas jenjang SMP & SMA)
    function filterGurusByKelas(kelasName) {
        const guruSelect = document.getElementById('guru_id');
        if (!guruSelect || !kelasName) return;

        const kUpper = kelasName.trim().toUpperCase();
        let targetJenjang = null;
        if (kUpper.includes('VII') || kUpper.includes('VIII') || kUpper.includes('IX') || /^[789]\b/.test(kUpper)) {
            targetJenjang = 'smp';
        } else if (kUpper.includes('X') || kUpper.includes('XI') || kUpper.includes('XII') || /^(10|11|12)\b/.test(kUpper)) {
            targetJenjang = 'sma';
        }

        if (!targetJenjang) return;

        let hasSpecific = false;
        Array.from(guruSelect.options).forEach(opt => {
            const j = opt.getAttribute('data-jenjang');
            if (j && j !== 'both' && j !== 'all') {
                hasSpecific = true;
            }
        });

        if (!hasSpecific) return;

        let currentValid = false;
        Array.from(guruSelect.options).forEach(opt => {
            if (!opt.value) return;
            const j = opt.getAttribute('data-jenjang');
            const match = !j || j === 'all' || j === 'both' || j === targetJenjang;
            opt.disabled = !match;
            opt.hidden = !match;
            if (opt.selected && match) {
                currentValid = true;
            }
        });

        if (!currentValid && guruSelect.value) {
            guruSelect.value = '';
            guruSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Inisialisasi awal saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        const kelasSelect = document.getElementById('select_kelas');
        if (kelasSelect) {
            renderSiswaByKelas(kelasSelect.value);
            filterGurusByKelas(kelasSelect.value);
            kelasSelect.addEventListener('change', function() {
                filterGurusByKelas(this.value);
            });
        }

        const guruSelect = document.getElementById('guru_id');
        const mapelInput = document.getElementById('mata_pelajaran');
        if (guruSelect) {
            guruSelect.addEventListener('change', function() {
                updateMataPelajaran(this);
            });
            // Jalankan update awal jika sudah ada guru terpilih atau jika mapel masih kosong
            if (guruSelect.value && (!mapelInput || !mapelInput.value)) {
                updateMataPelajaran(guruSelect);
            }
        }
    });
</script>
@endsection
