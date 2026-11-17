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
        max-width: 960px;
        margin: 0 auto;
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
                        {{ $u->nama_ujian }} ({{ $u->kelas->nama_kelas ?? '-' }} - {{ $u->mapel->nama_mapel ?? '-' }})
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
        <input type="file" id="fileInput" name="file_scan" accept=".pdf,.jpg,.jpeg,.png" style="display:none;" onchange="handleFileSelected(this.files)">
        
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
        <p style="font-size:13px; color:#64748b; margin:0 auto 16px; max-width:480px;">
            Dukung 1 file <strong>PDF multi-halaman</strong> (satu tumpuk lembar jawab satu kelas) atau gambar <strong>JPG/PNG</strong>. Maksimal ukuran file 50 MB.
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
        if (files.length > 0) {
            handleFileSelected(files);
        }
    });

    function handleFileSelected(files) {
        if (!files || files.length === 0) return;
        const file = files[0];

        // Validasi tipe file
        const validExts = ['pdf', 'jpg', 'jpeg', 'png'];
        const ext = file.name.split('.').pop().toLowerCase();
        if (!validExts.includes(ext)) {
            alert('Format berkas tidak didukung. Harap pilih berkas PDF, JPG, atau PNG.');
            return;
        }

        // Tampilkan progress UI
        dropzone.style.display = 'none';
        progressCard.style.display = 'block';
        resultCard.style.display = 'none';

        progressTitle.textContent = 'Mengunggah ' + file.name + '...';
        progressSubtitle.textContent = 'Ukuran: ' + (file.size / (1024 * 1024)).toFixed(2) + ' MB';
        progressBarFill.style.width = '10%';
        progressPercent.textContent = '10%';
        progressStep.textContent = 'Mengirim berkas ke server...';

        const formData = new FormData();
        formData.append('file_scan', file);
        formData.append('_token', '{{ csrf_token() }}');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '{{ route('ujian.process-scan', $ujian->id) }}', true);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 60);
                progressBarFill.style.width = percentComplete + '%';
                progressPercent.textContent = percentComplete + '%';
                if (percentComplete >= 60) {
                    progressTitle.textContent = 'Memproses Koreksi OMR & QR Code...';
                    progressSubtitle.textContent = 'Sistem sedang mengekstrak halaman dan membaca bulatan jawaban secara berurutan.';
                    progressStep.textContent = 'Memeriksa sudut fiducial marker & grading jawaban...';
                }
            }
        };

        xhr.onload = function () {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const res = JSON.parse(xhr.responseText);
                    progressBarFill.style.width = '100%';
                    progressPercent.textContent = '100%';
                    setTimeout(() => {
                        progressCard.style.display = 'none';
                        showScanResults(res.data);
                    }, 400);
                } catch (err) {
                    showError('Gagal memproses respons server.');
                }
            } else {
                let errMsg = 'Gagal memproses berkas.';
                try {
                    const errRes = JSON.parse(xhr.responseText);
                    if (errRes.message) errMsg = errRes.message;
                } catch(e) {}
                showError(errMsg);
            }
        };

        xhr.onerror = function () {
            showError('Koneksi jaringan terputus atau server tidak merespons.');
        };

        xhr.send(formData);
    }

    function showScanResults(data) {
        resultCard.style.display = 'block';
        resultSummaryText.textContent = `Total ${data.total_halaman} halaman diproses. Berhasil: ${data.berhasil} lembar, Perlu Cek Manual: ${data.perlu_cek} lembar.`;

        let rowsHtml = '';
        if (data.rincian && data.rincian.length > 0) {
            data.rincian.forEach(r => {
                const statusBadge = r.status_scan === 'valid'
                    ? '<span style="background:#ecfdf5; color:#059669; font-weight:600; padding:3px 8px; border-radius:6px; font-size:11px;">Valid</span>'
                    : '<span style="background:#fffbeb; color:#d97706; font-weight:600; padding:3px 8px; border-radius:6px; font-size:11px;">Perlu Cek Manual</span>';

                rowsHtml += `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px 14px; font-weight:600; color:#64748b;">#${r.halaman}</td>
                        <td style="padding:10px 14px;">
                            <div style="font-weight:600; color:#1e293b;">${r.nama_siswa || 'Tidak Dikenali'}</div>
                            <div style="font-size:11px; color:#94a3b8;">${r.catatan_scan || '-'}</div>
                        </td>
                        <td style="padding:10px 14px; text-align:center; color:#059669; font-weight:600;">${r.jumlah_benar ?? 0}</td>
                        <td style="padding:10px 14px; text-align:center; color:#dc2626; font-weight:600;">${r.jumlah_salah ?? 0}</td>
                        <td style="padding:10px 14px; text-align:center; color:#64748b;">${r.jumlah_kosong ?? 0}</td>
                        <td style="padding:10px 14px; text-align:center; font-weight:800; color:#0052FF; font-size:14px;">${r.nilai_akhir ?? 0}</td>
                        <td style="padding:10px 14px;">${statusBadge}</td>
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
        alert('Kesalahan Scan LJK:\n' + message);
    }

    function resetUploadForm() {
        resultCard.style.display = 'none';
        dropzone.style.display = 'block';
        fileInput.value = '';
    }
</script>
@endif
@endsection

