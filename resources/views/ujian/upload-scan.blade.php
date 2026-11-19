@extends('layouts.app')

@section('title', 'Koreksi LJK Otomatis' . ($ujian ? ': ' . $ujian->nama_ujian : ''))

@section('header-actions')
@if($ujian)
<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    <a href="{{ route('ujian.hasil', $ujian->id) }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px;">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 20V10M12 20V4M6 20v-6"></path>
        </svg>
        <span>Lihat Rekap Hasil</span>
    </a>
    <a href="{{ route('ujian.cetak-ljk', $ujian->id) }}" target="_blank" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px;">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        <span>Cetak LJK</span>
    </a>
    <a href="{{ route('ujian.soal') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px;">
        &larr; Soal Ujian
    </a>
</div>
@endif
@endsection

@section('content')
<style>
    .upload-container {
        width: 100%;
    }
    .info-summary-card {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 12px;
        padding: 18px 22px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .dropzone-box {
        background: var(--bg-surface, #ffffff);
        border: 2px dashed #0052FF;
        border-radius: 16px;
        padding: 44px 24px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }
    .dropzone-box:hover, .dropzone-box.dragover {
        background: #f0f6ff;
        border-color: #003ecb;
        transform: translateY(-2px);
    }
    .dropzone-icon-circle {
        width: 68px;
        height: 68px;
        margin: 0 auto 16px;
        border-radius: 50%;
        background: #eff6ff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0052FF;
    }
    .guide-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 20px;
        margin-top: 24px;
    }
    .guide-step {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 10px;
        font-size: 13px;
        color: #475569;
    }
    .guide-step-num {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #0052FF;
        color: #fff;
        font-weight: 700;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .progress-card {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 14px;
        padding: 24px;
        margin-top: 20px;
        display: none;
    }
    .progress-bar-bg {
        width: 100%;
        height: 10px;
        background: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
        margin: 14px 0 10px;
    }
    .progress-bar-fill {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, #0052FF, #00C49F);
        border-radius: 999px;
        transition: width 0.3s ease;
    }
    .result-card {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 14px;
        padding: 24px;
        margin-top: 20px;
        display: none;
    }
</style>

<div class="upload-container">
    @if(session('success'))
        <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:14px; display:flex; align-items:center; gap:8px;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:14px;">
            {{ session('error') }}
        </div>
    @endif

    @if(isset($ujians) && $ujians->count() > 0)
    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:14px 18px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:10px;">
            <label for="selectUjian" style="font-size:13px; font-weight:700; color:#334155; margin:0;">Pilih Ujian:</label>
            <select id="selectUjian" class="form-control" style="font-size:13px; padding:6px 12px; border-radius:8px; border:1px solid #cbd5e1; min-width:280px;" onchange="window.location.href='{{ route('ujian.koreksi') }}?ujian_id=' + this.value">
                @foreach($ujians as $u)
                    <option value="{{ $u->id }}" {{ ($ujian && $ujian->id == $u->id) ? 'selected' : '' }}>
                        {{ $u->nama_ujian }} ({{ $u->kelas?->nama_kelas ?? '-' }} - {{ $u->mapel?->mata_pelajaran ?? '-' }})
                    </option>
                @endforeach
            </select>
        </div>
        <div style="font-size:12px; color:#64748b;">
            Total Ujian: <strong>{{ $ujians->count() }}</strong>
        </div>
    </div>
    @endif

    @if(!$ujian)
    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; padding:48px 24px; text-align:center; color:#64748b;">
        <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:16px;">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
        </svg>
        <h3 style="font-size:16px; font-weight:700; color:#1e293b; margin:0 0 8px;">Belum Ada Ujian</h3>
        <p style="font-size:13px; margin:0 auto 16px; max-width:400px;">Silakan buat ujian baru atau unggah soal terlebih dahulu sebelum melakukan koreksi scan LJK.</p>
        <a href="{{ route('ujian.soal') }}" class="btn btn-primary" style="font-size:13px; padding:8px 18px; border-radius:8px;">
            Ke Menu Soal Ujian
        </a>
    </div>
    @else
    <!-- Kartu Informasi Ujian -->
    <div class="info-summary-card">
        <div>
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                <h2 style="font-size:18px; font-weight:700; margin:0; color:#1e293b;">{{ $ujian->nama_ujian }}</h2>
                <span class="badge" style="background:#eff6ff; color:#0052FF; font-weight:600; padding:2px 8px; border-radius:6px; font-size:11px;">{{ $ujian->kode_ujian }}</span>
            </div>
            <div style="font-size:13px; color:#64748b; display:flex; gap:16px; flex-wrap:wrap;">
                <span><strong>Mapel:</strong> {{ $ujian->mapel?->mata_pelajaran ?? '-' }}</span>
                <span><strong>Kelas:</strong> {{ $ujian->kelas?->nama_kelas ?? '-' }}</span>
                <span><strong>Guru:</strong> {{ $ujian->guru?->name ?? '-' }}</span>
                <span><strong>Soal:</strong> {{ $ujian->jumlah_soal }} Butir ({{ $ujian->jumlah_opsi }} Opsi)</span>
            </div>
        </div>
        <div>
            <span style="font-size:12px; color:#64748b;">Sudah Dikoreksi:</span>
            <div style="font-size:20px; font-weight:800; color:#0052FF; text-align:right;">
                {{ $ujian->hasils_count ?? 0 }} <span style="font-size:13px; font-weight:500; color:#64748b;">Siswa</span>
            </div>
        </div>
    </div>

    <!-- Dropzone Area -->
    <div class="dropzone-box" id="dropzoneBox" onclick="document.getElementById('fileInput').click()">
        <input type="file" id="fileInput" name="file_scan" accept=".pdf,.jpg,.jpeg,.png,.webp" multiple style="display:none;" onchange="handleFileSelected(this.files)">
        
        <div class="dropzone-icon-circle">
            <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
        </div>

        <h3 style="font-size:16px; font-weight:700; color:#1e293b; margin:0 0 6px;">
            Pilih atau Tarik File Hasil Scan ke Sini
        </h3>
        <p style="font-size:13px; color:#64748b; margin:0 auto 16px; max-width:520px;">
            Dukung 1 file <strong>PDF multi-halaman</strong> (satu tumpuk lembar jawab satu kelas) atau beberapa gambar <strong>JPG/PNG</strong> sekaligus. Halaman akan dikonversi &amp; dikompresi otomatis per lembar sebelum dikirim.
        </p>

        <button type="button" class="btn btn-primary" style="padding:9px 22px; font-size:13px; border-radius:8px; pointer-events:none;">
            Pilih Berkas LJK
        </button>
    </div>

    <!-- Progress Card -->
    <div class="progress-card" id="progressCard">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <strong style="font-size:15px; color:#1e293b;" id="progressTitle">Mengunggah file scan...</strong>
                <div style="font-size:13px; color:#64748b;" id="progressSubtitle">Mohon tunggu, berkas sedang dikirim ke server.</div>
            </div>
            <span style="font-size:16px; font-weight:700; color:#0052FF;" id="progressPercent">0%</span>
        </div>

        <div class="progress-bar-bg">
            <div class="progress-bar-fill" id="progressBarFill"></div>
        </div>

        <div style="display:flex; align-items:center; gap:8px; font-size:12px; color:#64748b; margin-top:8px;">
            <svg class="spinner-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;">
                <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                <path d="M12 2a10 10 0 0 1 10 10" stroke="#0052FF"></path>
            </svg>
            <span id="progressStep">Sedang memproses halaman secara berurutan...</span>
        </div>
    </div>

    <!-- Results Card -->
    <div class="result-card" id="resultCard">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:38px; height:38px; border-radius:50%; background:#ecfdf5; color:#059669; display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <div>
                    <h3 style="font-size:16px; font-weight:700; margin:0; color:#1e293b;">Koreksi Selesai!</h3>
                    <p style="font-size:13px; color:#64748b; margin:2px 0 0;" id="resultSummaryText">Hasil pemindaian berhasil diproses.</p>
                </div>
            </div>

            <div style="display:flex; gap:8px;">
                <button type="button" class="btn btn-secondary" onclick="resetUploadForm()" style="font-size:13px; padding:7px 14px; border-radius:8px;">
                    Unggah Lagi
                </button>
                <a href="{{ route('ujian.hasil', $ujian->id) }}" class="btn btn-primary" style="font-size:13px; padding:7px 16px; border-radius:8px; display:inline-flex; align-items:center; gap:6px;">
                    <span>Buka Rekapitulasi Nilai &rarr;</span>
                </a>
            </div>
        </div>

        <div style="overflow-x:auto; border:1px solid #e2e8f0; border-radius:10px;">
            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0; text-align:left; color:#475569;">
                        <th style="padding:10px 14px;">Hal</th>
                        <th style="padding:10px 14px;">Nama Siswa</th>
                        <th style="padding:10px 14px; text-align:center;">Benar</th>
                        <th style="padding:10px 14px; text-align:center;">Salah</th>
                        <th style="padding:10px 14px; text-align:center;">Kosong</th>
                        <th style="padding:10px 14px; text-align:center;">Nilai</th>
                        <th style="padding:10px 14px;">Status Scan</th>
                    </tr>
                </thead>
                <tbody id="resultTableBody">
                    <!-- Populated via JS -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Panduan Pemindaian LJK -->
    <div class="guide-box">
        <h4 style="font-size:14px; font-weight:700; color:#1e293b; margin:0 0 12px; display:flex; align-items:center; gap:8px;">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            Petunjuk Pemindaian Scanner LJK Agar Akurasi Maksimal:
        </h4>
        <div class="guide-step">
            <span class="guide-step-num">1</span>
            <div><strong>Pastikan 4 Kotak Sudut (Fiducial Markers) Terlihat Jelas:</strong> 4 kotak hitam di pojok lembar A4 digunakan untuk kalibrasi kemiringan dan sudut kertas secara otomatis. Jangan sampai terpotong saat scan.</div>
        </div>
        <div class="guide-step">
            <span class="guide-step-num">2</span>
            <div><strong>Resolusi Scanner Disarankan 150 – 300 DPI:</strong> Menggunakan mode Grayscale atau Warna standar dokumen. File PDF multi-halaman dari Automatic Document Feeder (ADF) sangat disarankan.</div>
        </div>
        <div class="guide-step">
            <span class="guide-step-num">3</span>
            <div><strong>Pensil / Pulpen Hitam Pekat:</strong> Siswa disarankan mengisi bulatan penuh menggunakan pensil 2B atau pulpen bertinta gelap.</div>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<!-- PDF.js, jsQR & ZBar WebAssembly (Self-hosted dengan fallback CDN) -->
<script src="{{ asset('js/vendor/pdfjs/pdf.min.js') }}"
        onerror="this.onerror=null;this.src='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js'"></script>
<script src="{{ asset('js/vendor/jsqr/jsqr.js') }}"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js'"></script>
<script src="{{ asset('js/vendor/zbar/zbar-wasm.js') }}"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/@undecaf/zbar-wasm@0.9.15/dist/index.js'"></script>
<script src="{{ asset('js/vendor/zbar/barcode-detector-polyfill.js') }}"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/@undecaf/barcode-detector-polyfill@0.9.21/dist/index.js'"></script>

<script>
    const dropzone = document.getElementById('dropzoneBox');
    const fileInput = document.getElementById('fileInput');
    const progressCard = document.getElementById('progressCard');
    const progressBarFill = document.getElementById('progressBarFill');
    const progressPercent = document.getElementById('progressPercent');
    const progressTitle = document.getElementById('progressTitle');
    const progressSubtitle = document.getElementById('progressSubtitle');
    const progressStep = document.getElementById('progressStep');
    const resultCard = document.getElementById('resultCard');
    const resultTableBody = document.getElementById('resultTableBody');
    const resultSummaryText = document.getElementById('resultSummaryText');

    const PROCESS_SCAN_URL = '{{ route('ujian.process-scan', $ujian->id) }}';
    const ASSIGN_SISWA_URL = '{{ route('ujian.assign-hasil-siswa', $ujian->id) }}';
    const SISWAS_KELAS = @json($siswasKelas ?? []);
    const CSRF_TOKEN = '{{ csrf_token() }}';
    const PDF_WORKER_URL = '{{ asset('js/vendor/pdfjs/pdf.worker.min.js') }}';

    if (typeof window.BarcodeDetector === 'undefined' && typeof barcodeDetectorPolyfill !== 'undefined') {
        window.BarcodeDetector = barcodeDetectorPolyfill.BarcodeDetectorPolyfill;
    }

    let qrDetectorInstance = null;

    function configurePdfJsWorker() {
        if (window.pdfjsLib && window.pdfjsLib.GlobalWorkerOptions) {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = PDF_WORKER_URL;
        }
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        });
    });

    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
            handleFileSelected(files);
        }
    });

    function updateProgress(pct, title, subtitle, step) {
        const safePct = Math.max(0, Math.min(100, Math.round(pct)));
        progressBarFill.style.width = safePct + '%';
        progressPercent.textContent = safePct + '%';
        if (title !== undefined) progressTitle.textContent = title;
        if (subtitle !== undefined) progressSubtitle.textContent = subtitle;
        if (step !== undefined) progressStep.textContent = step;
    }

    /**
     * Ekspor canvas menjadi JPEG Blob dengan batas aman < 1.4 MB agar tidak pernah ditolak batas upload PHP server
     */
    function canvasToSafeJpegBlob(canvas) {
        return new Promise((resolve, reject) => {
            const qualities = [0.85, 0.72, 0.58];
            let idx = 0;

            function tryNext() {
                canvas.toBlob((blob) => {
                    if (!blob) {
                        reject(new Error('Gagal mengonversi halaman ke gambar JPEG.'));
                        return;
                    }
                    if (blob.size <= 1400 * 1024 || idx >= qualities.length - 1) {
                        resolve(blob);
                    } else {
                        idx++;
                        tryNext();
                    }
                }, 'image/jpeg', qualities[idx]);
            }

            tryNext();
        });
    }

    /**
     * Dekode QR Code identitas siswa langsung di browser menggunakan jsQR / ZBar / BarcodeDetector
     */
    async function decodeQrFromCanvas(sourceCanvas) {
        const w = sourceCanvas.width;
        const h = sourceCanvas.height;
        if (!w || !h) return null;

        if (!qrDetectorInstance && typeof window.BarcodeDetector !== 'undefined') {
            try {
                qrDetectorInstance = new window.BarcodeDetector({ formats: ['qr_code', 'code_128', 'code_39'] });
            } catch (e) {
                try { qrDetectorInstance = new window.BarcodeDetector(); } catch (e2) {}
            }
        }

        // Daftar area pencarian QR Code (prioritas pojok kanan atas LJK: x: 55%-95%, y: 2%-30%)
        const regions = [
            { x: Math.floor(w * 0.55), y: Math.floor(h * 0.02), w: Math.ceil(w * 0.40), h: Math.ceil(h * 0.28) },
            { x: Math.floor(w * 0.50), y: 0, w: Math.ceil(w * 0.50), h: Math.ceil(h * 0.35) },
            { x: 0, y: 0, w: w, h: Math.ceil(h * 0.45) },
            { x: 0, y: 0, w: w, h: h },
        ];

        const cropCanvas = document.createElement('canvas');
        const cropCtx = cropCanvas.getContext('2d', { willReadFrequently: true });

        for (const reg of regions) {
            if (reg.w < 40 || reg.h < 40) continue;

            // Skala area crop agar optimal untuk pembacaan QR
            const maxDim = 900;
            const scale = Math.min(1, maxDim / Math.max(reg.w, reg.h));
            const tw = Math.max(40, Math.round(reg.w * scale));
            const th = Math.max(40, Math.round(reg.h * scale));

            cropCanvas.width = tw;
            cropCanvas.height = th;
            cropCtx.drawImage(sourceCanvas, reg.x, reg.y, reg.w, reg.h, 0, 0, tw, th);
            const imgData = cropCtx.getImageData(0, 0, tw, th);

            // 1. Coba jsQR (Pure JS, paling andal dan sinkron)
            if (typeof window.jsQR === 'function') {
                try {
                    const qrResult = window.jsQR(imgData.data, tw, th, { inversionAttempts: 'attemptBoth' });
                    if (qrResult && qrResult.data && qrResult.data.trim().length > 0) {
                        return qrResult.data.trim();
                    }
                } catch (e) {}
            }

            // 2. Coba ZBar WebAssembly
            if (typeof zbarWasm !== 'undefined' && typeof zbarWasm.scanImageData === 'function') {
                try {
                    const symbols = await zbarWasm.scanImageData(imgData);
                    if (symbols && symbols.length > 0) {
                        for (const sym of symbols) {
                            const val = sym.decode ? sym.decode() : '';
                            if (val && val.trim().length > 0) {
                                return val.trim();
                            }
                        }
                    }
                } catch (e) {}
            }

            // 3. Coba Native / Polyfill BarcodeDetector
            if (qrDetectorInstance) {
                try {
                    const codes = await qrDetectorInstance.detect(cropCanvas);
                    if (codes && codes.length > 0 && codes[0].rawValue) {
                        return String(codes[0].rawValue).trim();
                    }
                } catch (e) {}
            }
        }

        return null;
    }

    /**
     * Render file gambar (JPG/PNG/WebP) ke canvas dengan ukuran optimal (~1450px max lebar)
     */
    function loadImageFileToCanvas(file) {
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => {
                URL.revokeObjectURL(url);
                const maxW = 1450;
                const scale = img.naturalWidth > maxW ? (maxW / img.naturalWidth) : 1;
                const canvas = document.createElement('canvas');
                canvas.width = Math.round(img.naturalWidth * scale);
                canvas.height = Math.round(img.naturalHeight * scale);
                const ctx = canvas.getContext('2d', { willReadFrequently: true });
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                resolve(canvas);
            };
            img.onerror = () => {
                URL.revokeObjectURL(url);
                reject(new Error('Gagal membaca berkas gambar ' + file.name));
            };
            img.src = url;
        });
    }

    /**
     * Kirim satu halaman JPEG terkompresi (+ QR payload jika terbaca) ke server untuk dikoreksi OMR
     */
    async function uploadSinglePageToServer(blob, filename, pageNumber, qrPayload) {
        const formData = new FormData();
        formData.append('file_scan', blob, filename);
        formData.append('page_number', String(pageNumber));
        if (qrPayload) {
            formData.append('qr_payload', qrPayload);
        }
        formData.append('_token', CSRF_TOKEN);

        const response = await fetch(PROCESS_SCAN_URL, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        });

        let payload = null;
        try {
            payload = await response.json();
        } catch (e) {
            throw new Error(`Respons server tidak valid (HTTP ${response.status}).`);
        }

        if (!response.ok || !payload || !payload.success) {
            let msg = (payload && payload.message) ? payload.message : `Gagal memproses halaman #${pageNumber} (HTTP ${response.status}).`;
            if (payload && payload.errors) {
                const firstErr = Object.values(payload.errors).flat()[0];
                if (firstErr) msg = firstErr;
            }
            throw new Error(msg);
        }

        return payload.data;
    }

    /**
     * Render dan proses seluruh halaman berkas PDF di browser lalu kirim satu per satu ke server
     */
    async function processPdfClientSide(file, pageOffset, aggregated) {
        configurePdfJsWorker();
        if (!window.pdfjsLib) {
            throw new Error('Pustaka pembaca PDF di browser belum termuat. Silakan periksa koneksi internet lalu muat ulang halaman.');
        }

        updateProgress(5, 'Membuka dokumen PDF ' + file.name + '...', 'Menghitung jumlah halaman lembar jawab...', 'Memuat PDF secara lokal di browser...');
        const arrayBuffer = await file.arrayBuffer();
        const pdfDoc = await window.pdfjsLib.getDocument({ data: arrayBuffer }).promise;
        const numPages = pdfDoc.numPages;

        const renderCanvas = document.createElement('canvas');
        const renderCtx = renderCanvas.getContext('2d', { willReadFrequently: true });

        for (let p = 1; p <= numPages; p++) {
            const globalPage = pageOffset + p;
            const basePct = ((p - 1) / numPages) * 100;

            updateProgress(
                Math.max(5, basePct + (25 / numPages)),
                `Memproses Halaman ${p} dari ${numPages}...`,
                `Berkas: ${file.name} (${(file.size / (1024 * 1024)).toFixed(2)} MB)`,
                `Merender gambar LJK & membaca QR Code siswa halaman #${p}...`
            );

            const page = await pdfDoc.getPage(p);
            const unscaledViewport = page.getViewport({ scale: 1.0 });
            // Target lebar ~1400px (setara ~170 DPI A4) — sangat tajam untuk OMR & QR, ukuran file ringan
            const targetWidth = 1400;
            const scale = Math.min(2.5, Math.max(1.2, targetWidth / unscaledViewport.width));
            const viewport = page.getViewport({ scale });

            renderCanvas.width = Math.round(viewport.width);
            renderCanvas.height = Math.round(viewport.height);
            renderCtx.fillStyle = '#ffffff';
            renderCtx.fillRect(0, 0, renderCanvas.width, renderCanvas.height);

            await page.render({ canvasContext: renderCtx, viewport }).promise;

            // Baca QR Code langsung di browser
            const qrPayload = await decodeQrFromCanvas(renderCanvas);

            // Kompres ke JPEG ringan (< 1.4 MB)
            const jpegBlob = await canvasToSafeJpegBlob(renderCanvas);

            updateProgress(
                basePct + (65 / numPages),
                `Mengoreksi OMR Halaman ${p} dari ${numPages}...`,
                `Ukuran terkompresi: ${(jpegBlob.size / 1024).toFixed(0)} KB` + (qrPayload ? ' • QR Siswa Terbaca' : ''),
                `Memeriksa fiducial marker & menilai bulatan jawaban halaman #${p}...`
            );

            const serverData = await uploadSinglePageToServer(
                jpegBlob,
                `ljk_page_${globalPage}.jpg`,
                globalPage,
                qrPayload
            );

            mergePageResult(aggregated, serverData);
            updateProgress((p / numPages) * 100, `Halaman ${p} dari ${numPages} selesai`, undefined, `Selesai memproses halaman #${p}.`);

            page.cleanup();
        }

        await pdfDoc.destroy();
        return numPages;
    }

    function mergePageResult(aggregated, serverData) {
        if (!serverData) return;
        aggregated.total_halaman += (serverData.total_halaman || 0);
        aggregated.berhasil += (serverData.berhasil || 0);
        aggregated.perlu_cek += (serverData.perlu_cek || 0);
        if (Array.isArray(serverData.rincian)) {
            aggregated.rincian.push(...serverData.rincian);
        }
    }

    async function handleFileSelected(fileList) {
        if (!fileList || fileList.length === 0) return;

        const files = Array.from(fileList);
        const validExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

        for (const f of files) {
            const ext = (f.name.split('.').pop() || '').toLowerCase();
            if (!validExts.includes(ext)) {
                alert('Format berkas "' + f.name + '" tidak didukung. Harap pilih berkas PDF, JPG, atau PNG.');
                return;
            }
        }

        // Tampilkan progress UI
        dropzone.style.display = 'none';
        progressCard.style.display = 'block';
        resultCard.style.display = 'none';
        updateProgress(5, 'Menyiapkan berkas scan...', `${files.length} berkas dipilih`, 'Memulai ekstraksi halaman...');

        const aggregated = {
            total_halaman: 0,
            berhasil: 0,
            perlu_cek: 0,
            rincian: [],
        };

        try {
            let pageCounter = 0;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const ext = (file.name.split('.').pop() || '').toLowerCase();

                if (ext === 'pdf') {
                    const count = await processPdfClientSide(file, pageCounter, aggregated);
                    pageCounter += count;
                } else {
                    pageCounter++;
                    const basePct = (i / files.length) * 100;
                    updateProgress(
                        Math.max(10, basePct + (25 / files.length)),
                        `Memproses gambar ${i + 1} dari ${files.length}...`,
                        `${file.name} (${(file.size / (1024 * 1024)).toFixed(2)} MB)`,
                        'Mengoptimalkan resolusi gambar & membaca QR Code siswa...'
                    );

                    const canvas = await loadImageFileToCanvas(file);
                    const qrPayload = await decodeQrFromCanvas(canvas);
                    const jpegBlob = await canvasToSafeJpegBlob(canvas);

                    updateProgress(
                        basePct + (65 / files.length),
                        `Mengoreksi OMR gambar ${i + 1} dari ${files.length}...`,
                        `Ukuran terkompresi: ${(jpegBlob.size / 1024).toFixed(0)} KB` + (qrPayload ? ' • QR Siswa Terbaca' : ''),
                        'Memeriksa fiducial marker & menilai bulatan jawaban...'
                    );

                    const serverData = await uploadSinglePageToServer(
                        jpegBlob,
                        `ljk_img_${pageCounter}.jpg`,
                        pageCounter,
                        qrPayload
                    );
                    mergePageResult(aggregated, serverData);
                }
            }

            updateProgress(100, 'Koreksi LJK Selesai!', `Total ${aggregated.total_halaman} halaman berhasil diproses.`, 'Menampilkan rekap hasil...');
            setTimeout(() => {
                progressCard.style.display = 'none';
                showScanResults(aggregated);
            }, 350);
        } catch (err) {
            showError(err && err.message ? err.message : 'Terjadi kesalahan saat memproses berkas LJK.');
        }
    }

    function buildSiswaOptionsHtml(selectedId) {
        let opts = '';
        if (Array.isArray(SISWAS_KELAS)) {
            SISWAS_KELAS.forEach(s => {
                const isSel = (selectedId && Number(selectedId) === Number(s.id)) ? 'selected' : '';
                opts += `<option value="${s.id}" ${isSel}>${s.nama_lengkap} (ID: ${s.id})</option>`;
            });
        }
        return opts;
    }

    function toggleSiswaSelect(halaman) {
        const wrap = document.getElementById(`siswa_select_wrap_${halaman}`);
        if (wrap) {
            wrap.style.display = (wrap.style.display === 'none' || wrap.style.display === '') ? 'block' : 'none';
        }
    }

    async function assignSiswaFromSelect(halaman, hasilId, siswaId, jawabanObj) {
        if (!siswaId) return;

        try {
            const res = await fetch(ASSIGN_SISWA_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                },
                body: JSON.stringify({
                    hasil_id: hasilId || null,
                    siswa_id: Number(siswaId),
                    jawaban: jawabanObj || {},
                }),
            });

            const payload = await res.json();
            if (!res.ok || !payload.success) {
                alert(payload.message || 'Gagal menghubungkan siswa.');
                return;
            }

            const nameEl = document.getElementById(`siswa_nama_${halaman}`);
            if (nameEl) nameEl.textContent = payload.data.nama_siswa;

            const badgeEl = document.getElementById(`status_badge_${halaman}`);
            if (badgeEl) {
                badgeEl.innerHTML = '<span style="background:#ecfdf5; color:#059669; font-weight:600; padding:3px 8px; border-radius:6px; font-size:11px;">Valid (Tersimpan)</span>';
            }

            const noticeEl = document.getElementById(`unassigned_notice_${halaman}`);
            if (noticeEl) noticeEl.style.display = 'none';

            const dispEl = document.getElementById(`siswa_display_${halaman}`);
            if (dispEl) dispEl.style.display = 'flex';

            const wrapEl = document.getElementById(`siswa_select_wrap_${halaman}`);
            if (wrapEl) wrapEl.style.display = 'none';

            const noteEl = document.getElementById(`catatan_scan_${halaman}`);
            if (noteEl) noteEl.textContent = 'Diverifikasi manual oleh Guru (' + payload.data.nama_siswa + ')';

        } catch (e) {
            alert('Terjadi kesalahan: ' + e.message);
        }
    }

    function showScanResults(data) {
        resultCard.style.display = 'block';
        resultSummaryText.textContent = `Total ${data.total_halaman} halaman diproses. Berhasil: ${data.berhasil} lembar, Perlu Cek Manual: ${data.perlu_cek} lembar.`;

        let rowsHtml = '';
        if (data.rincian && data.rincian.length > 0) {
            data.rincian.forEach(r => {
                const isIdentified = !!r.siswa_id;
                const statusBadge = (r.status_scan === 'valid' && isIdentified)
                    ? '<span style="background:#ecfdf5; color:#059669; font-weight:600; padding:3px 8px; border-radius:6px; font-size:11px;">Valid</span>'
                    : '<span style="background:#fffbeb; color:#d97706; font-weight:600; padding:3px 8px; border-radius:6px; font-size:11px;">Perlu Cek Manual</span>';

                const jawabanEscaped = JSON.stringify(r.jawaban || {}).replace(/"/g, '&quot;');

                rowsHtml += `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px 14px; font-weight:600; color:#64748b;">#${r.halaman}</td>
                        <td style="padding:10px 14px;">
                            <div id="unassigned_notice_${r.halaman}" style="${isIdentified ? 'display:none;' : 'display:block; margin-bottom:4px; color:#d97706; font-weight:700;'}">
                                ⚠️ ${r.nama_siswa || 'Siswa Belum Dipilih'}
                            </div>
                            <div id="siswa_display_${r.halaman}" style="${isIdentified ? 'display:flex;' : 'display:none;'} align-items:center; gap:8px;">
                                <div style="font-weight:600; color:#1e293b;" id="siswa_nama_${r.halaman}">${r.nama_siswa || 'Tidak Dikenali'}</div>
                                <button type="button" onclick="toggleSiswaSelect(${r.halaman})" style="border:none; background:#eff6ff; color:#0052FF; font-size:11px; padding:2px 8px; border-radius:4px; cursor:pointer;" title="Ubah Siswa">Ubah</button>
                            </div>
                            <div id="siswa_select_wrap_${r.halaman}" style="${isIdentified ? 'display:none;' : 'display:block;'} margin-top:4px;">
                                <select class="form-control" style="font-size:12px; padding:4px 8px; border-radius:6px; border:1px solid #3b82f6; max-width:280px; background:#f0f6ff;" onchange="assignSiswaFromSelect(${r.halaman}, ${r.hasil_id || 0}, this.value, ${jawabanEscaped})">
                                    <option value="">-- Pilih Nama Siswa Manual --</option>
                                    ${buildSiswaOptionsHtml(r.siswa_id)}
                                </select>
                            </div>
                            <div style="font-size:11px; color:#94a3b8; margin-top:2px;" id="catatan_scan_${r.halaman}">${r.catatan_scan || '-'}</div>
                        </td>
                        <td style="padding:10px 14px; text-align:center; color:#059669; font-weight:600;">${r.jumlah_benar ?? 0}</td>
                        <td style="padding:10px 14px; text-align:center; color:#dc2626; font-weight:600;">${r.jumlah_salah ?? 0}</td>
                        <td style="padding:10px 14px; text-align:center; color:#64748b;">${r.jumlah_kosong ?? 0}</td>
                        <td style="padding:10px 14px; text-align:center; font-weight:800; color:#0052FF; font-size:14px;">${r.nilai_akhir ?? 0}</td>
                        <td style="padding:10px 14px;" id="status_badge_${r.halaman}">${statusBadge}</td>
                    </tr>
                `;
            });
        } else {
            rowsHtml = '<tr><td colspan="7" style="text-align:center; padding:20px; color:#94a3b8;">Tidak ada data hasil scan yang terdeteksi.</td></tr>';
        }

        resultTableBody.innerHTML = rowsHtml;
    }

    function showError(message) {
        progressCard.style.display = 'none';
        dropzone.style.display = 'block';
        fileInput.value = '';
        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan Scan LJK',
                text: message,
                confirmButtonColor: '#0052FF',
            });
        } else {
            alert('Kesalahan Scan LJK:\n' + message);
        }
    }

    function resetUploadForm() {
        resultCard.style.display = 'none';
        dropzone.style.display = 'block';
        fileInput.value = '';
    }
</script>
@endif
@endsection

