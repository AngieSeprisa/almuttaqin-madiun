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
                    <p style="font-size:12.5px; color:var(--text-secondary); margin:2px 0 0;">Pilih buku yang dipinjam, lengkapi data peminjam, lalu klik Simpan</p>
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

        {{-- Area Scanner Barcode Opsional --}}
        <div id="pinjamScannerWrap" style="display:none; margin-bottom:20px; padding:16px; border-radius:12px; background:#f8fafc; border:1px dashed #cbd5e1;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                <span style="font-size:13px; font-weight:700; color:var(--text-primary);">Arahkan Kamera HP ke Barcode Buku (Otomatis Terpilih Saat Terbaca)</span>
                <button type="button" class="btn btn-sm btn-secondary" onclick="stopPinjamScanner()">Tutup Kamera</button>
            </div>
            <div style="width:100%; max-width:420px; margin:0 auto; border-radius:10px; overflow:hidden; background:#0f172a; position:relative;">
                <video id="pinjamNativeVideo" playsinline muted autoplay style="display:none; width:100%; max-height:260px; object-fit:cover;"></video>
                <div id="pinjamBarcodeReader" style="width:100%;"></div>
            </div>
            <div id="pinjamScanFeedback" style="display:none; margin-top:10px; padding:9px 12px; border-radius:8px; font-size:12.5px; font-weight:600;"></div>
        </div>

        <form action="{{ route('perpustakaan.pinjam.store') }}" method="POST" id="formPinjamBuku">
            @csrf

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:18px; margin-bottom:18px;">
                {{-- Pilih Buku --}}
                <div class="form-group">
                    <label for="perpustakaan_buku_id" class="form-label required">Pilih Buku yang Dipinjam</label>
                    <select name="perpustakaan_buku_id" id="perpustakaan_buku_id" class="form-select" required>
                        <option value="">-- Pilih Buku Perpustakaan --</option>
                        @foreach ($bukuList as $buku)
                            @php
                                $isSelected = (string) old('perpustakaan_buku_id', $selectedBukuId) === (string) $buku->id;
                                $habis = $buku->stok_tersedia <= 0;
                            @endphp
                            <option value="{{ $buku->id }}"
                                    data-kode="{{ $buku->kode_buku }}"
                                    data-barcode="{{ $buku->barcode }}"
                                    data-stok="{{ $buku->stok_tersedia }}"
                                    {{ $isSelected ? 'selected' : '' }}
                                    {{ $habis && !$isSelected ? 'disabled' : '' }}>
                                [{{ $buku->kode_buku }}] {{ $buku->judul_buku }} — (Tersedia: {{ $buku->stok_tersedia }})
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
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dataSantri = @json($santriJsonData);

    const inputNama = document.getElementById('input_nama_peminjam');
    const inputSiswaId = document.getElementById('siswa_id');
    const inputKelasKamar = document.getElementById('kelas_kamar');
    const listPrediksi = document.getElementById('list_prediksi_santri');
    const btnToggle = document.getElementById('btn_toggle_santri');

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

// Scanner Barcode Buku pada Form Pinjam (Langsung Otomatis Terpilih Saat Terbaca)
let pinjamQrScanner = null;
let pinjamNativeStream = null;
let pinjamNativeTimer = null;
let isPinjamScanning = false;

async function togglePinjamScanner() {
    const wrap = document.getElementById('pinjamScannerWrap');
    if (isPinjamScanning) {
        await stopPinjamScanner();
        return;
    }

    wrap.style.display = 'block';

    // Prioritas 1: Native BarcodeDetector
    if ('BarcodeDetector' in window && navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        try {
            const supported = await window.BarcodeDetector.getSupportedFormats();
            const wanted = ['ean_13', 'ean_8', 'code_128', 'code_39', 'upc_a', 'upc_e', 'itf', 'qr_code', 'codabar']
                .filter(f => supported.includes(f));
            if (wanted.length > 0) {
                const detector = new window.BarcodeDetector({ formats: wanted });
                const video = document.getElementById('pinjamNativeVideo');
                pinjamNativeStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: false
                });
                video.srcObject = pinjamNativeStream;
                video.style.display = 'block';
                await video.play();
                isPinjamScanning = true;

                pinjamNativeTimer = setInterval(async () => {
                    if (!isPinjamScanning || !video || video.readyState < 2) return;
                    try {
                        const codes = await detector.detect(video);
                        if (codes && codes.length > 0 && codes[0].rawValue) {
                            const code = codes[0].rawValue.trim();
                            if (code) {
                                await stopPinjamScanner();
                                matchBookByScannedCode(code);
                            }
                        }
                    } catch (e) {}
                }, 120);
                return;
            }
        } catch (e) {
            stopPinjamNativeStream();
        }
    }

    // Prioritas 2: Html5Qrcode
    if (typeof Html5Qrcode === 'undefined') {
        showPinjamScanFeedback('Library scanner belum siap, silakan pilih buku dari daftar.', true);
        return;
    }

    try {
        if (!pinjamQrScanner) {
            let formats = undefined;
            if (typeof Html5QrcodeSupportedFormats !== 'undefined') {
                formats = [
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.UPC_A,
                    Html5QrcodeSupportedFormats.UPC_E,
                    Html5QrcodeSupportedFormats.ITF,
                    Html5QrcodeSupportedFormats.QR_CODE
                ];
            }
            pinjamQrScanner = new Html5Qrcode('pinjamBarcodeReader', {
                formatsToSupport: formats,
                experimentalFeatures: { useBarCodeDetectorIfSupported: true },
                verbose: false
            });
        }
        await pinjamQrScanner.start(
            { facingMode: 'environment' },
            { fps: 15, qrbox: { width: 250, height: 120 } },
            (decodedText) => {
                const code = (decodedText || '').trim();
                if (!code) return;
                stopPinjamScanner();
                matchBookByScannedCode(code);
            },
            () => {}
        );
        isPinjamScanning = true;
    } catch (e) {
        showPinjamScanFeedback('Tidak dapat membuka kamera. Pastikan izin kamera aktif.', true);
    }
}

function stopPinjamNativeStream() {
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
}

async function stopPinjamScanner() {
    stopPinjamNativeStream();
    if (pinjamQrScanner && isPinjamScanning) {
        try {
            await pinjamQrScanner.stop();
        } catch (e) {}
    }
    isPinjamScanning = false;
    const wrap = document.getElementById('pinjamScannerWrap');
    if (wrap) wrap.style.display = 'none';
}

function matchBookByScannedCode(code) {
    const select = document.getElementById('perpustakaan_buku_id');
    if (!select) return;

    const clean = code.toLowerCase();
    let matchedOption = null;

    Array.from(select.options).forEach(opt => {
        const barcode = (opt.getAttribute('data-barcode') || '').toLowerCase();
        const kode = (opt.getAttribute('data-kode') || '').toLowerCase();
        if ((barcode && barcode === clean) || (kode && kode === clean)) {
            matchedOption = opt;
        }
    });

    if (matchedOption) {
        select.value = matchedOption.value;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        showPinjamScanFeedback('Buku ditemukan: ' + matchedOption.textContent.trim(), false);
    } else {
        showPinjamScanFeedback('Buku dengan barcode "' + code + '" belum terdaftar di Daftar Buku.', true);
    }
}

function showPinjamScanFeedback(msg, isError) {
    const el = document.getElementById('pinjamScanFeedback');
    const wrap = document.getElementById('pinjamScannerWrap');
    if (!el) return;
    if (wrap) wrap.style.display = 'block';
    el.style.display = 'block';
    el.style.background = isError ? '#fef2f2' : '#ecfdf5';
    el.style.color = isError ? '#b91c1c' : '#047857';
    el.style.border = isError ? '1px solid #fecaca' : '1px solid #a7f3d0';
    el.textContent = msg;
}
</script>
@endpush
@endsection

