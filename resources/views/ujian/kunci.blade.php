@extends('layouts.app')

@section('title', 'Kunci Jawaban: ' . $ujian->nama_ujian)

@section('header-actions')
<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    <button type="button" class="btn btn-secondary" onclick="openQuickPasteModal()" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px;">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
        </svg>
        <span>+ Tempel Kunci Cepat</span>
    </button>

    <a href="{{ route('ujian.index') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px;">
        &larr; Kembali
    </a>
</div>
@endsection

@section('content')
<style>
    .kunci-container {
        width: 100%;
    }
    .kunci-header-card {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .kunci-grid-wrapper {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .kunci-columns {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 20px;
    }
    .kunci-col-box {
        background: var(--bg-page, #f8fafc);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 10px;
        padding: 12px 14px;
    }
    .kunci-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 6px 4px;
        border-bottom: 1px dashed var(--border-light, #e2e8f0);
    }
    .kunci-row:last-child {
        border-bottom: none;
    }
    .kunci-no {
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        min-width: 28px;
    }
    .kunci-options {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .kunci-opt-btn {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 1.5px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.12s ease;
    }
    .kunci-opt-btn:hover {
        border-color: #2563eb;
        color: #2563eb;
    }
    .kunci-opt-btn.is-active {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
        box-shadow: 0 2px 4px rgba(37,99,235,0.25);
    }

    /* Modal Quick Paste */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,0.6);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
        backdrop-filter: blur(2px);
    }
    .modal-box {
        background: #ffffff;
        border-radius: 14px;
        max-width: 540px;
        width: 100%;
        padding: 24px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);
    }
</style>

<div class="kunci-container">
    {{-- INFO BAR --}}
    <div class="kunci-header-card">
        <div>
            <h1 style="font-size:16px; font-weight:700; margin:0 0 2px 0; color:#0f172a;">{{ $ujian->nama_ujian }}</h1>
            <div style="font-size:12.5px; color:#64748b;">
                {{ $ujian->mapel?->mata_pelajaran ?? '-' }} &bull; Kelas: {{ $ujian->kelas?->nama_kelas ?? '-' }} &bull; {{ $ujian->jumlah_soal }} Soal (Opsi: {{ $ujian->jumlah_opsi == 5 ? 'A-E' : 'A-D' }})
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:8px;">
            <button type="button" class="btn btn-secondary" onclick="setAllKeys('A')" style="font-size:11.5px; padding:6px 10px;">Set Semua A</button>
            <button type="button" class="btn btn-secondary" onclick="setAllKeys('B')" style="font-size:11.5px; padding:6px 10px;">Set B</button>
            <button type="button" class="btn btn-secondary" onclick="setAllKeys('C')" style="font-size:11.5px; padding:6px 10px;">Set C</button>
            <button type="button" class="btn btn-secondary" onclick="setAllKeys('D')" style="font-size:11.5px; padding:6px 10px;">Set D</button>
        </div>
    </div>

    {{-- INTERACTIVE GRID FORM --}}
    <form action="{{ route('ujian.update-kunci', $ujian->id) }}" method="POST" id="formKunci">
        @csrf
        @method('PUT')

        <div class="kunci-grid-wrapper">
            @php
                $numCols = $ujian->jumlah_soal <= 40 ? 4 : 5;
                $perCol = (int) ceil($ujian->jumlah_soal / $numCols);
                $opsiList = $ujian->jumlah_opsi == 5 ? ['A', 'B', 'C', 'D', 'E'] : ['A', 'B', 'C', 'D'];
            @endphp

            <div class="kunci-columns">
                @for($col = 0; $col < $numCols; $col++)
                <div class="kunci-col-box">
                    @for($row = 0; $row < $perCol; $row++)
                        @php
                            $no = ($col * $perCol) + $row + 1;
                        @endphp
                        @if($no <= $ujian->jumlah_soal)
                            @php
                                $currentKey = strtoupper($kuncis[$no]->kunci_jawaban ?? 'A');
                            @endphp
                            <div class="kunci-row" id="row_soal_{{ $no }}">
                                <span class="kunci-no">{{ str_pad($no, 2, '0', STR_PAD_LEFT) }}.</span>

                                <input type="hidden" name="kunci[{{ $no }}]" id="input_kunci_{{ $no }}" value="{{ $currentKey }}">

                                <div class="kunci-options">
                                    @foreach($opsiList as $opsi)
                                    <button type="button"
                                            class="kunci-opt-btn {{ $currentKey === $opsi ? 'is-active' : '' }}"
                                            data-no="{{ $no }}"
                                            data-opsi="{{ $opsi }}"
                                            onclick="selectKey({{ $no }}, '{{ $opsi }}')">
                                        {{ $opsi }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endfor
                </div>
                @endfor
            </div>

            <div style="margin-top:24px; padding-top:18px; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                <div style="font-size:12.5px; color:#64748b;">
                    Tips: Klik tombol huruf untuk memilih kunci jawaban.
                </div>
                <div style="display:flex; gap:10px;">
                    <a href="{{ route('ujian.index') }}" class="btn btn-secondary">Kembali</a>
                    <button type="submit" class="btn btn-primary" style="padding:9px 24px; font-weight:600;">
                        Simpan Kunci Jawaban
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- MODAL QUICK PASTE --}}
<div class="modal-overlay" id="modalQuickPaste" onclick="if(event.target === this) closeQuickPasteModal()">
    <div class="modal-box">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <h3 style="font-size:16px; font-weight:700; margin:0; color:#0f172a;">Tempel Kunci Jawaban Cepat</h3>
            <button type="button" onclick="closeQuickPasteModal()" style="border:none; background:none; font-size:20px; cursor:pointer; color:#64748b;">&times;</button>
        </div>

        <p style="font-size:12.5px; color:#64748b; margin:0 0 12px 0;">
            Salin dan tempel daftar kunci jawaban Anda di bawah ini. Format yang didukung:
            <br>&bull; Format teks per nomor: <code>1:A, 2:C, 3:B, 4:D...</code> atau <code>1.A, 2.B, 3.C</code>
            <br>&bull; Format baris baru: <code>1 A<br>2 C<br>3 B</code>
            <br>&bull; Format deret huruf langsung: <code>ACBDABCDABCD...</code> (panjang sesuai jumlah soal)
        </p>

        <textarea id="pasteTextarea" rows="6" class="form-input" style="width:100%; box-sizing:border-box; font-family:monospace; font-size:13px;" placeholder="Tempel teks kunci jawaban di sini..."></textarea>

        <div style="margin-top:16px; display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" class="btn btn-secondary" onclick="closeQuickPasteModal()">Batal</button>
            <button type="button" class="btn btn-primary" onclick="applyQuickPaste()">Terapkan ke Grid</button>
        </div>
    </div>
</div>

<script>
    function selectKey(no, opsi) {
        document.getElementById('input_kunci_' + no).value = opsi;
        const row = document.getElementById('row_soal_' + no);
        const btns = row.querySelectorAll('.kunci-opt-btn');
        btns.forEach(btn => {
            if (btn.getAttribute('data-opsi') === opsi) {
                btn.classList.add('is-active');
            } else {
                btn.classList.remove('is-active');
            }
        });
    }

    function setAllKeys(opsi) {
        const total = {{ $ujian->jumlah_soal }};
        for (let i = 1; i <= total; i++) {
            selectKey(i, opsi);
        }
    }

    function openQuickPasteModal() {
        document.getElementById('pasteTextarea').value = '';
        document.getElementById('modalQuickPaste').style.display = 'flex';
        document.getElementById('pasteTextarea').focus();
    }

    function closeQuickPasteModal() {
        document.getElementById('modalQuickPaste').style.display = 'none';
    }

    function applyQuickPaste() {
        const text = document.getElementById('pasteTextarea').value.trim();
        if (!text) {
            alert('Silakan tempel teks kunci jawaban terlebih dahulu.');
            return;
        }

        const totalSoal = {{ $ujian->jumlah_soal }};
        let mapped = {};

        // 1. Coba deteksi format "1:A" atau "1.A" atau "1 A" atau "1=A"
        const regexPair = /(\d+)[\s.:=\-)]+([A-Ea-e])/g;
        let match;
        let countPairs = 0;

        while ((match = regexPair.exec(text)) !== null) {
            const no = parseInt(match[1]);
            const ans = match[2].toUpperCase();
            if (no >= 1 && no <= totalSoal) {
                mapped[no] = ans;
                countPairs++;
            }
        }

        // 2. Jika tidak cocok format pair, cek apakah deret huruf murni (misal: "ACBDABCD...")
        if (countPairs < 3) {
            const cleanLetters = text.replace(/[^A-Ea-e]/g, '').toUpperCase();
            if (cleanLetters.length >= 5) {
                for (let i = 0; i < cleanLetters.length && i < totalSoal; i++) {
                    mapped[i + 1] = cleanLetters[i];
                }
            }
        }

        const keysFound = Object.keys(mapped).length;
        if (keysFound === 0) {
            alert('Format teks tidak dikenali. Pastikan menyertakan nomor dan pilihan jawaban A-E.');
            return;
        }

        for (const [no, ans] of Object.entries(mapped)) {
            selectKey(no, ans);
        }

        closeQuickPasteModal();
        alert(`Berhasil menerapkan ${keysFound} kunci jawaban ke grid.`);
    }
</script>
@endsection

