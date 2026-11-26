@extends('layouts.app')

@section('title', 'Input Pelanggaran')

@section('header-actions')
@if(!auth()->check() || auth()->user()->canDo('pelanggaran_rekap', 'view'))
<a href="{{ route('pelanggaran.rekap') }}" class="btn btn-secondary">
    Rekap Pelanggaran
</a>
@endif
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; width:100%;">

    @if ($errors->any())
    <div class="alert-banner alert-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <div class="form-section-card" style="padding:24px 26px;">
        <form action="{{ route('pelanggaran.store') }}" method="POST" id="formInputPelanggaran">
            @csrf

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:18px; margin-bottom:18px;">
                <div class="form-group" style="position:relative;">
                    <label for="input_nama_siswa" class="form-label">Nama <span class="required-mark">*</span></label>
                    <div style="position:relative; display:flex; align-items:center;">
                        <input type="text" id="input_nama_siswa" class="form-control" placeholder="Ketik nama siswa..." autocomplete="off" required style="padding-right:34px;">
                        <input type="hidden" name="siswa_id" id="siswa_id" value="{{ old('siswa_id') }}" required>
                        <div id="btn_toggle_prediksi" style="position:absolute; right:12px; cursor:pointer; color:var(--text-muted); display:flex; align-items:center; user-select:none;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div id="list_prediksi" class="autocomplete-dropdown"></div>
                </div>

                <div class="form-group">
                    <label for="kelas_siswa" class="form-label">Kelas</label>
                    <input type="text" id="kelas_siswa" class="form-control" readonly placeholder="Otomatis terisi" style="background-color:var(--nav-hover-bg); cursor:not-allowed;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom:22px;">
                <label for="pelanggaran" class="form-label">Pelanggaran <span class="required-mark">*</span></label>
                <textarea name="pelanggaran" id="pelanggaran" rows="4" class="form-control" placeholder="Tulis pelanggaran..." required>{{ old('pelanggaran') }}</textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
                    Simpan
                </button>
            </div>
        </form>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dataSiswa = @json($daftarSiswa->map(fn ($s) => ['id' => $s->id, 'nama' => $s->nama_lengkap, 'kelas' => $s->kelas_tampil])->values());
    var inputNama = document.getElementById('input_nama_siswa');
    var inputId = document.getElementById('siswa_id');
    var inputKelas = document.getElementById('kelas_siswa');
    var listPrediksi = document.getElementById('list_prediksi');
    var btnToggle = document.getElementById('btn_toggle_prediksi');
    var formEl = document.getElementById('formInputPelanggaran');

    if (!inputNama || !listPrediksi) return;

    var selectedIndex = -1;

    function renderPrediksi(query) {
        var q = String(query || '').trim().toLowerCase();
        var filtered = dataSiswa.filter(function (s) {
            return !q || s.nama.toLowerCase().indexOf(q) !== -1;
        });

        listPrediksi.innerHTML = '';
        selectedIndex = -1;

        if (filtered.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'autocomplete-empty';
            empty.textContent = 'Siswa tidak ditemukan';
            listPrediksi.appendChild(empty);
        } else {
            filtered.forEach(function (item) {
                var el = document.createElement('div');
                el.className = 'autocomplete-item';
                el.textContent = item.nama;
                el.setAttribute('data-id', item.id);
                el.setAttribute('data-nama', item.nama);
                el.setAttribute('data-kelas', item.kelas);

                el.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    pilihSiswa(item);
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

    function pilihSiswa(item) {
        inputNama.value = item.nama;
        inputId.value = item.id;
        inputKelas.value = item.kelas || '-';
        tutupPrediksi();
    }

    function updateSelection(items) {
        items.forEach(function (item, idx) {
            if (idx === selectedIndex) {
                item.classList.add('is-selected');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('is-selected');
            }
        });
    }

    // Restore old selection if validation returned
    if (inputId.value) {
        var oldMatch = dataSiswa.find(function (s) { return String(s.id) === String(inputId.value); });
        if (oldMatch) {
            inputNama.value = oldMatch.nama;
            inputKelas.value = oldMatch.kelas || '-';
        }
    }

    inputNama.addEventListener('input', function () {
        var val = this.value;
        renderPrediksi(val);

        var exact = dataSiswa.find(function (s) {
            return s.nama.toLowerCase() === val.trim().toLowerCase();
        });
        if (exact) {
            inputId.value = exact.id;
            inputKelas.value = exact.kelas || '-';
        } else {
            inputId.value = '';
            inputKelas.value = '';
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
        var items = listPrediksi.querySelectorAll('.autocomplete-item');
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
                var it = items[selectedIndex];
                pilihSiswa({
                    id: it.getAttribute('data-id'),
                    nama: it.getAttribute('data-nama'),
                    kelas: it.getAttribute('data-kelas')
                });
            }
        } else if (e.key === 'Escape') {
            tutupPrediksi();
        }
    });

    document.addEventListener('click', function (e) {
        if (!inputNama.contains(e.target) && !listPrediksi.contains(e.target) && (!btnToggle || !btnToggle.contains(e.target))) {
            tutupPrediksi();
        }
    });

    if (formEl) {
        formEl.addEventListener('submit', function (e) {
            if (!inputId.value) {
                e.preventDefault();
                inputNama.focus();
                renderPrediksi(inputNama.value);
            }
        });
    }
});
</script>
@endpush
@endsection
