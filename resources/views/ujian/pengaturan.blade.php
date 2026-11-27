@extends('layouts.app')

@section('title', 'Pengaturan Ujian')

@section('content')
<style>
    .ujian-tabs-bar {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 12px;
        padding: 5px;
        width: fit-content;
    }
    .ujian-tab-link {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-secondary, #475569);
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .ujian-tab-link:hover {
        color: var(--text-primary, #0f172a);
        background: var(--bg-page, #f8fafc);
    }
    .ujian-tab-link.active {
        background: var(--electric-blue, #0052FF);
        color: #ffffff;
        box-shadow: 0 2px 6px rgba(0, 82, 255, 0.25);
    }
    .settings-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }
    .settings-card {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .settings-card-head {
        padding: 16px 22px;
        border-bottom: 1px solid var(--border-light, #e2e8f0);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .settings-card-title {
        font-size: 15.5px;
        font-weight: 700;
        color: var(--text-primary, #0f172a);
        margin: 0;
    }
    .settings-card-body {
        padding: 20px 22px;
    }
    .sesi-inline-form {
        display: grid;
        grid-template-columns: 1.5fr 1fr 1fr 0.7fr auto;
        gap: 12px;
        align-items: end;
    }
    .meja-form-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
        gap: 12px;
        align-items: end;
    }
    @media (max-width: 960px) {
        .sesi-inline-form,
        .meja-form-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    @media (max-width: 600px) {
        .sesi-inline-form,
        .meja-form-grid {
            grid-template-columns: 1fr;
        }
    }
    .s-field label {
        display: block;
        font-size: 12.5px;
        font-weight: 600;
        color: var(--text-secondary, #334155);
        margin-bottom: 5px;
    }
    .s-input, .s-select {
        width: 100%;
        padding: 8px 12px;
        font-size: 13.5px;
        border: 1px solid var(--border-light, #cbd5e1);
        border-radius: 8px;
        background: var(--bg-surface, #ffffff);
        color: var(--text-primary, #0f172a);
        box-sizing: border-box;
    }
    .s-input:focus, .s-select:focus {
        outline: none;
        border-color: var(--electric-blue, #0052FF);
        box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.12);
    }
</style>

<div style="display:flex; flex-direction:column; gap:18px; width:100%; max-width:100%; min-width:0;">

    {{-- Tab Navigasi Soal Ujian & Pengaturan --}}
    <div>
        <div class="ujian-tabs-bar">
            <a href="{{ route('ujian.soal') }}" class="ujian-tab-link">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <span>Soal Ujian</span>
            </a>
            <a href="{{ route('ujian.pengaturan') }}" class="ujian-tab-link active">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                <span>Pengaturan</span>
            </a>
        </div>
    </div>

    {{-- Feedback Alerts --}}
    @if(session('success'))
    <div class="alert-banner alert-success" style="display:flex; align-items:center; gap:10px; padding:12px 18px; border-radius:12px; background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.25); color:#065F46;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;flex-shrink:0; color:#10B981;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div style="font-weight:600; font-size:13.5px;">{{ session('success') }}</div>
    </div>
    @endif

    @if($errors->any())
    <div class="alert-banner alert-danger" style="display:flex; align-items:flex-start; gap:10px; padding:12px 18px; border-radius:12px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;flex-shrink:0; margin-top:2px;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div style="font-weight:600; font-size:13.5px;">
            @foreach($errors->all() as $err)
                <div>{{ $err }}</div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="settings-grid">
        {{-- BAGIAN 1: PENGATURAN JAM SESI UJIAN --}}
        <div class="settings-card">
            <div class="settings-card-head">
                <h2 class="settings-card-title">Pengaturan Jam Sesi Ujian</h2>
                <a href="{{ route('ujian.sesi') }}" class="btn btn-secondary" style="font-size:12.5px; padding:6px 12px; border-radius:8px; text-decoration:none;">
                    Buka Sesi Ujian &rarr;
                </a>
            </div>
            <div class="settings-card-body">
                <form action="{{ route('ujian.sesi-jam.store') }}" method="POST" class="sesi-inline-form" style="margin-bottom:20px;">
                    @csrf
                    <div class="s-field">
                        <label for="namaSesiInput">Nama Sesi</label>
                        <input type="text" id="namaSesiInput" name="nama_sesi" class="s-input" value="{{ old('nama_sesi') }}" placeholder="Contoh: Sesi 1" required>
                    </div>
                    <div class="s-field">
                        <label for="jamMulaiInput">Jam Mulai</label>
                        <input type="time" id="jamMulaiInput" name="jam_mulai" class="s-input" value="{{ old('jam_mulai', '07:30') }}" required>
                    </div>
                    <div class="s-field">
                        <label for="jamSelesaiInput">Jam Selesai</label>
                        <input type="time" id="jamSelesaiInput" name="jam_selesai" class="s-input" value="{{ old('jam_selesai', '09:00') }}" required>
                    </div>
                    <div class="s-field">
                        <label for="urutanInput">Urutan</label>
                        <input type="number" id="urutanInput" name="urutan" min="1" max="50" class="s-input" value="{{ old('urutan', $nextUrutan) }}">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="padding:9px 18px; font-size:13px; font-weight:600; border-radius:8px; width:100%;">
                            Tambah Sesi
                        </button>
                    </div>
                </form>

                <div style="border:1px solid var(--border-light, #e2e8f0); border-radius:10px; overflow-x:auto;">
                    <table class="modern-table" style="margin:0;">
                        <thead>
                            <tr>
                                <th style="width:56px; text-align:center;">No</th>
                                <th>Nama Sesi</th>
                                <th style="width:130px; text-align:center;">Jam Mulai</th>
                                <th style="width:130px; text-align:center;">Jam Selesai</th>
                                <th style="width:110px; text-align:center;">Status</th>
                                <th style="width:120px; text-align:center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sesiJams as $idx => $sj)
                            <tr>
                                <td style="text-align:center; color:var(--text-muted); font-weight:600;">{{ $idx + 1 }}</td>
                                <td style="font-weight:700; color:var(--text-primary);">{{ $sj->nama_sesi }}</td>
                                <td style="text-align:center; font-family:monospace; font-weight:700; color:#1d4ed8;">{{ $sj->jam_mulai_formatted }}</td>
                                <td style="text-align:center; font-family:monospace; font-weight:700; color:#1d4ed8;">{{ $sj->jam_selesai_formatted }}</td>
                                <td style="text-align:center;">
                                    @if($sj->is_active)
                                        <span class="status-pill status-sudah_kembali" style="font-size:11px; padding:3px 9px;">Aktif</span>
                                    @else
                                        <span class="status-pill status-melewati_batas" style="font-size:11px; padding:3px 9px;">Nonaktif</span>
                                    @endif
                                </td>
                                <td style="text-align:center;">
                                    <div class="action-btn-group">
                                        <button type="button"
                                                class="action-btn action-btn-edit"
                                                data-update-url="{{ route('ujian.sesi-jam.update', $sj->id) }}"
                                                data-nama="{{ $sj->nama_sesi }}"
                                                data-mulai="{{ $sj->jam_mulai_formatted }}"
                                                data-selesai="{{ $sj->jam_selesai_formatted }}"
                                                data-urutan="{{ $sj->urutan }}"
                                                data-active="{{ $sj->is_active ? '1' : '0' }}"
                                                onclick="openEditSesiModal(this)"
                                                title="Ubah Jam Sesi">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                        <form action="{{ route('ujian.sesi-jam.destroy', $sj->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus jam sesi {{ addslashes($sj->nama_sesi) }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn action-btn-delete" title="Hapus Jam Sesi">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="cell-empty">
                                    Belum ada jam sesi ujian yang diatur. Tambahkan jam sesi di formulir atas agar muncul di halaman Sesi Ujian.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- BAGIAN 2: CETAK NOMOR MEJA UJIAN PER KELAS --}}
        <div class="settings-card">
            <div class="settings-card-head">
                <h2 class="settings-card-title">Cetak Nomor Meja Ujian per Kelas</h2>
            </div>
            <div class="settings-card-body">
                <form action="{{ route('ujian.cetak-nomor-meja') }}" method="GET" target="_blank" class="meja-form-grid" style="margin-bottom:20px;">
                    <div class="s-field">
                        <label for="mejaKelasSelect">Pilih Kelas</label>
                        <select id="mejaKelasSelect" name="kelas_id" class="s-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            @foreach($kelases as $k)
                                <option value="{{ $k->id }}">
                                    {{ $k->nama_kelas }} — {{ $siswaCountsByKelas[$k->id] ?? 0 }} Siswa
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="s-field">
                        <label for="mejaJudulInput">Nama Kegiatan Ujian</label>
                        <input type="text" id="mejaJudulInput" name="judul_ujian" class="s-input" placeholder="Contoh: PAS Ganjil 2026/2027" value="NOMOR MEJA UJIAN">
                    </div>
                    <div class="s-field">
                        <label for="mejaRuangInput">Nama Ruang</label>
                        <input type="text" id="mejaRuangInput" name="ruang" class="s-input" placeholder="Otomatis sesuai kelas">
                    </div>
                    <div class="s-field">
                        <label for="mejaAwalanInput">Awalan Nomor</label>
                        <input type="text" id="mejaAwalanInput" name="awalan" class="s-input" placeholder="Opsional, mis. 7A-">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="padding:9px 18px; font-size:13px; font-weight:600; border-radius:8px; width:100%; display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                <rect x="6" y="14" width="12" height="8"></rect>
                            </svg>
                            <span>Cetak</span>
                        </button>
                    </div>
                </form>

                <div style="border:1px solid var(--border-light, #e2e8f0); border-radius:10px; overflow-x:auto;">
                    <table class="modern-table" style="margin:0;">
                        <thead>
                            <tr>
                                <th style="width:56px; text-align:center;">No</th>
                                <th>Kelas</th>
                                <th style="width:140px; text-align:center;">Jenjang</th>
                                <th style="width:140px; text-align:center;">Jumlah Siswa</th>
                                <th style="width:170px; text-align:center;">Cetak Nomor Meja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kelases as $idx => $k)
                            <tr>
                                <td style="text-align:center; color:var(--text-muted); font-weight:600;">{{ $idx + 1 }}</td>
                                <td style="font-weight:700; color:var(--text-primary);">{{ $k->nama_kelas }}</td>
                                <td style="text-align:center; color:var(--text-secondary); font-weight:600;">{{ $k->jenjang ?: '-' }}</td>
                                <td style="text-align:center; font-weight:700; color:#0052FF;">{{ $siswaCountsByKelas[$k->id] ?? 0 }} Siswa</td>
                                <td style="text-align:center;">
                                    <a href="{{ route('ujian.cetak-nomor-meja', ['kelas_id' => $k->id]) }}" target="_blank" class="btn btn-sm btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; font-size:12px; font-weight:600; text-decoration:none; border-radius:8px;">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                            <rect x="6" y="14" width="12" height="8"></rect>
                                        </svg>
                                        <span>Cetak Meja</span>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="cell-empty">Belum ada data kelas aktif.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Edit Jam Sesi Ujian --}}
<div id="editSesiModal" style="position:fixed; inset:0; background:rgba(15, 23, 42, 0.55); display:none; align-items:center; justify-content:center; z-index:1050; padding:20px;">
    <div style="background:#ffffff; border-radius:14px; width:100%; max-width:460px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.12); overflow:hidden;">
        <form method="POST" id="formEditSesiJam">
            @csrf
            @method('PUT')
            <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:15.5px; font-weight:700; color:#1e293b;">Ubah Jam Sesi Ujian</h3>
                <button type="button" onclick="closeEditSesiModal()" style="border:none; background:none; font-size:20px; cursor:pointer; color:#94a3b8;">&times;</button>
            </div>
            <div style="padding:18px 20px; display:flex; flex-direction:column; gap:12px;">
                <div class="s-field">
                    <label for="editNamaSesi">Nama Sesi</label>
                    <input type="text" id="editNamaSesi" name="nama_sesi" class="s-input" required>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="s-field">
                        <label for="editJamMulai">Jam Mulai</label>
                        <input type="time" id="editJamMulai" name="jam_mulai" class="s-input" required>
                    </div>
                    <div class="s-field">
                        <label for="editJamSelesai">Jam Selesai</label>
                        <input type="time" id="editJamSelesai" name="jam_selesai" class="s-input" required>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div class="s-field">
                        <label for="editUrutan">Urutan</label>
                        <input type="number" id="editUrutan" name="urutan" min="1" max="50" class="s-input" required>
                    </div>
                    <div class="s-field">
                        <label for="editIsActive">Status</label>
                        <select id="editIsActive" name="is_active" class="s-select">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>
            <div style="padding:14px 20px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn btn-secondary" onclick="closeEditSesiModal()" style="padding:7px 14px; font-size:13px; border-radius:8px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding:7px 16px; font-size:13px; border-radius:8px;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditSesiModal(btn) {
        document.getElementById('formEditSesiJam').action = btn.getAttribute('data-update-url');
        document.getElementById('editNamaSesi').value = btn.getAttribute('data-nama') || '';
        document.getElementById('editJamMulai').value = btn.getAttribute('data-mulai') || '07:30';
        document.getElementById('editJamSelesai').value = btn.getAttribute('data-selesai') || '09:00';
        document.getElementById('editUrutan').value = btn.getAttribute('data-urutan') || 1;
        document.getElementById('editIsActive').value = btn.getAttribute('data-active') || '1';
        document.getElementById('editSesiModal').style.display = 'flex';
    }
    function closeEditSesiModal() {
        document.getElementById('editSesiModal').style.display = 'none';
    }
</script>
@endsection
