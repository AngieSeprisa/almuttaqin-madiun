@extends('layouts.app')

@section('title', 'Catat Santriwati Haid')

@section('header-actions')
<a href="{{ route('perizinan.haid') }}" class="btn btn-secondary">
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

    {{-- Form Pencatatan Haid Santriwati --}}
    <div class="form-section-card" style="padding:26px 28px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:22px; padding-bottom:14px; border-bottom:1px solid var(--border-subtle);">
            <div style="width:40px; height:40px; border-radius:10px; background-color:#fdf2f8; color:#db2777; display:flex; align-items:center; justify-content:center; font-size:20px;">
                🌸
            </div>
            <div>
                <h2 style="font-size:16px; font-weight:700; color:var(--text-primary); margin:0;">Catat Santriwati Haid</h2>
            </div>
        </div>

        <form action="{{ route('perizinan.haid.store') }}" method="POST" id="formPerizinanHaid">
            @csrf

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:18px; margin-bottom:18px;">
                {{-- Input Ketik Nama & Prediksi Bersatu --}}
                <div class="form-group" style="position:relative;">
                    <label for="input_nama_siswa" class="form-label">Nama Santriwati <span class="required-mark">*</span></label>
                    <div style="position:relative; display:flex; align-items:center;">
                        <input type="text" id="input_nama_siswa" class="form-control" placeholder="Ketik nama santriwati..." autocomplete="off" required style="padding-right:34px;">
                        <input type="hidden" name="siswa_id" id="siswa_id" value="{{ old('siswa_id') }}" required>
                        <div id="btn_toggle_prediksi" style="position:absolute; right:12px; cursor:pointer; color:var(--text-muted); display:flex; align-items:center; user-select:none;" title="Tampilkan daftar santri">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div id="list_prediksi" class="autocomplete-dropdown"></div>
                </div>

                {{-- Kamar Otomatis Terisi --}}
                <div class="form-group">
                    <label for="kamar_siswa" class="form-label">Kamar / Asrama Putri</label>
                    <input type="text" id="kamar_siswa" name="kamar" class="form-control" readonly placeholder="Otomatis terisi setelah nama dipilih" style="background-color:var(--nav-hover-bg); cursor:not-allowed;">
                </div>

                {{-- Tanggal Mulai Haid --}}
                <div class="form-group">
                    <label for="tanggal_mulai" class="form-label">Tanggal Mulai Haid <span class="required-mark">*</span></label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ old('tanggal_mulai', $today) }}" class="form-control" required>
                </div>

                {{-- Estimasi Selesai (Opsional) --}}
                <div class="form-group">
                    <label for="tanggal_selesai" class="form-label">Estimasi Selesai (7 Hari)</label>
                    <input type="date" name="tanggal_selesai" id="tanggal_selesai" value="{{ old('tanggal_selesai', $estimasiSelesai) }}" class="form-control">
                </div>
            </div>

            {{-- Status Awal --}}
            <div class="form-group" style="max-width:280px; margin-bottom:24px;">
                <label for="status" class="form-label">Status Awal</label>
                <select name="status" id="status" class="form-select">
                    <option value="sedang_haid" selected>Sedang Haid</option>
                    <option value="sudah_suci">Sudah Suci</option>
                </select>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <a href="{{ route('perizinan.haid') }}" class="btn btn-secondary" style="padding:10px 20px;">Batal</a>
                <button type="submit" class="btn btn-primary" style="padding:10px 24px; background:#db2777; border-color:#db2777;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Simpan Perizinan Haid
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
            empty.textContent = 'Tidak ada nama santriwati yang cocok';
            empty.style.padding = '10px 14px';
            empty.style.color = '#94a3b8';
            empty.style.fontSize = '13px';
            listPrediksi.appendChild(empty);
            listPrediksi.style.display = 'block';
            return;
        }

        filtered.forEach((s) => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.style.padding = '10px 14px';
            item.style.cursor = 'pointer';
            item.style.borderBottom = '1px solid #f1f5f9';
            item.style.display = 'flex';
            item.style.justifyContent = 'space-between';
            item.style.alignItems = 'center';

            item.innerHTML = `
                <span style="font-weight:600; color:#1e293b;">${s.nama}</span>
                <span style="font-size:12px; color:#64748b; background:#f8fafc; padding:2px 8px; border-radius:4px;">${s.kamar || '-'}</span>
            `;

            item.addEventListener('mouseenter', () => {
                item.style.background = '#fdf2f8';
            });
            item.addEventListener('mouseleave', () => {
                item.style.background = 'transparent';
            });

            item.addEventListener('mousedown', function (e) {
                e.preventDefault();
                pilihSantri(s);
            });

            listPrediksi.appendChild(item);
        });

        listPrediksi.style.display = 'block';
    }

    function pilihSantri(s) {
        inputNama.value = s.nama;
        inputId.value = s.id;
        inputKamar.value = s.kamar || '-';
        listPrediksi.style.display = 'none';
    }

    inputNama.addEventListener('input', function () {
        inputId.value = '';
        renderPrediksi(this.value);
    });

    inputNama.addEventListener('focus', function () {
        renderPrediksi(this.value);
    });

    if (btnToggle) {
        btnToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            if (listPrediksi.style.display === 'block') {
                listPrediksi.style.display = 'none';
            } else {
                inputNama.focus();
                renderPrediksi('');
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (!inputNama.contains(e.target) && !listPrediksi.contains(e.target) && (!btnToggle || !btnToggle.contains(e.target))) {
            listPrediksi.style.display = 'none';
        }
    });
});
</script>
@endsection

