@extends('layouts.app')

@section('title', $isEdit ? 'Ubah Data Ujian' : 'Buat Ujian Baru')

@section('header-actions')
<a href="{{ route('ujian.index') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px;">
    &larr; Kembali
</a>
@endsection

@section('content')
<style>
    .ujian-form-card {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        width: 100%;
        overflow: hidden;
    }
    .ujian-form-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--border-light, #e2e8f0);
        background: var(--bg-page, #f8fafc);
    }
    .ujian-form-body {
        padding: 24px;
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 18px;
    }
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .form-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-primary, #0f172a);
    }
    .form-input, .form-select {
        padding: 9px 13px;
        font-size: 13.5px;
        border: 1px solid var(--border-light, #cbd5e1);
        border-radius: 8px;
        background: var(--bg-surface, #ffffff);
        color: var(--text-primary, #0f172a);
    }
    .form-input:focus, .form-select:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
    }
    .form-help {
        font-size: 11.5px;
        color: #64748b;
    }
</style>

<div class="ujian-form-card">
    <div class="ujian-form-header">
        <h2 style="font-size:17px; font-weight:700; margin:0 0 4px 0; color:var(--text-primary, #0f172a);">
            {{ $isEdit ? 'Ubah Informasi Ujian' : 'Form Pembuatan Ujian Baru' }}
        </h2>
        <p style="font-size:12.5px; color:#64748b; margin:0;">
            Lengkapi detail ujian di bawah ini untuk menghasilkan Lembar Jawab Komputer (LJK) ber-QR Code otomatis.
        </p>
    </div>

    <form action="{{ $isEdit ? route('ujian.update', $ujian->id) : route('ujian.store') }}" method="POST">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="ujian-form-body">
            @if($errors->any())
            <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:12px 16px; border-radius:10px; margin-bottom:18px; font-size:13px;">
                <ul style="margin:0; padding-left:18px;">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="form-grid">
                {{-- KODE UJIAN --}}
                <div class="form-group">
                    <label class="form-label">Kode Ujian <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="kode_ujian" class="form-input" value="{{ old('kode_ujian', $ujian->kode_ujian) }}" required style="font-family:monospace; font-weight:600;">
                    <span class="form-help">Kode unik untuk QR Code LJK (mis. UJN-202611-001)</span>
                </div>

                {{-- NAMA UJIAN --}}
                <div class="form-group">
                    <label class="form-label">Nama Ujian <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="nama_ujian" class="form-input" value="{{ old('nama_ujian', $ujian->nama_ujian) }}" placeholder="Contoh: Penilaian Harian 1 Semester Ganjil" required>
                </div>

                {{-- MATA PELAJARAN --}}
                <div class="form-group">
                    <label class="form-label">Mata Pelajaran <span style="color:#ef4444;">*</span></label>
                    <select name="mapel_id" class="form-select" required>
                        <option value="">-- Pilih Mata Pelajaran --</option>
                        @foreach($mapels as $m)
                        <option value="{{ $m->id }}" {{ old('mapel_id', $ujian->mapel_id) == $m->id ? 'selected' : '' }}>
                            {{ $m->mata_pelajaran }} {{ $m->lembaga ? '(' . $m->lembaga->nama_singkat . ')' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- KELAS PESERTA --}}
                <div class="form-group">
                    <label class="form-label">Kelas Peserta <span style="color:#ef4444;">*</span></label>
                    <select name="kelas_id" class="form-select" required>
                        <option value="">-- Pilih Kelas --</option>
                        @foreach($kelases as $k)
                        <option value="{{ $k->id }}" {{ old('kelas_id', $ujian->kelas_id) == $k->id ? 'selected' : '' }}>
                            {{ $k->nama_kelas }} ({{ $k->jenjang }})
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- GURU PENGAMPU --}}
                <div class="form-group">
                    <label class="form-label">Guru Pengampu</label>
                    <select name="guru_id" class="form-select">
                        <option value="">-- Pilih Guru --</option>
                        @foreach($gurus as $g)
                        <option value="{{ $g->id }}" {{ old('guru_id', $ujian->guru_id) == $g->id ? 'selected' : '' }}>
                            {{ $g->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- TANGGAL UJIAN --}}
                <div class="form-group">
                    <label class="form-label">Tanggal Ujian <span style="color:#ef4444;">*</span></label>
                    <input type="date" name="tanggal_ujian" class="form-input" value="{{ old('tanggal_ujian', $ujian->tanggal_ujian?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
                </div>

                {{-- JUMLAH SOAL PG --}}
                <div class="form-group">
                    <label class="form-label">Jumlah Soal Pilihan Ganda <span style="color:#ef4444;">*</span></label>
                    <input type="number" min="0" max="100" name="jumlah_soal" id="inputJumlahPg" class="form-input" value="{{ old('jumlah_soal', $ujian->jumlah_soal ?? 40) }}" required oninput="syncBobotFields()">
                    <span class="form-help">Isi 0 jika ujian hanya berupa soal Essay.</span>
                </div>

                {{-- JUMLAH SOAL ESSAY --}}
                <div class="form-group">
                    <label class="form-label">Jumlah Soal Essay / Uraian</label>
                    <input type="number" min="0" max="50" name="jumlah_soal_essay" id="inputJumlahEssay" class="form-input" value="{{ old('jumlah_soal_essay', $ujian->jumlah_soal_essay ?? 0) }}" oninput="syncBobotFields()">
                    <span class="form-help">Isi 0 jika hanya PG. LJK otomatis 1 halaman dan menambah halaman belakang bila ruang kurang.</span>
                </div>

                {{-- JUMLAH OPSI JAWABAN --}}
                <div class="form-group">
                    <label class="form-label">Pilihan Opsi Jawaban <span style="color:#ef4444;">*</span></label>
                    <select name="jumlah_opsi" class="form-select" required>
                        <option value="4" {{ old('jumlah_opsi', $ujian->jumlah_opsi) == 4 ? 'selected' : '' }}>4 Opsi (A, B, C, D) - Standar SMP</option>
                        <option value="5" {{ old('jumlah_opsi', $ujian->jumlah_opsi) == 5 ? 'selected' : '' }}>5 Opsi (A, B, C, D, E) - Standar SMA</option>
                    </select>
                </div>

                {{-- BOBOT PG (%) --}}
                <div class="form-group" id="groupBobotPg">
                    <label class="form-label">Bobot Pilihan Ganda (%)</label>
                    <input type="number" step="0.5" min="0" max="100" name="bobot_pg" id="inputBobotPg" class="form-input" value="{{ old('bobot_pg', (float) ($ujian->bobot_pg ?? 100)) }}" oninput="onBobotPgChanged()">
                    <span class="form-help">Persentase kontribusi nilai PG terhadap Nilai Akhir.</span>
                </div>

                {{-- BOBOT ESSAY (%) --}}
                <div class="form-group" id="groupBobotEssay">
                    <label class="form-label">Bobot Essay (%)</label>
                    <input type="number" step="0.5" min="0" max="100" name="bobot_essay" id="inputBobotEssay" class="form-input" value="{{ old('bobot_essay', (float) ($ujian->bobot_essay ?? 0)) }}" oninput="onBobotEssayChanged()">
                    <span class="form-help" id="bobotSummaryText">Total Bobot PG + Essay = 100%.</span>
                </div>

                {{-- TIMER PG --}}
                <div class="form-group">
                    <label class="form-label">Timer Soal PG (Detik)</label>
                    <input type="number" min="10" max="1800" name="timer_default" class="form-input" value="{{ old('timer_default', $ujian->timer_default ?? 90) }}">
                    <span class="form-help">Waktu tampil proyektor per soal PG (default 90 detik).</span>
                </div>

                {{-- TIMER ESSAY --}}
                <div class="form-group" id="groupTimerEssay">
                    <label class="form-label">Timer Soal Essay (Detik)</label>
                    <input type="number" min="15" max="3600" name="timer_essay_default" class="form-input" value="{{ old('timer_essay_default', $ujian->timer_essay_default ?? 180) }}">
                    <span class="form-help">Waktu tampil proyektor per soal Essay (default 180 detik).</span>
                </div>

                {{-- BOBOT BENAR PER SOAL --}}
                <div class="form-group">
                    <label class="form-label">Bobot Benar Default PG</label>
                    <input type="number" step="0.1" min="0.1" max="100" name="bobot_benar" class="form-input" value="{{ old('bobot_benar', $ujian->bobot_benar ?? 1.00) }}" required>
                    <span class="form-help">Nilai PG = (Total Bobot Benar / Total Bobot Maks) &times; 100</span>
                </div>

                {{-- STATUS UJIAN --}}
                <div class="form-group">
                    <label class="form-label">Status Ujian <span style="color:#ef4444;">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="draft" {{ old('status', $ujian->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="aktif" {{ old('status', $ujian->status) === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="selesai" {{ old('status', $ujian->status) === 'selesai' ? 'selected' : '' }}>Selesai</option>
                    </select>
                </div>
            </div>

            <div style="margin-top:28px; padding-top:20px; border-top:1px solid var(--border-light, #e2e8f0); display:flex; justify-content:flex-end; gap:10px;">
                <a href="{{ route('ujian.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary" style="padding:9px 24px; font-weight:600;">
                    {{ $isEdit ? 'Simpan Perubahan' : 'Lanjut ke Kunci Jawaban &rarr;' }}
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    function syncBobotFields() {
        const pgCount = parseInt(document.getElementById('inputJumlahPg').value || '0', 10);
        const esCount = parseInt(document.getElementById('inputJumlahEssay').value || '0', 10);
        const bobotPgEl = document.getElementById('inputBobotPg');
        const bobotEsEl = document.getElementById('inputBobotEssay');
        const groupBobotPg = document.getElementById('groupBobotPg');
        const groupBobotEs = document.getElementById('groupBobotEssay');
        const groupTimerEs = document.getElementById('groupTimerEssay');
        const summary = document.getElementById('bobotSummaryText');

        if (esCount <= 0) {
            bobotPgEl.value = 100;
            bobotEsEl.value = 0;
            bobotPgEl.readOnly = true;
            bobotEsEl.readOnly = true;
            groupBobotPg.style.display = 'none';
            groupBobotEs.style.display = 'none';
            groupTimerEs.style.display = 'none';
        } else if (pgCount <= 0) {
            bobotPgEl.value = 0;
            bobotEsEl.value = 100;
            bobotPgEl.readOnly = true;
            bobotEsEl.readOnly = true;
            groupBobotPg.style.display = 'none';
            groupBobotEs.style.display = 'flex';
            groupTimerEs.style.display = 'flex';
            summary.textContent = 'Ujian khusus Essay (Bobot Essay 100%).';
        } else {
            groupBobotPg.style.display = 'flex';
            groupBobotEs.style.display = 'flex';
            groupTimerEs.style.display = 'flex';
            bobotPgEl.readOnly = false;
            bobotEsEl.readOnly = false;

            const curPg = parseFloat(bobotPgEl.value || '0');
            const curEs = parseFloat(bobotEsEl.value || '0');
            if (curPg <= 0 || curEs <= 0 || Math.abs((curPg + curEs) - 100) > 0.1) {
                bobotPgEl.value = 70;
                bobotEsEl.value = 30;
            }
            updateBobotSummary();
        }
    }

    function onBobotPgChanged() {
        const pg = Math.max(0, Math.min(100, parseFloat(document.getElementById('inputBobotPg').value || '0')));
        document.getElementById('inputBobotEssay').value = Math.round((100 - pg) * 100) / 100;
        updateBobotSummary();
    }

    function onBobotEssayChanged() {
        const es = Math.max(0, Math.min(100, parseFloat(document.getElementById('inputBobotEssay').value || '0')));
        document.getElementById('inputBobotPg').value = Math.round((100 - es) * 100) / 100;
        updateBobotSummary();
    }

    function updateBobotSummary() {
        const pg = parseFloat(document.getElementById('inputBobotPg').value || '0');
        const es = parseFloat(document.getElementById('inputBobotEssay').value || '0');
        const total = Math.round((pg + es) * 100) / 100;
        const summary = document.getElementById('bobotSummaryText');
        summary.textContent = `Nilai Akhir = (${pg}% x PG) + (${es}% x Essay) — Total ${total}%`;
        summary.style.color = Math.abs(total - 100) > 0.1 ? '#dc2626' : '#64748b';
    }

    document.addEventListener('DOMContentLoaded', syncBobotFields);
</script>
@endsection

