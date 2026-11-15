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
                    <span style="font-size:12px; color:var(--text-muted);">Arahkan & tahan kamera sebentar pada barcode buku hingga terverifikasi penuh</span>
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
            {{-- Live Video Stream dengan ZBar WebAssembly + Verifikasi Stabil --}}
            <video id="nativeBarcodeVideo" playsinline muted autoplay style="display:none; width:100%; max-height:290px; object-fit:cover;"></video>

            {{-- Garis Panduan Fokus Barcode & Indikator Verifikasi --}}
            <div id="scanTargetOverlay" style="display:none; position:absolute; inset:0; pointer-events:none; flex-direction:column; align-items:center; justify-content:center; gap:10px;">
                <div id="scanTargetBox" style="width:80%; max-width:320px; height:125px; border:2.5px solid #38bdf8; border-radius:12px; box-shadow:0 0 0 9999px rgba(15,23,42,0.42); position:relative; transition: border-color 0.2s ease;">
                    <div id="scanLaserLine" style="position:absolute; left:10px; right:10px; top:50%; height:2px; background:#ef4444; box-shadow:0 0 8px #ef4444;"></div>
                </div>
                <div id="scanHoldStatus" style="z-index:2; background:rgba(15,23,42,0.82); color:#f8fafc; font-size:12px; font-weight:600; padding:5px 12px; border-radius:999px; border:1px solid rgba(255,255,255,0.15);">
                    Arahkan & tahan kamera pada barcode...
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
                <div id="scannerStatusDesc" style="font-size:12px; color:#94A3B8; max-width:320px; margin:0 auto;">Arahkan kamera HP ke barcode buku, angka barcode akan langsung tertulis otomatis setelah terbaca utuh.</div>
            </div>
        </div>

        <div id="scanSuccessBanner" style="display:none; margin-top:14px; padding:10px 14px; border-radius:10px; background:#ecfdf5; border:1px solid #a7f3d0; color:#047857; font-size:13px; font-weight:600; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:18px;height:18px;flex-shrink:0;">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span id="scanSuccessText">Barcode berhasil terbaca dan otomatis ditulis ke Kode Buku!</span>
            </div>
            <button type="button" id="btnRescanBarcode" class="btn btn-sm btn-secondary" style="font-size:12px; padding:4px 10px;">
                Scan Ulang
            </button>
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

{{-- ZBar WebAssembly + BarcodeDetector Polyfill --}}
<script src="{{ asset('js/vendor/zbar/zbar-wasm.js') }}" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/@undecaf/zbar-wasm@0.9.15/dist/index.js'"></script>
<script src="{{ asset('js/vendor/zbar/barcode-detector-polyfill.js') }}" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/@undecaf/barcode-detector-polyfill@0.9.21/dist/index.js'"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var nativeStream = null;
    var scanTimer = null;
    var isScanning = false;
    var isDecodingFrame = false;
    var currentFacingMode = 'environment';
    var cameraOpenedAt = 0;

    // Konsensus multi-frame agar tidak salah baca / terpotong saat kamera baru diarahkan
    var REQUIRED_CONSENSUS = 4;
    var lastCandidateCode = '';
    var candidateStreak = 0;

    var scanCanvas = document.createElement('canvas');
    var scanCtx = scanCanvas.getContext('2d', { willReadFrequently: true });
    var configuredZbarScanner = null;

    var btnToggleCamera = document.getElementById('btnToggleCamera');
    var camBtnText = document.getElementById('camBtnText');
    var btnSwitchCamera = document.getElementById('btnSwitchCamera');
    var btnRescanBarcode = document.getElementById('btnRescanBarcode');
    var placeholder = document.getElementById('scannerPlaceholder');
    var scannerStatusTitle = document.getElementById('scannerStatusTitle');
    var nativeVideo = document.getElementById('nativeBarcodeVideo');
    var scanOverlay = document.getElementById('scanTargetOverlay');
    var scanTargetBox = document.getElementById('scanTargetBox');
    var scanHoldStatus = document.getElementById('scanHoldStatus');

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

    // Validasi keutuhan angka barcode (panjang standar & Check Digit Modulo-10 GS1 untuk EAN/UPC)
    function isValidCompleteBarcode(raw) {
        var code = String(raw || '').trim();
        if (!code || code.length < 8) return false;

        if (/^\d+$/.test(code)) {
            if (code.length !== 8 && code.length !== 12 && code.length !== 13) {
                return false;
            }
            var sum = 0;
            var len = code.length;
            for (var i = 0; i < len - 1; i++) {
                var digit = parseInt(code.charAt(i), 10);
                var posFromRight = (len - 1) - i;
                var weight = (posFromRight % 2 === 1) ? 3 : 1;
                sum += digit * weight;
            }
            var checkDigit = (10 - (sum % 10)) % 10;
            return checkDigit === parseInt(code.charAt(len - 1), 10);
        }

        return code.length >= 8;
    }

    // Inisialisasi ZBarScanner khusus format utuh (Matikan I25/Codabar/Databar/EAN2/EAN5/UPCA agar angka tidak terpotong)
    async function getConfiguredZbarScanner() {
        if (configuredZbarScanner) return configuredZbarScanner;
        if (typeof zbarWasm === 'undefined' || typeof zbarWasm.ZBarScanner === 'undefined') return null;

        try {
            var scanner = await zbarWasm.ZBarScanner.create();
            var T = zbarWasm.ZBarSymbolType;
            var C = zbarWasm.ZBarConfigType;

            scanner.setConfig(T.ZBAR_NONE, C.ZBAR_CFG_ENABLE, 0);
            scanner.setConfig(T.ZBAR_EAN13, C.ZBAR_CFG_ENABLE, 1);
            scanner.setConfig(T.ZBAR_EAN8, C.ZBAR_CFG_ENABLE, 1);
            scanner.setConfig(T.ZBAR_CODE128, C.ZBAR_CFG_ENABLE, 1);
            scanner.setConfig(T.ZBAR_CODE39, C.ZBAR_CFG_ENABLE, 1);
            scanner.setConfig(T.ZBAR_QRCODE, C.ZBAR_CFG_ENABLE, 1);

            configuredZbarScanner = scanner;
            return scanner;
        } catch (e) {
            return null;
        }
    }

    function applyScannedBarcode(code) {
        var cleanCode = String(code || '').trim();
        if (!cleanCode) return;

        if (inputKode) {
            inputKode.value = cleanCode;
            inputKode.style.borderColor = '#10b981';
            inputKode.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.25)';
            setTimeout(function () {
                inputKode.style.borderColor = '';
                inputKode.style.boxShadow = '';
            }, 2200);
        }

        if (inputBarcode) {
            inputBarcode.value = cleanCode;
        }

        if (scanBanner && scanText) {
            scanText.textContent = 'Barcode terbaca lengkap & otomatis ditulis: ' + cleanCode;
            scanBanner.style.display = 'flex';
        }

        if (navigator.vibrate) {
            try { navigator.vibrate([80, 40, 80]); } catch (e) {}
        }

        stopScanner();

        if (inputJudul && !inputJudul.value.trim()) {
            inputJudul.focus();
        }
    }

    function resetConsensusUI() {
        lastCandidateCode = '';
        candidateStreak = 0;
        if (scanTargetBox) scanTargetBox.style.borderColor = '#38bdf8';
        if (scanHoldStatus) scanHoldStatus.textContent = 'Arahkan & tahan kamera pada barcode...';
    }

    function setScanningUI(active) {
        isScanning = active;
        resetConsensusUI();
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

    async function decodeCenterFrame() {
        var vw = nativeVideo.videoWidth;
        var vh = nativeVideo.videoHeight;
        if (!vw || !vh) return null;

        var cropW = Math.round(vw * 0.8);
        var cropH = Math.round(vh * 0.48);
        var cropX = Math.round((vw - cropW) / 2);
        var cropY = Math.round((vh - cropH) / 2);
        var targetW = Math.min(840, cropW);
        var targetH = Math.min(500, cropH);

        scanCanvas.width = targetW;
        scanCanvas.height = targetH;
        scanCtx.drawImage(nativeVideo, cropX, cropY, cropW, cropH, 0, 0, targetW, targetH);
        var imgData = scanCtx.getImageData(0, 0, targetW, targetH);

        if (typeof zbarWasm !== 'undefined' && typeof zbarWasm.scanImageData === 'function') {
            try {
                var scanner = await getConfiguredZbarScanner();
                var symbols = scanner
                    ? await zbarWasm.scanImageData(imgData, scanner)
                    : await zbarWasm.scanImageData(imgData);
                if (symbols && symbols.length > 0) {
                    var val = symbols[0].decode();
                    if (val && isValidCompleteBarcode(val)) {
                        return val.trim();
                    }
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
            cameraOpenedAt = Date.now();
            setScanningUI(true);

            scanTimer = setInterval(async function () {
                if (!isScanning || isDecodingFrame || !nativeVideo || nativeVideo.readyState < 2) return;

                // Tunggu 800ms pertama agar autofokus lensa HP stabil terlebih dahulu
                if (Date.now() - cameraOpenedAt < 800) {
                    return;
                }

                isDecodingFrame = true;
                try {
                    var found = await decodeCenterFrame();
                    if (found) {
                        if (found === lastCandidateCode) {
                            candidateStreak++;
                        } else {
                            lastCandidateCode = found;
                            candidateStreak = 1;
                        }

                        if (scanTargetBox) scanTargetBox.style.borderColor = '#10b981';
                        if (scanHoldStatus) {
                            scanHoldStatus.textContent = 'Memverifikasi angka barcode: ' + found + ' (' + candidateStreak + '/' + REQUIRED_CONSENSUS + ')';
                        }

                        if (candidateStreak >= REQUIRED_CONSENSUS) {
                            applyScannedBarcode(found);
                        }
                    } else {
                        if (candidateStreak > 0) {
                            resetConsensusUI();
                        }
                    }
                } catch (e) {
                } finally {
                    isDecodingFrame = false;
                }
            }, 220);
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

    if (btnRescanBarcode) {
        btnRescanBarcode.addEventListener('click', function () {
            if (scanBanner) scanBanner.style.display = 'none';
            startScanner();
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
