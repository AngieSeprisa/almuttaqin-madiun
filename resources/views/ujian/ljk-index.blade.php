@extends('layouts.app')

@section('title', 'Cetak Lembar Jawab Komputer (LJK)')

@section('header-actions')
@if($ujian)
<div style="display:flex; align-items:center; gap:8px;">
    <a href="{{ route('ujian.cetak-ljk', $ujian->id) }}" target="_blank" class="btn btn-primary ljk-print-link" data-base-href="{{ route('ujian.cetak-ljk', $ujian->id) }}" style="display:inline-flex; align-items:center; gap:6px; padding:7px 16px; font-size:13px; border-radius:8px; font-weight:600;">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        <span>Cetak LJK Kelas (A4)</span>
    </a>
</div>
@endif
@endsection

@section('content')
<style>
    .ljk-layout-grid {
        display: grid;
        grid-template-columns: 360px 1fr;
        gap: 20px;
    }
    @media (max-width: 900px) {
        .ljk-layout-grid {
            grid-template-columns: 1fr;
        }
    }
    .panel-card {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .peraturan-textarea {
        width: 100%;
        min-height: 140px;
        font-family: inherit;
        font-size: 13px;
        line-height: 1.6;
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #f8fafc;
        resize: vertical;
    }
    .peraturan-textarea:focus {
        outline: none;
        background: #fff;
        border-color: #0052FF;
    }
</style>

<div>
    @if(session('success'))
        <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:14px;">
            {{ session('success') }}
        </div>
    @endif

    <div class="ljk-layout-grid">
        <!-- Kolom Kiri: Pemilihan Ujian & Pengaturan Peraturan Ujian -->
        <div>
            <!-- Pilih Ujian -->
            <div class="panel-card" style="margin-bottom:20px;">
                <h3 style="font-size:15px; font-weight:700; color:#1e293b; margin:0 0 12px;">Pilih Ujian</h3>
                <form method="GET" action="{{ route('ujian.ljk') }}">
                    <select name="ujian_id" onchange="this.form.submit()" style="width:100%; padding:9px 12px; font-size:13px; border:1px solid #cbd5e1; border-radius:8px; font-weight:600; color:#1e293b;">
                        @forelse($ujians as $u)
                            <option value="{{ $u->id }}" {{ $ujian && $ujian->id === $u->id ? 'selected' : '' }}>
                                {{ $u->nama_ujian }} ({{ $u->kelas?->nama_kelas ?? 'Kelas -' }})
                            </option>
                        @empty
                            <option value="">Belum ada data ujian</option>
                        @endforelse
                    </select>
                </form>

                @if($ujian)
                <div style="margin-top:14px; font-size:12.5px; color:#64748b; line-height:1.7; border-top:1px solid #f1f5f9; padding-top:12px;">
                    <div><strong>Kode Ujian:</strong> <span style="font-family:monospace; color:#0052FF;">{{ $ujian->kode_ujian }}</span></div>
                    <div><strong>Mata Pelajaran:</strong> {{ $ujian->mapel?->mata_pelajaran ?? '-' }}</div>
                    <div><strong>Kelas:</strong> {{ $ujian->kelas?->nama_kelas ?? '-' }}</div>
                    <div><strong>Guru Pengampu:</strong> {{ $ujian->guru?->name ?? '-' }}</div>
                    <div><strong>Jumlah Soal:</strong> {{ $ujian->ringkasan_soal }}@if($ujian->hasPg()) ({{ $ujian->jumlah_opsi }} Opsi)@endif</div>
                    @if($ujian->hasEssay())
                        @php $w = $ujian->getEffectiveWeights(); @endphp
                        <div><strong>Bobot Nilai:</strong> PG {{ $w['pg'] }}% &bull; Essay {{ $w['essay'] }}%</div>
                    @endif
                    <div><strong>Total Siswa:</strong> {{ $siswas->count() }} Santri/Siswa</div>
                </div>
                @endif
            </div>

            <!-- Pengaturan Peraturan Ujian (Template) -->
            @if($ujian)
            <div class="panel-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <h3 style="font-size:15px; font-weight:700; color:#1e293b; margin:0;">Tata Tertib & Peraturan LJK</h3>
                </div>
                <p style="font-size:12px; color:#64748b; margin:0 0 10px;">
                    Teks ini tercetak langsung pada kotak petunjuk di setiap lembar LJK siswa.
                </p>

                <form method="POST" action="{{ route('ujian.update-peraturan', $ujian->id) }}">
                    @csrf
                    <textarea name="peraturan_ujian" class="peraturan-textarea">{{ $ujian->peraturan_efektif }}</textarea>
                    
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
                        <button type="button" class="btn btn-secondary" onclick="resetDefaultPeraturan()" style="font-size:12px; padding:6px 10px;">
                            Pakai Template Bawaan
                        </button>
                        <button type="submit" class="btn btn-primary" style="font-size:12px; padding:6px 14px;">
                            Simpan Peraturan
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>

        <!-- Kolom Kanan: Pratinjau Daftar Siswa yang Siap Dicetak LJK -->
        <div class="panel-card">
            @if($ujian)
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
                <div>
                    <h3 style="font-size:16px; font-weight:700; color:#1e293b; margin:0;">Daftar Siswa Kelas {{ $ujian->kelas?->nama_kelas ?? '-' }}</h3>
                    <p style="font-size:12.5px; color:#64748b; margin:2px 0 0;">
                        Pilih jenis kode identitas (QR Code atau Barcode Garis) yang ingin dicetak pada lembar LJK.
                    </p>
                </div>
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <div style="display:inline-flex; align-items:center; background:#f1f5f9; padding:3px; border-radius:8px; border:1px solid #cbd5e1;">
                        <button type="button" id="btnModeQr" onclick="setLjkCodeType('qr')" style="border:none; background:#0052FF; color:#ffffff; font-size:12px; font-weight:700; padding:6px 12px; border-radius:6px; cursor:pointer; transition:all 0.15s;">
                            QR Code
                        </button>
                        <button type="button" id="btnModeBarcode" onclick="setLjkCodeType('barcode')" style="border:none; background:transparent; color:#475569; font-size:12px; font-weight:700; padding:6px 12px; border-radius:6px; cursor:pointer; transition:all 0.15s;">
                            Barcode Garis
                        </button>
                    </div>

                    <a href="{{ route('ujian.cetak-ljk', $ujian->id) }}" target="_blank" class="btn btn-primary ljk-print-link" data-base-href="{{ route('ujian.cetak-ljk', $ujian->id) }}" style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; font-size:13px; border-radius:8px; font-weight:600;">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        <span>Cetak Seluruh Kelas ({{ $siswas->count() }} LJK)</span>
                    </a>
                </div>
            </div>

            <div class="table-card" style="margin-top:14px;">
                <div class="table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th style="width:50px; text-align:center;">No</th>
                                <th>Nama Lengkap Siswa</th>
                                <th style="width:140px; text-align:center;">NISN</th>
                                <th style="width:110px; text-align:center;">Kamar</th>
                                <th style="width:120px; text-align:center;">Cetak Lembar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($siswas as $idx => $s)
                            <tr>
                                <td style="text-align:center; color:var(--text-muted); font-weight:600;">{{ $idx + 1 }}</td>
                                <td class="cell-nama">
                                    <div style="font-weight:700; color:var(--text-primary); font-size:13.5px;">{{ $s->nama_lengkap }}</div>
                                </td>
                                <td style="text-align:center; font-family:monospace; font-weight:600; color:var(--text-secondary);">
                                    {{ $s->nisn ?: '-' }}
                                </td>
                                <td style="text-align:center; color:var(--text-secondary);">
                                    {{ $s->kamar ?: '-' }}
                                </td>
                                <td style="text-align:center;">
                                    <div class="action-btn-group">
                                        <a href="{{ route('ujian.cetak-ljk', ['ujian' => $ujian->id, 'siswa_id' => $s->id]) }}" target="_blank" class="action-btn action-btn-print ljk-print-link" data-base-href="{{ route('ujian.cetak-ljk', ['ujian' => $ujian->id, 'siswa_id' => $s->id]) }}" title="Cetak 1 Lembar LJK ({{ $s->nama_lengkap }})" aria-label="Cetak 1 LJK {{ $s->nama_lengkap }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                                <rect x="6" y="14" width="12" height="8"></rect>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="cell-empty">
                                    Belum ada siswa aktif terdaftar pada kelas ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div style="text-align:center; padding:50px; color:#94a3b8;">
                Silakan pilih ujian terlebih dahulu pada panel sebelah kiri.
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    const defaultTemplateText = `1. Periksa identitas yang tercetak pada lembar jawab sebelum mulai mengerjakan.
2. Hitamkan bulatan pilihan jawaban secara penuh menggunakan pensil 2B atau pulpen bertinta hitam pekat.
3. Dilarang melipat, meremas, atau mengotori 4 kotak hitam pekat di setiap sudut lembar kertas.
4. Soal ujian ditampilkan berurutan di layar proyektor kelas dengan batas waktu otomatis per nomor.
5. Dilarang berbicara, berbuat curang, atau bekerja sama selama sesi ujian berlangsung.`;

    function resetDefaultPeraturan() {
        if (confirm('Gunakan teks peraturan ujian template bawaan?')) {
            document.querySelector('textarea[name="peraturan_ujian"]').value = defaultTemplateText;
        }
    }

    function setLjkCodeType(type) {
        const btnQr = document.getElementById('btnModeQr');
        const btnBc = document.getElementById('btnModeBarcode');
        if (btnQr && btnBc) {
            if (type === 'barcode') {
                btnBc.style.background = '#0052FF';
                btnBc.style.color = '#ffffff';
                btnQr.style.background = 'transparent';
                btnQr.style.color = '#475569';
            } else {
                btnQr.style.background = '#0052FF';
                btnQr.style.color = '#ffffff';
                btnBc.style.background = 'transparent';
                btnBc.style.color = '#475569';
            }
        }

        document.querySelectorAll('.ljk-print-link').forEach(link => {
            const base = link.getAttribute('data-base-href') || link.href;
            const sep = base.includes('?') ? '&' : '?';
            link.href = base + sep + 'tipe_kode=' + encodeURIComponent(type);
        });
    }
</script>
@endsection

