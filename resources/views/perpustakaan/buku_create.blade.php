@extends('layouts.app')

@section('title', 'Tambah Buku')

@section('header-actions')
<a href="{{ route('perpustakaan.buku.index') }}" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    <span>Kembali ke Daftar Buku</span>
</a>
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:20px; max-width:860px; margin:0 auto; width:100%;">

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

    {{-- LANGKAH 1: SCAN BARCODE BUKU DENGAN KAMERA HP (OTOMATIS TERTULIS KE KODE BUKU) --}}
    <div class="form-section-card" style="padding:22px 24px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:38px; height:38px; border-radius:10px; background:rgba(37,99,235,0.1); color:#2563eb; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:19px;height:19px;">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                </div>
                <div>
                    <h2 style="font-size:16px; font-weight:700; color:var(--text-primary); margin:0;">1. Scan Barcode Buku (Kamera HP)</h2>
                    <span style="font-size:12px; color:var(--text-muted);">Arahkan kamera ke barcode buku — begitu terbaca langsung otomatis tertulis ke Kode Buku</span>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <button type="button" id="btnSwitchCamera" class="btn btn-secondary" style="display:none; align-items:center; gap:6px; padding:8px 14px; font-size:13px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                        <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                    </svg>
                    <span>Ganti Kamera</span>
                </button>

                <button type="button" id="btnToggleCamera" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:8px 16px; font-size:13px; font-weight:600;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                    <span id="camBtnText">Buka Kamera HP</span>
                </button>
            </div>
        </div>

        <div id="scannerWrapper" style="position:relative; width:100%; max-width:460px; margin:0 auto; border-radius:14px; overflow:hidden; background:#0B0F17; min-height:230px; display:flex; flex-direction:column; align-items:center; justify-content:center; border:2px dashed var(--border-light);">
            {{-- Live Video Stream dengan ZBar WebAssembly + Native BarcodeDetector --}}
            <video id="nativeBarcodeVideo" playsinline muted autoplay style="display:none; width:100%; max-height:290px; object-fit:cover;"></video>

            {{-- Garis Panduan Fokus Barcode --}}
            <div id="scanTargetOverlay" style="display:none; position:absolute; inset:0; pointer-events:none; align-items:center; justify-content:center;">
                <div style="width:80%; max-width:320px; height:125px; border:2px solid #38bdf8; border-radius:12px; box-shadow:0 0 0 9999px rgba(15,23,42,0.38); position:relative;">
                    <div style="position:absolute; left:10px; right:10px; top:50%; height:2px; background:#ef4444; box-shadow:0 0 8px #ef4444;"></div>
                </div>
            </div>

            <div id="scannerPlaceholder" style="padding:28px 20px; text-align:center; color:#94A3B8;">
                <div style="width:52px; height:52px; border-radius:50%; background:rgba(255,255,255,0.06); margin:0 auto 10px auto; display:flex; align-items:center; justify-content:center; color:#E2E8F0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:26px;height:26px;">
                        <path d="M3 5v14"></path>
                        <path d="M8 5v14"></path>
                        <path d="M12 5v14"></path>
                        <path d="M17 5v14"></path>
                        <path d="M21 5v14"></path>
                    </svg>
                </div>
                <div id="scannerStatusTitle" style="font-size:14px; font-weight:600; color:#F1F5F9; margin-bottom:4px;">Menyiapkan Kamera Pemindai Barcode...</div>
                <div id="scannerStatusDesc" style="font-size:12px; color:#94A3B8; max-width:320px; margin:0 auto;">Arahkan kamera HP ke barcode buku, angka barcode akan langsung tertulis otomatis pada kolom Kode Buku di bawah.</div>
            </div>
        </div>

        <div id="scanSuccessBanner" style="display:none; margin-top:14px; padding:10px 14px; border-radius:10px; background:#ecfdf5; border:1px solid #a7f3d0; color:#047857; font-size:13px; font-weight:600; align-items:center; gap:8px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:18px;height:18px;flex-shrink:0;">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <span id="scanSuccessText">Barcode berhasil terbaca dan otomatis ditulis ke Kode Buku!</span>
        </div>
    </div>

    {{-- LANGKAH 2: DETAIL BUKU & KODE BUKU --}}
    <div class="form-section-card" style="padding:24px 26px;">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:18px; padding-bottom:12px; border-bottom:1px solid var(--border-subtle);">
            <div style="width:36px; height:36px; border-radius:10px; background:rgba(37,99,235,0.1); color:#2563eb; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <div>
                <h2 style="font-size:16px; font-weight:700; color:var(--text-primary); margin:0;">2. Informasi Buku</h2>
                <span style="font-size:12px; color:var(--text-muted);">Kode buku otomatis terisi dari hasil scan barcode (atau kode otomatis jika tanpa barcode)</span>
            </div>
        </div>

        <form action="{{ route('perpustakaan.buku.store') }}" method="POST" id="formTambahBuku" style="display:flex; flex-direction:column; gap:16px;">
            @csrf
            <input type="hidden" name="barcode" id="input_barcode" value="{{ old('barcode') }}">

            {{-- Kode Buku (Hasil Scan Barcode / Otomatis) --}}
            <div class="form-group" style="margin:0;">
                <label for="kode_buku" class="form-label">Kode Buku (Barcode) <span class="required-mark">*</span></label>
                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="text"
                           name="kode_buku"
                           id="kode_buku"
                           value="{{ old('kode_buku', $nextKodeBuku) }}"
                           class="form-control"
                           placeholder="Otomatis tertulis saat barcode buku terbaca kamera..."
                           style="font-family:monospace; font-weight:700; font-size:14px; color:#1d4ed8; transition: box-shadow 0.25s ease, border-color 0.25s ease;"
                           required>
                    <button type="button"
                            id="btnGenerateKode"
                            class="btn btn-secondary"
                            style="white-space:nowrap; padding:9px 12px; font-size:12.5px;"
                            title="Buat Kode Buku Otomatis (Jika Buku Tidak Ada Barcode)">
                        Kode Otomatis
                    </button>
                </div>
            </div>

            {{-- Nama Buku --}}
            <div class="form-group" style="margin:0;">
                <label for="judul_buku" class="form-label">Nama / Judul Buku <span class="required-mark">*</span></label>
                <input type="text"
                       name="judul_buku"
                       id="judul_buku"
                       value="{{ old('judul_buku') }}"
                       class="form-control"
                       placeholder="Masukkan nama atau judul buku..."
                       required>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px;">
                <div class="form-group" style="margin:0;">
                    <label for="kategori" class="form-label">Kategori Buku</label>
                    <input type="text" name="kategori" id="kategori" value="{{ old('kategori') }}" class="form-control" placeholder="Contoh: Kitab, Pelajaran, Umum">
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="penulis" class="form-label">Penulis / Pengarang</label>
                    <input type="text" name="penulis" id="penulis" value="{{ old('penulis') }}" class="form-control" placeholder="Opsional">
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="stok_total" class="form-label">Jumlah Stok</label>
                    <input type="number" name="stok_total" id="stok_total" min="1" value="{{ old('stok_total', 1) }}" class="form-control" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="rak" class="form-label">Rak / Lokasi</label>
                    <input type="text" name="rak" id="rak" value="{{ old('rak') }}" class="form-control" placeholder="Contoh: Rak A1">
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; padding-top:8px; border-top:1px solid var(--border-subtle);">
                <a href="{{ route('perpustakaan.buku.index') }}" class="btn btn-secondary" style="padding:10px 18px;">Batal</a>
                <button type="submit" class="btn btn-primary" style="padding:10px 24px; font-weight:700;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <span>Simpan Buku</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ZBar WebAssembly + BarcodeDetector Polyfill (Mendukung iOS Safari, Android Chrome, & Desktop) --}}
<script src="{{ asset('js/vendor/zbar/zbar-wasm.js') }}" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/@undecaf/zbar-wasm@0.9.15/dist/index.js'"></script>
<script src="{{ asset('js/vendor/zbar/barcode-detector-polyfill.js') }}" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/@undecaf/barcode-detector-polyfill@0.9.21/dist/index.js'"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.BarcodeDetector === 'undefined' && typeof barcodeDetectorPolyfill !== 'undefined') {
        window.BarcodeDetector = barcodeDetectorPolyfill.BarcodeDetectorPolyfill;
    }

    var nativeStream = null;
    var scanTimer = null;
    var isScanning = false;
    var isDecodingFrame = false;
    var currentFacingMode = 'environment';

    var scanCanvas = document.createElement('canvas');
    var scanCtx = scanCanvas.getContext('2d', { willReadFrequently: true });
    var nativeDetector = null;

    var btnToggleCamera = document.getElementById('btnToggleCamera');
    var camBtnText = document.getElementById('camBtnText');
    var btnSwitchCamera = document.getElementById('btnSwitchCamera');
    var placeholder = document.getElementById('scannerPlaceholder');
    var scannerStatusTitle = document.getElementById('scannerStatusTitle');
    var nativeVideo = document.getElementById('nativeBarcodeVideo');
    var scanOverlay = document.getElementById('scanTargetOverlay');

    var inputBarcode = document.getElementById('input_barcode');
    var inputKode = document.getElementById('kode_buku');
    var inputJudul = document.getElementById('judul_buku');
    var btnGenerateKode = document.getElementById('btnGenerateKode');
    var scanBanner = document.getElementById('scanSuccessBanner');
    var scanText = document.getElementById('scanSuccessText');

    function fetchKodeBukuOtomatis() {
        fetch('{{ route('perpustakaan.buku.generate-kode') }}', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.kode_buku && inputKode) {
                inputKode.value = data.kode_buku;
                if (inputBarcode) inputBarcode.value = '';
            }
        })
        .catch(function () {});
    }

    if (btnGenerateKode) {
        btnGenerateKode.addEventListener('click', function () {
            fetchKodeBukuOtomatis();
        });
    }

    // Normalisasi UPC-A 12 digit yang diawali 0 pada hasil EAN-13
    function normalizeBarcode(raw) {
        var s = String(raw || '').trim();
        if (/^00\d{11}$/.test(s)) {
            return s.substring(1);
        }
        return s;
    }

    // Ketika barcode terbaca oleh kamera: langsung otomatis ditulis ke Kode Buku tanpa klik apapun
    function applyScannedBarcode(code) {
        if (!code) return;
        var cleanCode = normalizeBarcode(code);
        if (!cleanCode) return;

        if (inputKode) {
            inputKode.value = cleanCode;
            inputKode.style.borderColor = '#10b981';
            inputKode.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.25)';
            setTimeout(function () {
                inputKode.style.borderColor = '';
                inputKode.style.boxShadow = '';
            }, 2000);
        }

        if (inputBarcode) {
            inputBarcode.value = cleanCode;
        }

        if (scanBanner && scanText) {
            scanText.textContent = 'Barcode terbaca & otomatis ditulis ke Kode Buku: ' + cleanCode;
            scanBanner.style.display = 'flex';
        }

        if (navigator.vibrate) {
            try { navigator.vibrate(100); } catch (e) {}
        }

        stopScanner();

        if (inputJudul && !inputJudul.value.trim()) {
            inputJudul.focus();
        }
    }

    function setScanningUI(active) {
        isScanning = active;
        if (active) {
            if (placeholder) placeholder.style.display = 'none';
            if (scanOverlay) scanOverlay.style.display = 'flex';
            if (camBtnText) camBtnText.textContent = 'Matikan Kamera';
            if (btnToggleCamera) btnToggleCamera.className = 'btn btn-secondary';
            if (btnSwitchCamera) btnSwitchCamera.style.display = 'inline-flex';
        } else {
            if (nativeVideo) nativeVideo.style.display = 'none';
            if (scanOverlay) scanOverlay.style.display = 'none';
            if (placeholder) placeholder.style.display = 'block';
            if (scannerStatusTitle) scannerStatusTitle.textContent = 'Pemindai Kamera Nonaktif';
            if (camBtnText) camBtnText.textContent = 'Buka Kamera HP';
            if (btnToggleCamera) btnToggleCamera.className = 'btn btn-primary';
            if (btnSwitchCamera) btnSwitchCamera.style.display = 'none';
        }
    }

    async function decodeRegion(sx, sy, sw, sh, targetW, targetH) {
        if (sw <= 0 || sh <= 0) return null;
        scanCanvas.width = targetW;
        scanCanvas.height = targetH;
        scanCtx.drawImage(nativeVideo, sx, sy, sw, sh, 0, 0, targetW, targetH);
        var imgData = scanCtx.getImageData(0, 0, targetW, targetH);

        // 1. ZBar WebAssembly (C++ WASM — sangat akurat untuk 1D barcode di iOS & Android)
        if (typeof zbarWasm !== 'undefined' && typeof zbarWasm.scanImageData === 'function') {
            try {
                var symbols = await zbarWasm.scanImageData(imgData);
                if (symbols && symbols.length > 0) {
                    var val = symbols[0].decode();
                    if (val && val.trim()) return val.trim();
                }
            } catch (e) {}
        }

        // 2. BarcodeDetector (Native / Polyfill)
        if (nativeDetector) {
            try {
                var codes = await nativeDetector.detect(imgData);
                if (codes && codes.length > 0 && codes[0].rawValue) {
                    return codes[0].rawValue.trim();
                }
            } catch (e) {}
        }

        return null;
    }

    async function startScanner() {
        if (isScanning) return;
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setScanningUI(false);
            return;
        }

        try {
            if (!nativeDetector && typeof window.BarcodeDetector !== 'undefined') {
                try {
                    nativeDetector = new window.BarcodeDetector();
                } catch (e) {}
            }

            nativeStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: currentFacingMode },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            });

            nativeVideo.srcObject = nativeStream;
            nativeVideo.setAttribute('playsinline', 'true');
            nativeVideo.style.display = 'block';
            await nativeVideo.play();
            setScanningUI(true);

            scanTimer = setInterval(async function () {
                if (!isScanning || isDecodingFrame || !nativeVideo || nativeVideo.readyState < 2) return;
                var vw = nativeVideo.videoWidth;
                var vh = nativeVideo.videoHeight;
                if (!vw || !vh) return;

                isDecodingFrame = true;
                try {
                    // Pass 1: Area Tengah (Kotak Target Barcode) dengan resolusi tajam
                    var cropW = Math.round(vw * 0.8);
                    var cropH = Math.round(vh * 0.5);
                    var cropX = Math.round((vw - cropW) / 2);
                    var cropY = Math.round((vh - cropH) / 2);
                    var found = await decodeRegion(cropX, cropY, cropW, cropH, Math.min(800, cropW), Math.min(500, cropH));

                    // Pass 2: Full Frame jika barcode berada sedikit di luar kotak tengah
                    if (!found) {
                        found = await decodeRegion(0, 0, vw, vh, 640, Math.round(640 * (vh / vw)));
                    }

                    if (found) {
                        applyScannedBarcode(found);
                    }
                } catch (e) {
                } finally {
                    isDecodingFrame = false;
                }
            }, 110);
        } catch (err) {
            stopScanner();
        }
    }

    function stopScanner() {
        if (scanTimer) {
            clearInterval(scanTimer);
            scanTimer = null;
        }
        if (nativeStream) {
            nativeStream.getTracks().forEach(function (t) { t.stop(); });
            nativeStream = null;
        }
        if (nativeVideo) {
            nativeVideo.srcObject = null;
            nativeVideo.style.display = 'none';
        }
        isDecodingFrame = false;
        setScanningUI(false);
    }

    if (btnToggleCamera) {
        btnToggleCamera.addEventListener('click', function () {
            if (isScanning) {
                stopScanner();
            } else {
                startScanner();
            }
        });
    }

    if (btnSwitchCamera) {
        btnSwitchCamera.addEventListener('click', function () {
            currentFacingMode = (currentFacingMode === 'environment') ? 'user' : 'environment';
            stopScanner();
            setTimeout(function () {
                startScanner();
            }, 250);
        });
    }

    // Langsung aktifkan kamera otomatis saat halaman dibuka
    startScanner();
});
</script>
@endsection
