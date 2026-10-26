@extends('layouts.app')

@section('title', 'Import Data Siswa')

@section('header-actions')
<a href="{{ route('siswa') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    <span>Kembali ke Daftar Siswa</span>
</a>
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:20px; width:100%;">

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

    @if($errors->any())
    <div class="alert-banner alert-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
            <strong>Gagal mengimpor file:</strong>
            <ul style="margin:4px 0 0 18px;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <div class="import-grid">
        {{-- SISI KIRI: Form Upload & Download Template --}}
        <div style="display:flex; flex-direction:column; gap:20px;">

            {{-- Card 1: Unduh Template --}}
            <div class="form-section-card">
                <div class="form-section-header" style="margin-bottom:14px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div class="import-step-badge">1</div>
                        <div>
                            <h2 style="font-size:16px; font-weight:700; margin:0; color:var(--text-primary);">Unduh Template Excel</h2>
                            <p style="font-size:12.5px; color:var(--text-muted); margin:2px 0 0 0;">
                                Gunakan format template resmi yang telah disesuaikan dengan struktur tabel data siswa Al Muttaqin.
                            </p>
                        </div>
                    </div>
                </div>

                <div style="background:var(--bg-subtle, #F8FAFC); border:1px solid var(--border-light, #E2E8F0); border-radius:10px; padding:14px 16px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div class="csv-icon-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px; color:#10B981;">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="8" y1="13" x2="16" y2="13"></line>
                                <line x1="8" y1="17" x2="16" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </div>
                        <div>
                            <div style="font-weight:700; font-size:13.5px; color:var(--text-primary);">template_import_siswa_almuttaqin.xlsx</div>
                            <div style="font-size:12px; color:var(--text-muted);">Mendukung Microsoft Excel, Google Sheets, dan LibreOffice</div>
                        </div>
                    </div>

                    <a href="{{ route('master.import-siswa.template') }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none; padding:8px 18px; border-radius:8px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span>Unduh Template (.XLSX)</span>
                    </a>
                </div>
            </div>

            {{-- Card 2: Upload File & Proses Import --}}
            <div class="form-section-card">
                <div class="form-section-header" style="margin-bottom:14px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div class="import-step-badge">2</div>
                        <div>
                            <h2 style="font-size:16px; font-weight:700; margin:0; color:var(--text-primary);">Upload File Data Siswa</h2>
                            <p style="font-size:12.5px; color:var(--text-muted); margin:2px 0 0 0;">
                                Pilih atau seret file Excel yang telah Anda lengkapi dengan data santri.
                            </p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('master.import-siswa.store') }}" method="POST" enctype="multipart/form-data" id="formImportSiswa">
                    @csrf

                    <div class="upload-dropzone" id="dropzoneArea" onclick="document.getElementById('file_siswa').click()">
                        <input type="file" name="file_siswa" id="file_siswa" accept=".xlsx, .xls" style="display:none;" onchange="handleFileSelected(this)">
                        
                        <div class="upload-icon-circle">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:28px;height:28px;color:#0052FF;">
                                <polyline points="16 16 12 12 8 16"></polyline>
                                <line x1="12" y1="12" x2="12" y2="21"></line>
                                <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path>
                                <polyline points="16 16 12 12 8 16"></polyline>
                            </svg>
                        </div>

                        <div style="text-align:center;">
                            <div style="font-size:14px; font-weight:700; color:var(--text-primary);" id="uploadPromptText">
                                Klik untuk memilih file, atau seret & jatuhkan file ke sini
                            </div>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:4px;" id="uploadSubText">
                                Format didukung: .XLSX atau .XLS (Maks. 10MB)
                            </div>
                        </div>

                        <div id="fileInfoBox" style="display:none; align-items:center; gap:10px; padding:8px 14px; background:#EFF6FF; border:1px solid #BFDBFE; border-radius:8px; margin-top:10px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;color:#0052FF;">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                            <span id="fileNameLabel" style="font-size:13px; font-weight:600; color:#1D4ED8;"></span>
                            <span id="fileSizeLabel" style="font-size:12px; color:#60A5FA;"></span>
                        </div>
                    </div>

                    <div style="margin-top:18px; display:flex; flex-direction:column; gap:12px;">
                        <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer; font-size:13px; color:var(--text-primary);">
                            <input type="checkbox" name="update_existing" value="1" checked style="margin-top:3px; accent-color:#0052FF;">
                            <div>
                                <strong>Perbarui data jika siswa dengan nama yang sama sudah ada</strong>
                                <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">
                                    Jika dicentang, santri lama dengan nama yang cocok akan diperbarui data kelas, kamar, dan biodatanya.
                                </div>
                            </div>
                        </label>

                        <div style="display:flex; align-items:center; justify-content:flex-end; gap:12px; margin-top:10px;">
                            <a href="{{ route('siswa') }}" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary" id="btnSubmitImport" style="display:inline-flex; align-items:center; gap:8px; padding:9px 22px; font-weight:700;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span>Mulai Import Data Siswa</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- SISI KANAN: Panduan Format Kolom Tabel Siswa --}}
        <div style="display:flex; flex-direction:column; gap:20px;">
            <div class="form-section-card">
                <div class="form-section-header" style="margin-bottom:12px;">
                    <h2 style="font-size:15px; font-weight:700; margin:0; color:var(--text-primary);">Panduan Kolom Template</h2>
                    <p style="font-size:12px; color:var(--text-muted); margin:2px 0 0 0;">
                        Tabel berikut menjelaskan arti setiap kolom pada template upload.
                    </p>
                </div>

                <div class="table-wrapper" style="max-height:480px; overflow-y:auto; border:1px solid var(--border-light, #E2E8F0); border-radius:8px;">
                    <table class="modern-table" style="font-size:12.5px;">
                        <thead>
                            <tr>
                                <th style="padding:10px 12px;">Nama Kolom</th>
                                <th style="padding:10px 12px;">Sifat</th>
                                <th style="padding:10px 12px;">Keterangan</th>
                                <th style="padding:10px 12px;">Contoh</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($columns as $col)
                            <tr>
                                <td style="padding:8px 12px; font-family:monospace; font-weight:600; color:var(--primary, #0052FF);">
                                    {{ $col['field'] }}
                                </td>
                                <td style="padding:8px 12px;">
                                    @if($col['required'])
                                        <span class="status-badge" style="background:#FEF2F2; color:#DC2626; border:1px solid #FECACA; font-size:10.5px; padding:2px 6px;">Wajib</span>
                                    @else
                                        <span class="status-badge" style="background:#F1F5F9; color:#64748B; font-size:10.5px; padding:2px 6px;">Opsional</span>
                                    @endif
                                </td>
                                <td style="padding:8px 12px; color:var(--text-secondary);">
                                    {{ $col['desc'] }}
                                </td>
                                <td style="padding:8px 12px; font-style:italic; color:var(--text-muted);">
                                    {{ $col['example'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:14px; padding:12px; border-radius:8px; background:rgba(0, 82, 255, 0.05); border:1px solid rgba(0, 82, 255, 0.15); font-size:12px; line-height:1.5; color:var(--text-primary);">
                    <strong>💡 Tips Import Cepat:</strong>
                    <ul style="margin:4px 0 0 16px; padding:0;">
                        <li>Hanya kolom <code>nama_lengkap</code> yang harus terisi untuk membuat santri baru.</li>
                        <li>Kolom lainnya bersifat opsional dan dapat dilengkapi kemudian.</li>
                        <li>Format tanggal dapat menggunakan <code>YYYY-MM-DD</code> (contoh: <code>2010-05-15</code>) atau <code>DD/MM/YYYY</code>.</li>
                        <li>Data yang berhasil diimpor otomatis terhubung dengan modul Plotting Kelas dan Presensi Sekolah.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.import-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
@media (max-width: 992px) {
    .import-grid {
        grid-template-columns: 1fr;
    }
}

.import-step-badge {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #0052FF;
    color: #FFFFFF;
    font-weight: 700;
    font-size: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.csv-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: #ECFDF5;
    border: 1px solid #A7F3D0;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.dark-mode .csv-icon-wrap {
    background: rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.3);
}

.upload-dropzone {
    border: 2px dashed var(--border-light, #CBD5E1);
    border-radius: 12px;
    padding: 36px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: var(--bg-card, #FFFFFF);
    cursor: pointer;
    transition: all 0.2s ease;
}
.upload-dropzone:hover {
    border-color: #0052FF;
    background: rgba(0, 82, 255, 0.02);
}
.dark-mode .upload-dropzone {
    border-color: #334155;
    background: #141C28;
}
.dark-mode .upload-dropzone:hover {
    border-color: #38BDF8;
    background: rgba(56, 189, 248, 0.05);
}

.upload-icon-circle {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: rgba(0, 82, 255, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
}
.dark-mode .upload-icon-circle {
    background: rgba(56, 189, 248, 0.15);
}
</style>

<script>
function handleFileSelected(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        document.getElementById('fileInfoBox').style.display = 'inline-flex';
        document.getElementById('fileNameLabel').textContent = file.name;
        document.getElementById('fileSizeLabel').textContent = '(' + (file.size / 1024).toFixed(1) + ' KB)';
        document.getElementById('uploadPromptText').textContent = 'File siap diimport';
        document.getElementById('uploadSubText').textContent = 'Klik "Mulai Import Data Siswa" di bawah untuk memproses.';
    }
}

// Drag & drop handlers
const dropzone = document.getElementById('dropzoneArea');
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropzone.addEventListener(eventName, e => {
        e.preventDefault();
        e.stopPropagation();
    }, false);
});

['dragenter', 'dragover'].forEach(eventName => {
    dropzone.addEventListener(eventName, () => {
        dropzone.style.borderColor = '#0052FF';
        dropzone.style.background = 'rgba(0, 82, 255, 0.05)';
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropzone.addEventListener(eventName, () => {
        dropzone.style.borderColor = '';
        dropzone.style.background = '';
    }, false);
});

dropzone.addEventListener('drop', e => {
    const dt = e.dataTransfer;
    const files = dt.files;
    if (files && files[0]) {
        const fileInput = document.getElementById('file_siswa');
        fileInput.files = files;
        handleFileSelected(fileInput);
    }
});
</script>
@endsection
