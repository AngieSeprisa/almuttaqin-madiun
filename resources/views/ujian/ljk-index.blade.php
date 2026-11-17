@extends('layouts.app')

@section('title', 'Cetak Lembar Jawab Komputer (LJK)')

@section('header-actions')
@if($ujian)
<div style="display:flex; align-items:center; gap:8px;">
    <a href="{{ route('ujian.cetak-ljk', $ujian->id) }}" target="_blank" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 16px; font-size:13px; border-radius:8px; font-weight:600;">
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
                    <div><strong>Jumlah Soal:</strong> {{ $ujian->jumlah_soal }} Butir ({{ $ujian->jumlah_opsi }} Opsi)</div>
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
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
                <div>
                    <h3 style="font-size:16px; font-weight:700; color:#1e293b; margin:0;">Daftar Siswa Kelas {{ $ujian->kelas?->nama_kelas ?? '-' }}</h3>
                    <p style="font-size:12.5px; color:#64748b; margin:2px 0 0;">
                        Setiap lembar A4 akan memuat identitas nama, NISN, dan QR Code unik siswa.
                    </p>
                </div>
                <a href="{{ route('ujian.cetak-ljk', $ujian->id) }}" target="_blank" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; font-size:13px; border-radius:8px; font-weight:600;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    <span>Cetak Seluruh Kelas ({{ $siswas->count() }} LJK)</span>
                </a>
            </div>

            <div style="overflow-x:auto; border:1px solid #e2e8f0; border-radius:10px;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0; text-align:left; color:#475569;">
                            <th style="padding:10px 14px; width:50px; text-align:center;">No</th>
                            <th style="padding:10px 14px;">Nama Lengkap Siswa</th>
                            <th style="padding:10px 14px; width:130px; text-align:center;">NISN</th>
                            <th style="padding:10px 14px; width:100px; text-align:center;">Kamar</th>
                            <th style="padding:10px 14px; width:110px; text-align:center;">Cetak Lembar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($siswas as $idx => $s)
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:10px 14px; text-align:center; color:#64748b; font-weight:600;">{{ $idx + 1 }}</td>
                            <td style="padding:10px 14px; font-weight:600; color:#1e293b;">
                                {{ $s->nama_lengkap }}
                            </td>
                            <td style="padding:10px 14px; text-align:center; font-family:monospace; color:#475569;">
                                {{ $s->nisn ?: '-' }}
                            </td>
                            <td style="padding:10px 14px; text-align:center; color:#64748b;">
                                {{ $s->kamar ?: '-' }}
                            </td>
                            <td style="padding:10px 14px; text-align:center;">
                                <a href="{{ route('ujian.cetak-ljk', ['ujian' => $ujian->id, 'siswa_id' => $s->id]) }}" target="_blank" class="btn btn-secondary" style="font-size:11.5px; padding:4px 8px; border-radius:6px;">
                                    Cetak 1 LJK
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">
                                Belum ada siswa aktif terdaftar pada kelas ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
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
</script>
@endsection

