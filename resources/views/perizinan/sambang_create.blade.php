@extends('layouts.app')

@section('title', 'Input Perizinan Sambang')

@section('header-actions')
<a href="{{ route('perizinan.sambang') }}" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    Kembali ke Daftar
</a>
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:20px; width:100%;">

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

    {{-- Form Perizinan Sambang Siswa --}}
    <div class="form-section-card" style="padding:26px 28px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:22px; padding-bottom:14px; border-bottom:1px solid var(--border-subtle);">
            <div style="width:38px; height:38px; border-radius:10px; background-color:var(--electric-blue-light); color:var(--electric-blue); display:flex; align-items:center; justify-content:center;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <polyline points="17 11 19 13 23 9"></polyline>
                </svg>
            </div>
            <h2 style="font-size:17px; font-weight:700; color:var(--text-primary); margin:0;">Formulir Perizinan Sambang</h2>
        </div>

        <form action="{{ route('perizinan.sambang.store') }}" method="POST" id="formPerizinanSambang">
            @csrf

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:18px; margin-bottom:18px;">
                {{-- Form Input dan Prediksi Menjadi Satu --}}
                <div class="form-group" style="position:relative;">
                    <label for="input_nama_siswa" class="form-label">Nama Santri <span class="required-mark">*</span></label>
                    <div style="position:relative; display:flex; align-items:center;">
                        <input type="text" id="input_nama_siswa" class="form-control" placeholder="Ketik nama santri..." autocomplete="off" required style="padding-right:34px;">
                        <input type="hidden" name="siswa_id" id="siswa_id" value="{{ old('siswa_id') }}" required>
                        <div id="btn_toggle_prediksi" style="position:absolute; right:12px; cursor:pointer; color:var(--text-muted); display:flex; align-items:center; user-select:none;" title="Tampilkan daftar santri">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                    </div>
                    {{-- Dropdown Prediksi Mengambang (Hanya Nama Santri Saja) --}}
                    <div id="list_prediksi" class="autocomplete-dropdown"></div>
                </div>

                {{-- Kamar Otomatis Terisi --}}
                <div class="form-group">
                    <label for="kamar_siswa" class="form-label">Kamar / Asrama <span class="required-mark">*</span></label>
                    <input type="text" id="kamar_siswa" name="kamar_display" class="form-control" readonly placeholder="Otomatis terisi setelah nama dipilih" style="background-color:var(--nav-hover-bg); cursor:not-allowed;">
                </div>

                {{-- Tanggal Mulai Sambang --}}
                <div class="form-group">
                    <label for="tanggal_mulai" class="form-label">Tanggal Mulai Sambang <span class="required-mark">*</span></label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ old('tanggal_mulai', $today) }}" class="form-control" required>
                </div>

                {{-- Tanggal Estimasi Kembali --}}
                <div class="form-group">
                    <label for="tanggal_kembali" class="form-label">Tanggal Estimasi Kembali <span class="required-mark">*</span></label>
                    <input type="date" name="tanggal_kembali" id="tanggal_kembali" value="{{ old('tanggal_kembali', \Carbon\Carbon::parse($today)->addDays(3)->toDateString()) }}" class="form-control" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:22px;">
                <label for="keterangan" class="form-label">Keterangan / Keperluan</label>
                <input type="text" name="keterangan" id="keterangan" value="{{ old('keterangan') }}" class="form-control" placeholder="Contoh: Acara keluarga, menjenguk orang tua, dll. (Opsional)">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <a href="{{ route('perizinan.sambang') }}" class="btn btn-secondary" style="padding:10px 20px;">Batal</a>
                <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Simpan Perizinan Sambang
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dataSantri = @json($daftarSiswa->map(fn($s) => ['id' => $s->id, 'nama' => $s->nama_lengkap, 'kamar' => $s->kamar]));
    const inputNama = document.getElementById('input_nama_siswa');
    const inputId = document.getElementById('siswa_id');
    const inputKamar = document.getElementById('kamar_siswa');
    const listPrediksi = document.getElementById('list_prediksi');
    const btnToggle = document.getElementById('btn_toggle_prediksi');

    if (!inputNama || !listPrediksi) return;

    let selectedIndex = -1;

    function renderPrediksi(query = '') {
        const q = query.trim().toLowerCase();
        const filtered = dataSantri.filter(s => !q || s.nama.toLowerCase().includes(q));

        listPrediksi.innerHTML = '';
        selectedIndex = -1;

        if (filtered.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'autocomplete-empty';
            empty.textContent = 'Santri tidak ditemukan';
            listPrediksi.appendChild(empty);
        } else {
            filtered.forEach((item) => {
                const el = document.createElement('div');
                el.className = 'autocomplete-item';
                // HANYA NAMA SAJA (kamar tidak ditampilkan pada opsi)
                el.textContent = item.nama;
                el.setAttribute('data-id', item.id);
                el.setAttribute('data-nama', item.nama);
                el.setAttribute('data-kamar', item.kamar);

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
        inputId.value = item.id;
        inputKamar.value = item.kamar; // Kamar otomatis terisi
        tutupPrediksi();
    }

    // Ketika mengetik nama
    inputNama.addEventListener('input', function () {
        const val = this.value;
        renderPrediksi(val);

        // Cari apakah cocok persis dengan salah satu nama
        const exact = dataSantri.find(s => s.nama.toLowerCase() === val.trim().toLowerCase());
        if (exact) {
            inputId.value = exact.id;
            inputKamar.value = exact.kamar;
        } else {
            inputId.value = '';
            inputKamar.value = '';
        }
    });

    // Buka prediksi saat input fokus
    inputNama.addEventListener('focus', function () {
        renderPrediksi(this.value);
    });

    // Klik toggle panah
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

    // Navigasi keyboard (ArrowDown, ArrowUp, Enter, Escape)
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
            updateSelection(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = (selectedIndex - 1 + items.length) % items.length;
            updateSelection(items);
        } else if (e.key === 'Enter') {
            if (selectedIndex >= 0 && selectedIndex < items.length) {
                e.preventDefault();
                const item = items[selectedIndex];
                pilihSantri({
                    id: item.getAttribute('data-id'),
                    nama: item.getAttribute('data-nama'),
                    kamar: item.getAttribute('data-kamar')
                });
            }
        } else if (e.key === 'Escape') {
            tutupPrediksi();
        }
    });

    function updateSelection(items) {
        items.forEach((item, idx) => {
            if (idx === selectedIndex) {
                item.classList.add('is-selected');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('is-selected');
            }
        });
    }

    // Klik di luar menutup dropdown
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#input_nama_siswa, #list_prediksi, #btn_toggle_prediksi')) {
            tutupPrediksi();
        }
    });

    // Inisialisasi jika ada old('siswa_id')
    const oldId = inputId.value;
    if (oldId) {
        const found = dataSantri.find(s => s.id == oldId);
        if (found) {
            inputNama.value = found.nama;
            inputKamar.value = found.kamar;
        }
    }
});
</script>
@endsection
