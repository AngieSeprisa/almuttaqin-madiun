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

    {{-- LANGKAH 1: SCAN BARCODE BUKU DENGAN KAMERA HP --}}
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
                    <span style="font-size:12px; color:var(--text-muted);">Arahkan kamera HP ke barcode pada sampul buku</span>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                <input type="file" id="barcodeFileInput" accept="image/*" capture="environment" style="display:none;">
                <button type="button" id="btnUploadBarcode" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; font-size:13px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    <span>Foto Barcode</span>
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

        <div id="scannerWrapper" style="position:relative; width:100%; max-width:460px; margin:0 auto; border-radius:14px; overflow:hidden; background:#0B0F17; min-height:220px; display:flex; flex-direction:column; align-items:center; justify-content:center; border:2px dashed var(--border-light);">
            <div id="barcodeReader" style="width:100%;"></div>

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
                <div style="font-size:14px; font-weight:600; color:#F1F5F9; margin-bottom:4px;">Pemindai Barcode Buku Siap</div>
                <div style="font-size:12px; color:#94A3B8; max-width:300px; margin:0 auto;">Tekan <strong>"Buka Kamera HP"</strong> untuk scan barcode buku secara langsung, atau ketik manual di bawah.</div>
            </div>
        </div>

        <div id="cameraControls" style="display:none; margin-top:12px; text-align:center;">
            <button type="button" id="btnSwitchCamera" class="btn btn-secondary" style="font-size:12.5px; padding:6px 14px; display:inline-flex; align-items:center; gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                </svg>
                <span>Ganti Kamera</span>
            </button>
        </div>

        <div id="scanSuccessBanner" style="display:none; margin-top:14px; padding:10px 14px; border-radius:10px; background:#ecfdf5; border:1px solid #a7f3d0; color:#047857; font-size:13px; font-weight:600; align-items:center; gap:8px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:18px;height:18px;flex-shrink:0;">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <span id="scanSuccessText">Barcode berhasil dipindai!</span>
        </div>
    </div>

    {{-- LANGKAH 2 & 3: DETAIL BUKU & KODE BUKU OTOMATIS --}}
    <div class="form-section-card" style="padding:24px 26px;">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:18px; padding-bottom:12px; border-bottom:1px solid var(--border-subtle);">
            <div style="width:36px; height:36px; border-radius:10px; background:rgba(37,99,235,0.1); color:#2563eb; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <div>
                <h2 style="font-size:16px; font-weight:700; color:var(--text-primary); margin:0;">2. Informasi Buku & Kode Otomatis</h2>
                <span style="font-size:12px; color:var(--text-muted);">Masukkan nama buku dan simpan, kode buku dibuat secara otomatis</span>
            </div>
        </div>

        <form action="{{ route('perpustakaan.buku.store') }}" method="POST" id="formTambahBuku" style="display:flex; flex-direction:column; gap:16px;">
            @csrf

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px;">
                {{-- Barcode Buku (Hasil Scan / Ketik Manual) --}}
                <div class="form-group" style="margin:0;">
                    <label for="input_barcode" class="form-label">Barcode Buku</label>
                    <input type="text"
                           name="barcode"
                           id="input_barcode"
                           value="{{ old('barcode') }}"
                           class="form-control"
                           placeholder="Otomatis terisi saat scan barcode..."
                           style="font-family:monospace; font-weight:600;">
                </div>

                {{-- Kode Buku Otomatis --}}
                <div class="form-group" style="margin:0;">
                    <label for="kode_buku" class="form-label">Kode Buku (Otomatis)</label>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <input type="text"
                               name="kode_buku"
                               id="kode_buku"
                               value="{{ old('kode_buku', $nextKodeBuku) }}"
                               class="form-control"
                               readonly
                               style="font-family:monospace; font-weight:700; background:var(--nav-hover-bg); color:#1d4ed8;">
                        <button type="button"
                                id="btnGenerateKode"
                                class="btn btn-secondary"
                                style="white-space:nowrap; padding:9px 12px; font-size:12.5px;"
                                title="Buat Ulang Kode Buku Otomatis">
                            Buat Otomatis
                        </button>
                    </div>
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
                       required
                       autofocus>
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

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var html5QrCode = null;
    var isScanning = false;
    var camerasList = [];
    var currentCameraIndex = 0;

    var btnToggleCamera = document.getElementById('btnToggleCamera');
    var camBtnText = document.getElementById('camBtnText');
    var placeholder = document.getElementById('scannerPlaceholder');
    var cameraControls = document.getElementById('cameraControls');
    var btnSwitchCamera = document.getElementById('btnSwitchCamera');
    var btnUploadBarcode = document.getElementById('btnUploadBarcode');
    var barcodeFileInput = document.getElementById('barcodeFileInput');

    var inputBarcode = document.getElementById('input_barcode');
    var inputJudul = document.getElementById('judul_buku');
    var inputKode = document.getElementById('kode_buku');
    var btnGenerateKode = document.getElementById('btnGenerateKode');
    var scanBanner = document.getElementById('scanSuccessBanner');
    var scanText = document.getElementById('scanSuccessText');

    function fetchKodeBukuOtomatis() {
        fetch('{{ route("perpustakaan.buku.generate-kode") }}', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data && data.kode_buku && inputKode) {
                inputKode.value = data.kode_buku;
            }
        })
        .catch(function () {});
    }

    if (btnGenerateKode) {
        btnGenerateKode.addEventListener('click', function () {
            fetchKodeBukuOtomatis();
        });
    }

    function applyScannedBarcode(code) {
        if (!code) return;
        var cleanCode = String(code).trim();
        if (inputBarcode) {
            inputBarcode.value = cleanCode;
        }
        if (inputKode && !inputKode.value.trim()) {
            fetchKodeBukuOtomatis();
        }
        if (scanBanner && scanText) {
            scanText.textContent = 'Barcode berhasil dipindai: ' + cleanCode;
            scanBanner.style.display = 'flex';
        }
        if (navigator.vibrate) {
            navigator.vibrate([80, 40, 80]);
        }
        stopScanner();
        if (inputJudul) {
            inputJudul.focus();
        }
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

    if (btnUploadBarcode && barcodeFileInput) {
        btnUploadBarcode.addEventListener('click', function () {
            barcodeFileInput.click();
        });

        barcodeFileInput.addEventListener('change', function (e) {
            if (!e.target.files || e.target.files.length === 0) return;
            var file = e.target.files[0];

            if (!html5QrCode && typeof Html5Qrcode !== 'undefined') {
                html5QrCode = new Html5Qrcode('barcodeReader');
            }

            if (html5QrCode) {
                html5QrCode.scanFile(file, false)
                    .then(function (decodedText) {
                        applyScannedBarcode(decodedText);
                    })
                    .catch(function () {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Barcode Tidak Terbaca',
                            text: 'Pastikan foto barcode buku terlihat jelas dan terang, atau ketik angka barcode secara manual.'
                        });
                    })
                    .finally(function () {
                        barcodeFileInput.value = '';
                    });
            }
        });
    }

    function startScanner() {
        if (typeof Html5Qrcode === 'undefined') {
            if (barcodeFileInput) barcodeFileInput.click();
            return;
        }

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode('barcodeReader');
        }

        Html5Qrcode.getCameras().then(function (devices) {
            if (!devices || !devices.length) {
                if (barcodeFileInput) barcodeFileInput.click();
                return;
            }
            camerasList = devices;
            var backIdx = devices.findIndex(function (d) {
                var lbl = (d.label || '').toLowerCase();
                return lbl.includes('back') || lbl.includes('environment') || lbl.includes('belakang');
            });
            currentCameraIndex = backIdx !== -1 ? backIdx : 0;

            if (placeholder) placeholder.style.display = 'none';
            if (camerasList.length > 1 && cameraControls) cameraControls.style.display = 'block';

            html5QrCode.start(
                camerasList[currentCameraIndex].id,
                {
                    fps: 10,
                    qrbox: { width: 260, height: 150 }
                },
                function (decodedText) {
                    applyScannedBarcode(decodedText);
                },
                function () {}
            ).then(function () {
                isScanning = true;
                if (camBtnText) camBtnText.textContent = 'Tutup Kamera';
                if (btnToggleCamera) btnToggleCamera.className = 'btn btn-secondary';
            }).catch(function () {
                if (barcodeFileInput) barcodeFileInput.click();
            });
        }).catch(function () {
            if (barcodeFileInput) barcodeFileInput.click();
        });
    }

    function stopScanner() {
        if (html5QrCode && isScanning) {
            html5QrCode.stop().then(function () {
                isScanning = false;
                if (camBtnText) camBtnText.textContent = 'Buka Kamera HP';
                if (btnToggleCamera) btnToggleCamera.className = 'btn btn-primary';
                if (placeholder) placeholder.style.display = 'block';
                if (cameraControls) cameraControls.style.display = 'none';
            }).catch(function () {});
        }
    }

    if (btnSwitchCamera) {
        btnSwitchCamera.addEventListener('click', function () {
            if (camerasList.length > 1 && isScanning) {
                currentCameraIndex = (currentCameraIndex + 1) % camerasList.length;
                html5QrCode.stop().then(function () {
                    isScanning = false;
                    startScanner();
                });
            }
        });
    }
});
</script>
@endsection

