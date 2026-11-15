@extends('layouts.app')

@section('title', 'Pinjam Buku')

@section('header-actions')
<a href="{{ route('perpustakaan.pinjam.index') }}" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    <span>Kembali ke Daftar</span>
</a>
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:20px; width:100%; max-width:860px; margin:0 auto;">

    {{-- Alert Notifikasi --}}
    @if (session('success'))
    <div class="alert-banner alert-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    @if ($errors->any())
    <div class="alert-banner alert-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
            <strong>Terjadi kesalahan saat memproses data:</strong>
            <ul style="margin: 4px 0 0 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Card Form Pinjam Buku --}}
    <div class="form-section-card" style="padding:26px 28px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:22px; padding-bottom:14px; border-bottom:1px solid var(--border-subtle); flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:40px; height:40px; border-radius:10px; background-color:var(--electric-blue-light); color:var(--electric-blue); display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </div>
                <div>
                    <h2 style="font-size:17px; font-weight:700; color:var(--text-primary); margin:0;">Formulir Peminjaman Buku</h2>
                    <p style="font-size:12.5px; color:var(--text-secondary); margin:2px 0 0;">Scan barcode buku atau pilih buku dari daftar, lengkapi data peminjam, lalu klik Simpan</p>
                </div>
            </div>

            <button type="button" id="btnTogglePinjamScanner" class="btn btn-secondary" style="font-size:12.5px; padding:8px 14px;" onclick="togglePinjamScanner()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                    <circle cx="12" cy="13" r="4"></circle>
                </svg>
                <span id="txtTogglePinjamScanner">Scan Barcode Buku</span>
            </button>
        </div>

        {{-- Area Scanner Barcode Buku --}}
        <div id="pinjamScannerWrap" style="display:none; margin-bottom:20px; padding:16px; border-radius:12px; background:#f8fafc; border:1px dashed #cbd5e1;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                <span style="font-size:13px; font-weight:700; color:var(--text-primary);">Arahkan & Tahan Kamera pada Barcode Buku Hingga Terverifikasi</span>
                <button type="button" class="btn btn-sm btn-secondary" onclick="stopPinjamScanner()">Tutup Kamera</button>
            </div>
            <div style="width:100%; max-width:420px; margin:0 auto; border-radius:10px; overflow:hidden; background:#0f172a; position:relative; min-height:220px; display:flex; align-items:center; justify-content:center;">
                <video id="pinjamNativeVideo" playsinline muted autoplay style="display:none; width:100%; max-height:270px; object-fit:cover;"></video>
                <div id="pinjamScanOverlay" style="display:none; position:absolute; inset:0; pointer-events:none; flex-direction:column; align-items:center; justify-content:center; gap:10px;">
                    <div id="pinjamTargetBox" style="width:80%; max-width:300px; height:120px; border:2.5px solid #38bdf8; border-radius:12px; box-shadow:0 0 0 9999px rgba(15,23,42,0.42); position:relative; transition: border-color 0.2s ease;">
                        <div style="position:absolute; left:10px; right:10px; top:50%; height:2px; background:#ef4444; box-shadow:0 0 8px #ef4444;"></div>
                    </div>
                    <div id="pinjamHoldStatus" style="z-index:2; background:rgba(15,23,42,0.82); color:#f8fafc; font-size:12px; font-weight:600; padding:5px 12px; border-radius:999px; border:1px solid rgba(255,255,255,0.15);">
                        Arahkan & tahan kamera pada barcode...
                    </div>
                </div>
            </div>
        </div>

        <div id="pinjamScanFeedback" style="display:none; margin-bottom:16px; padding:10px 14px; border-radius:10px; font-size:13px; font-weight:600;"></div>

        <form action="{{ route('perpustakaan.pinjam.store') }}" method="POST" id="formPinjamBuku">
            @csrf

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:18px; margin-bottom:18px;">
                {{-- Kode Buku (Barcode) --}}
                <div class="form-group">
                    <label for="input_kode_buku_pinjam" class="form-label">Kode Buku (Barcode)</label>
                    <input type="text"
                           id="input_kode_buku_pinjam"
                           class="form-control"
                           placeholder="Otomatis tertulis saat scan barcode..."
                           style="font-family:monospace; font-weight:700; color:#1d4ed8; transition: box-shadow 0.25s ease, border-color 0.25s ease;">
                </div>

                {{-- Pilih Buku --}}
                <div class="form-group">
                    <label for="perpustakaan_buku_id" class="form-label required">Pilih Buku yang Dipinjam</label>
                    <select name="perpustakaan_buku_id" id="perpustakaan_buku_id" class="form-select" required>
                        <option value="">-- Pilih Buku Perpustakaan --</option>
                        @foreach ($bukuList as $buku)
                            @php
                                $isSelected = (string) old('perpustakaan_buku_id', $selectedBukuId) === (string) $buku->id;
                                $habis = $buku->stok_tersedia <= 0;
                                $kodeDisplay = $buku->kode_buku ?: $buku->barcode;
                            @endphp
                            <option value="{{ $buku->id }}"
                                    data-kode="{{ $kodeDisplay }}"
                                    data-barcode="{{ $buku->barcode }}"
                                    data-stok="{{ $buku->stok_tersedia }}"
                                    {{ $isSelected ? 'selected' : '' }}
                                    {{ $habis && !$isSelected ? 'disabled' : '' }}>
                                [{{ $kodeDisplay }}] {{ $buku->judul_buku }} — (Tersedia: {{ $buku->stok_tersedia }})
                            </option>
                        @endforeach
                    </select>
                    @if ($bukuList->isEmpty())
                        <small style="font-size:12px; color:#d97706; display:block; margin-top:6px;">
                            Belum ada data buku. <a href="{{ route('perpustakaan.buku.create') }}" style="font-weight:700; text-decoration:underline;">Tambah buku terlebih dahulu</a>.
                        </small>
                    @endif
                </div>

                {{-- Jumlah Eksemplar --}}
                <div class="form-group">
                    <label for="jumlah" class="form-label required">Jumlah Buku</label>
                    <input type="number" name="jumlah" id="jumlah" value="{{ old('jumlah', 1) }}" min="1" max="100" class="form-control" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:18px; margin-bottom:18px;">
                {{-- Nama Peminjam (Bisa Pilih Santri/Siswa atau Ketik Manual) --}}
                <div class="form-group" style="position:relative;">
                    <label for="input_nama_peminjam" class="form-label required">Nama Peminjam (Santri / Siswa)</label>
                    <div style="position:relative; display:flex; align-items:center;">
                        <input type="text"
                               name="nama_peminjam"
                               id="input_nama_peminjam"
                               value="{{ old('nama_peminjam') }}"
                               class="form-control"
                               placeholder="Ketik atau pilih nama peminjam..."
                               autocomplete="off"
                               required
                               style="padding-right:34px;">
                        <input type="hidden" name="siswa_id" id="siswa_id" value="{{ old('siswa_id') }}">
                        <div id="btn_toggle_santri" style="position:absolute; right:12px; cursor:pointer; color:var(--text-muted); display:flex; align-items:center; user-select:none;" title="Tampilkan daftar santri/siswa">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div id="list_prediksi_santri" class="autocomplete-dropdown"></div>
                </div>

                {{-- Kelas / Kamar --}}
                <div class="form-group">
                    <label for="kelas_kamar" class="form-label">Kelas / Kamar Asrama</label>
                    <input type="text"
                           name="kelas_kamar"
                           id="kelas_kamar"
                           value="{{ old('kelas_kamar') }}"
                           class="form-control"
                           placeholder="Otomatis terisi atau ketik manual">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:18px; margin-bottom:18px;">
                {{-- Tanggal Pinjam --}}
                <div class="form-group">
                    <label for="tanggal_pinjam" class="form-label required">Tanggal Pinjam</label>
                    <input type="date" name="tanggal_pinjam" id="tanggal_pinjam" value="{{ old('tanggal_pinjam', $today) }}" class="form-control" required>
                </div>

                {{-- Tanggal Estimasi Kembali --}}
                <div class="form-group">
                    <label for="tanggal_jatuh_tempo" class="form-label">Tanggal Estimasi Kembali</label>
                    <input type="date" name="tanggal_jatuh_tempo" id="tanggal_jatuh_tempo" value="{{ old('tanggal_jatuh_tempo', $defaultJatuhTempo) }}" class="form-control">
                </div>
            </div>

            <div class="form-group" style="margin-bottom:24px;">
                <label for="catatan" class="form-label">Catatan / Keterangan (Opsional)</label>
                <textarea name="catatan" id="catatan" rows="2" class="form-control" placeholder="Catatan tambahan peminjaman buku...">{{ old('catatan') }}</textarea>
            </div>

            <div style="display:flex; align-items:center; justify-content:flex-end; gap:10px; padding-top:16px; border-top:1px solid var(--border-subtle);">
                <a href="{{ route('perpustakaan.pinjam.index') }}" class="btn btn-secondary" style="padding:10px 18px;">Batal</a>
                <button type="submit" class="btn btn-primary" style="padding:10px 24px; font-weight:700;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <span>Simpan</span>
                </button>
            </div>
        </form>
    </div>
</div>

@php
    $santriJsonData = $daftarSiswa->map(function ($s) {
        return [
            'id'   => $s->id,
            'nama' => $s->nama_lengkap,
            'info' => implode(' • ', array_filter([$s->kelas, $s->kamar])),
        ];
    })->values();
@endphp
@push('scripts')
{{-- ZBar WebAssembly + BarcodeDetector Polyfill (Mendukung iOS Safari, Android Chrome, & Desktop) --}}
<script src="{{ asset('js/vendor/zbar/zbar-wasm.js') }}" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/@undecaf/zbar-wasm@0.9.15/dist/index.js'"></script>
<script src="{{ asset('js/vendor/zbar/barcode-detector-polyfill.js') }}" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/@undecaf/barcode-detector-polyfill@0.9.21/dist/index.js'"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.BarcodeDetector === 'undefined' && typeof barcodeDetectorPolyfill !== 'undefined') {
        window.BarcodeDetector = barcodeDetectorPolyfill.BarcodeDetectorPolyfill;
    }

    const dataSantri = @json($santriJsonData);

    const inputNama = document.getElementById('input_nama_peminjam');
    const inputSiswaId = document.getElementById('siswa_id');
    const inputKelasKamar = document.getElementById('kelas_kamar');
    const listPrediksi = document.getElementById('list_prediksi_santri');
    const btnToggle = document.getElementById('btn_toggle_santri');
    const selectBuku = document.getElementById('perpustakaan_buku_id');
    const inputKodePinjam = document.getElementById('input_kode_buku_pinjam');

    // Sinkronisasi Kode Buku <-> Pilih Buku
    if (selectBuku && inputKodePinjam) {
        function syncKodeFromSelectedOption() {
            const opt = selectBuku.options[selectBuku.selectedIndex];
            if (opt && opt.value) {
                inputKodePinjam.value = opt.getAttribute('data-kode') || opt.getAttribute('data-barcode') || '';
            }
        }
        syncKodeFromSelectedOption();

        selectBuku.addEventListener('change', function () {
            syncKodeFromSelectedOption();
        });

        inputKodePinjam.addEventListener('input', function () {
            const val = this.value.trim();
            if (val.length >= 3) {
                matchBookByScannedCode(val, true);
            }
        });
    }

    if (inputNama && listPrediksi) {
        let selectedIndex = -1;

        function renderPrediksi(query = '') {
            const q = query.trim().toLowerCase();
            const filtered = dataSantri.filter(s => !q || s.nama.toLowerCase().includes(q)).slice(0, 40);

            listPrediksi.innerHTML = '';
            selectedIndex = -1;

            if (filtered.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'autocomplete-empty';
                empty.textContent = 'Nama santri tidak ditemukan di daftar (tetap bisa disimpan manual)';
                listPrediksi.appendChild(empty);
            } else {
                filtered.forEach((item) => {
                    const el = document.createElement('div');
                    el.className = 'autocomplete-item';
                    el.textContent = item.nama;
                    el.setAttribute('data-id', item.id);
                    el.setAttribute('data-nama', item.nama);
                    el.setAttribute('data-info', item.info || '');

                    el.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        pilihSantri(item);
                    });

                    listPrediksi.appendChild(el);
                });
            }

            listPrediksi.classList.add('is-open');
        }

        function tutupPrediksi() {
            listPrediksi.classList.remove('is-open');
            selectedIndex = -1;
        }

        function pilihSantri(item) {
            inputNama.value = item.nama;
            inputSiswaId.value = item.id;
            if (item.info) {
                inputKelasKamar.value = item.info;
            }
            tutupPrediksi();
        }

        inputNama.addEventListener('input', function () {
            const val = this.value;
            renderPrediksi(val);

            const exact = dataSantri.find(s => s.nama.toLowerCase() === val.trim().toLowerCase());
            if (exact) {
                inputSiswaId.value = exact.id;
                if (exact.info && !inputKelasKamar.value) {
                    inputKelasKamar.value = exact.info;
                }
            } else {
                inputSiswaId.value = '';
            }
        });

        inputNama.addEventListener('focus', function () {
            renderPrediksi(this.value);
        });

        if (btnToggle) {
            btnToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                if (listPrediksi.classList.contains('is-open')) {
                    tutupPrediksi();
                } else {
                    inputNama.focus();
                    renderPrediksi(inputNama.value);
                }
            });
        }

        inputNama.addEventListener('keydown', function (e) {
            const items = listPrediksi.querySelectorAll('.autocomplete-item');
            if (!listPrediksi.classList.contains('is-open') || items.length === 0) {
                if (e.key === 'ArrowDown') {
                    renderPrediksi(this.value);
                }
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % items.length;
                items.forEach((it, idx) => it.classList.toggle('is-selected', idx === selectedIndex));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                items.forEach((it, idx) => it.classList.toggle('is-selected', idx === selectedIndex));
            } else if (e.key === 'Enter' && selectedIndex >= 0 && selectedIndex < items.length) {
                e.preventDefault();
                const it = items[selectedIndex];
                pilihSantri({
                    id: it.getAttribute('data-id'),
                    nama: it.getAttribute('data-nama'),
                    info: it.getAttribute('data-info')
                });
            } else if (e.key === 'Escape') {
                tutupPrediksi();
            }
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('#input_nama_peminjam, #list_prediksi_santri, #btn_toggle_santri')) {
                tutupPrediksi();
            }
        });
    }
});

// Scanner Barcode Buku pada Form Pinjam (ZBar WebAssembly + BarcodeDetector)
let pinjamNativeStream = null;
let pinjamNativeTimer = null;
let isPinjamScanning = false;
let isPinjamDecoding = false;
let pinjamCameraOpenedAt = 0;
let pinjamDetector = null;
let pinjamScanCanvas = document.createElement('canvas');
let pinjamScanCtx = pinjamScanCanvas.getContext('2d', { willReadFrequently: true });

const PINJAM_REQUIRED_MATCHES = 2;
let pinjamLastCandidate = '';
let pinjamCandidateCount = 0;

function formatPinjamSymbolResult(rawVal, typeName, formatName) {
    let val = String(rawVal || '').trim();
    if (!val) return null;

    const tName = String(typeName || '').toUpperCase();
    const fName = String(formatName || '').toLowerCase();

    if ((tName === 'ZBAR_I25' || fName === 'itf') && val.length < 10) {
        return null;
    }
    if (tName === 'ZBAR_EAN2' || tName === 'ZBAR_EAN5' || fName === 'ean_2' || fName === 'ean_5') {
        return null;
    }

    if ((tName === 'ZBAR_UPCA' || fName === 'upc_a') && /^\d{12}$/.test(val)) {
        val = '0' + val;
    }

    if (val.length < 6) {
        return null;
    }

    return val;
}

async function decodePinjamRegion(video, sx, sy, sw, sh, targetW, targetH) {
    if (sw <= 0 || sh <= 0) return null;
    pinjamScanCanvas.width = targetW;
    pinjamScanCanvas.height = targetH;
    pinjamScanCtx.drawImage(video, sx, sy, sw, sh, 0, 0, targetW, targetH);
    const imgData = pinjamScanCtx.getImageData(0, 0, targetW, targetH);

    if (typeof zbarWasm !== 'undefined' && typeof zbarWasm.scanImageData === 'function') {
        try {
            const symbols = await zbarWasm.scanImageData(imgData);
            if (symbols && symbols.length > 0) {
                for (let i = 0; i < symbols.length; i++) {
                    const sym = symbols[i];
                    const formatted = formatPinjamSymbolResult(sym.decode(), sym.typeName, '');
                    if (formatted) return formatted;
                }
            }
        } catch (e) {}
    }

    if (pinjamDetector) {
        try {
            const codes = await pinjamDetector.detect(imgData);
            if (codes && codes.length > 0) {
                for (let j = 0; j < codes.length; j++) {
                    const c = codes[j];
                    const formattedCode = formatPinjamSymbolResult(c.rawValue, '', c.format);
                    if (formattedCode) return formattedCode;
                }
            }
        } catch (e) {}
    }

    return null;
}

function resetPinjamConsensusUI() {
    pinjamLastCandidate = '';
    pinjamCandidateCount = 0;
    const box = document.getElementById('pinjamTargetBox');
    const hold = document.getElementById('pinjamHoldStatus');
    if (box) box.style.borderColor = '#38bdf8';
    if (hold) hold.textContent = 'Arahkan kamera pada barcode buku...';
}

async function togglePinjamScanner() {
    const wrap = document.getElementById('pinjamScannerWrap');
    const video = document.getElementById('pinjamNativeVideo');
    const overlay = document.getElementById('pinjamScanOverlay');
    const box = document.getElementById('pinjamTargetBox');
    const hold = document.getElementById('pinjamHoldStatus');

    if (isPinjamScanning) {
        await stopPinjamScanner();
        return;
    }

    wrap.style.display = 'block';
    resetPinjamConsensusUI();

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showPinjamScanFeedback('Browser tidak mendukung akses kamera.', true);
        return;
    }

    try {
        if (!pinjamDetector && typeof window.BarcodeDetector !== 'undefined') {
            try { pinjamDetector = new window.BarcodeDetector(); } catch (e) {}
        }

        pinjamNativeStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false
        });
        video.srcObject = pinjamNativeStream;
        video.setAttribute('playsinline', 'true');
        video.style.display = 'block';
        if (overlay) overlay.style.display = 'flex';
        await video.play();
        pinjamCameraOpenedAt = Date.now();
        isPinjamScanning = true;

        pinjamNativeTimer = setInterval(async () => {
            if (!isPinjamScanning || isPinjamDecoding || !video || video.readyState < 2) return;
            if (Date.now() - pinjamCameraOpenedAt < 350) return;

            const vw = video.videoWidth;
            const vh = video.videoHeight;
            if (!vw || !vh) return;

            isPinjamDecoding = true;
            try {
                const cropW = Math.round(vw * 0.8);
                const cropH = Math.round(vh * 0.5);
                const cropX = Math.round((vw - cropW) / 2);
                const cropY = Math.round((vh - cropH) / 2);

                let found = await decodePinjamRegion(video, cropX, cropY, cropW, cropH, Math.min(800, cropW), Math.min(500, cropH));
                if (!found) {
                    found = await decodePinjamRegion(video, 0, 0, vw, vh, 640, Math.round(640 * (vh / vw)));
                }

                if (found) {
                    if (found === pinjamLastCandidate) {
                        pinjamCandidateCount++;
                    } else {
                        pinjamLastCandidate = found;
                        pinjamCandidateCount = 1;
                    }

                    if (box) box.style.borderColor = '#10b981';
                    if (hold) {
                        hold.textContent = 'Mendeteksi: ' + found + '...';
                    }

                    if (pinjamCandidateCount >= PINJAM_REQUIRED_MATCHES) {
                        await stopPinjamScanner();
                        matchBookByScannedCode(found, false);
                    }
                }
            } catch (e) {
            } finally {
                isPinjamDecoding = false;
            }
        }, 115);
    } catch (e) {
        showPinjamScanFeedback('Tidak dapat membuka kamera. Pastikan izin kamera diizinkan pada browser.', true);
    }
}

async function stopPinjamScanner() {
    if (pinjamNativeTimer) {
        clearInterval(pinjamNativeTimer);
        pinjamNativeTimer = null;
    }
    if (pinjamNativeStream) {
        pinjamNativeStream.getTracks().forEach(t => t.stop());
        pinjamNativeStream = null;
    }
    const video = document.getElementById('pinjamNativeVideo');
    if (video) {
        video.srcObject = null;
        video.style.display = 'none';
    }
    const overlay = document.getElementById('pinjamScanOverlay');
    if (overlay) overlay.style.display = 'none';

    isPinjamScanning = false;
    isPinjamDecoding = false;
    resetPinjamConsensusUI();
    const wrap = document.getElementById('pinjamScannerWrap');
    if (wrap) wrap.style.display = 'none';
}

function matchBookByScannedCode(code, silentIfNotFound) {
    const select = document.getElementById('perpustakaan_buku_id');
    const inputKode = document.getElementById('input_kode_buku_pinjam');
    const clean = String(code || '').trim();

    // Langsung otomatis tulis ke kolom Kode Buku (Barcode)
    if (inputKode && !silentIfNotFound) {
        inputKode.value = clean;
        inputKode.style.borderColor = '#10b981';
        inputKode.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.25)';
        setTimeout(() => {
            inputKode.style.borderColor = '';
            inputKode.style.boxShadow = '';
        }, 2000);
    }

    if (navigator.vibrate && !silentIfNotFound) {
        try { navigator.vibrate(100); } catch (e) {}
    }

    if (!select) return;

    const cleanLower = clean.toLowerCase();
    const cleanNoZero = cleanLower.replace(/^0+/, '');
    let matchedOption = null;

    Array.from(select.options).forEach(opt => {
        if (!opt.value) return;
        const barcode = (opt.getAttribute('data-barcode') || '').toLowerCase().trim();
        const kode = (opt.getAttribute('data-kode') || '').toLowerCase().trim();
        const barcodeNoZero = barcode.replace(/^0+/, '');
        const kodeNoZero = kode.replace(/^0+/, '');

        if (
            (barcode && (barcode === cleanLower || (cleanNoZero && barcodeNoZero === cleanNoZero))) ||
            (kode && (kode === cleanLower || (cleanNoZero && kodeNoZero === cleanNoZero)))
        ) {
            matchedOption = opt;
        }
    });

    if (matchedOption) {
        select.value = matchedOption.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        select.dispatchEvent(new Event('fi-select:sync'));
        if (!silentIfNotFound) {
            showPinjamScanFeedback('Barcode "' + clean + '" terbaca — Buku terpilih: ' + matchedOption.textContent.trim(), false);
        }
    } else if (!silentIfNotFound) {
        showPinjamScanFeedback('Barcode "' + clean + '" berhasil terbaca, namun buku dengan kode tersebut belum terdaftar di Daftar Buku.', true);
    }
}

function showPinjamScanFeedback(msg, isError) {
    const el = document.getElementById('pinjamScanFeedback');
    if (!el) return;
    el.style.display = 'block';
    el.style.background = isError ? '#fef2f2' : '#ecfdf5';
    el.style.color = isError ? '#b91c1c' : '#047857';
    el.style.border = isError ? '1px solid #fecaca' : '1px solid #a7f3d0';
    el.textContent = msg;
}
</script>
@endpush
@endsection
