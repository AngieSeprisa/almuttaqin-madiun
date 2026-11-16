@extends('layouts.app')

@section('title', 'Rekap Hasil Ujian: ' . $ujian->nama_ujian)

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
    <a href="{{ route('ujian.export-excel', $ujian->id) }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px; background:#ecfdf5; border-color:#a7f3d0; color:#065f46;">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        <span>Ekspor Excel (.xlsx)</span>
    </a>

    <a href="{{ route('ujian.upload-scan', $ujian->id) }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px;">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
        </svg>
        <span>Unggah Hasil Scan</span>
    </a>

    <a href="{{ route('ujian.cetak-ljk', $ujian->id) }}" target="_blank" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px;">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        <span>Cetak LJK</span>
    </a>

    <a href="{{ route('ujian.kunci', $ujian->id) }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px;">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path>
        </svg>
        <span>Kunci Jawaban</span>
    </a>

    <a href="{{ route('ujian.index') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:7px 14px; font-size:13px; border-radius:8px;">
        &larr; Kembali
    </a>
</div>
@endsection

@section('content')
<style>
    .hasil-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .stat-val {
        font-size: 22px;
        font-weight: 800;
        line-height: 1.1;
        color: #1e293b;
    }
    .stat-lbl {
        font-size: 12px;
        color: #64748b;
        margin-top: 3px;
    }
    .table-container {
        background: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-light, #e2e8f0);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .hasil-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .hasil-table th {
        background: #f8fafc;
        padding: 12px 14px;
        text-align: left;
        font-weight: 600;
        color: #475569;
        border-bottom: 1px solid #e2e8f0;
    }
    .hasil-table td {
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .hasil-table tr:hover {
        background: #fafafa;
    }

    /* Modal Koreksi */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.55);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1050;
        padding: 20px;
    }
    .modal-card {
        background: #ffffff;
        border-radius: 14px;
        width: 100%;
        max-width: 840px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
    }
    .modal-head {
        padding: 18px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .modal-body {
        padding: 20px 24px;
        overflow-y: auto;
    }
    .modal-foot {
        padding: 14px 24px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        background: #f8fafc;
        border-bottom-left-radius: 14px;
        border-bottom-right-radius: 14px;
    }
    .modal-answers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 10px;
    }
    .answer-edit-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px 10px;
        font-size: 12px;
    }
    .answer-edit-box.is-wrong {
        border-color: #fecaca;
        background: #fff5f5;
    }
    .answer-edit-box.is-ganda {
        border-color: #fde68a;
        background: #fffbeb;
    }
</style>

<div class="hasil-container">
    @if(session('success'))
        <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:14px;">
            {{ session('success') }}
        </div>
    @endif

    <!-- Kartu Statistik -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff; color:#0052FF;">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div>
                <div class="stat-val">{{ $statistik['total_peserta'] }}</div>
                <div class="stat-lbl">Total Siswa Terkoreksi</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#ecfdf5; color:#059669;">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"></polyline>
                    <polyline points="16 7 22 7 22 13"></polyline>
                </svg>
            </div>
            <div>
                <div class="stat-val">{{ $statistik['nilai_tertinggi'] }}</div>
                <div class="stat-lbl">Nilai Tertinggi</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <div>
                <div class="stat-val">{{ $statistik['nilai_rata_rata'] }}</div>
                <div class="stat-lbl">Rata-rata Nilai</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#fffbeb; color:#d97706;">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>
            <div>
                <div class="stat-val" style="color: {{ $statistik['perlu_cek'] > 0 ? '#d97706' : '#1e293b' }};">
                    {{ $statistik['perlu_cek'] }}
                </div>
                <div class="stat-lbl">Perlu Cek Manual</div>
            </div>
        </div>
    </div>

    <!-- Tabel Rekap Nilai -->
    <div class="table-container">
        <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <div>
                <h3 style="font-size:16px; font-weight:700; margin:0; color:#1e293b;">Daftar Nilai Siswa</h3>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">
                    Kelas {{ $ujian->kelas?->nama_kelas ?? 'Semua Kelas' }} &bull; {{ $ujian->mapel?->mata_pelajaran ?? '-' }} &bull; Total Soal: {{ $ujian->jumlah_soal }}
                </div>
            </div>
            <div style="font-size:13px; color:#64748b;">
                Menampilkan {{ $hasils->count() }} dari {{ $hasils->total() }} hasil
            </div>
        </div>

        <table class="hasil-table">
            <thead>
                <tr>
                    <th style="width:50px; text-align:center;">No</th>
                    <th>Nama Siswa</th>
                    <th style="width:110px; text-align:center;">NISN</th>
                    <th style="width:80px; text-align:center;">Benar</th>
                    <th style="width:80px; text-align:center;">Salah</th>
                    <th style="width:80px; text-align:center;">Kosong</th>
                    <th style="width:90px; text-align:center;">Nilai</th>
                    <th style="width:130px;">Status Scan</th>
                    <th>Catatan</th>
                    <th style="width:110px; text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hasils as $idx => $h)
                    <tr id="row-hasil-{{ $h->id }}">
                        <td style="text-align:center; color:#64748b; font-weight:600;">
                            {{ $hasils->firstItem() + $idx }}
                        </td>
                        <td>
                            <div style="font-weight:600; color:#1e293b;">{{ $h->siswa?->nama_lengkap ?? 'Siswa #' . $h->siswa_id }}</div>
                            <div style="font-size:11px; color:#94a3b8;">Kamar: {{ $h->siswa?->kamar ?? '-' }}</div>
                        </td>
                        <td style="text-align:center; font-family:monospace; color:#475569;">
                            {{ $h->siswa?->nisn ?? '-' }}
                        </td>
                        <td style="text-align:center; font-weight:700; color:#059669;" id="cell-benar-{{ $h->id }}">
                            {{ $h->jumlah_benar }}
                        </td>
                        <td style="text-align:center; font-weight:700; color:#dc2626;" id="cell-salah-{{ $h->id }}">
                            {{ $h->jumlah_salah }}
                        </td>
                        <td style="text-align:center; font-weight:600; color:#64748b;" id="cell-kosong-{{ $h->id }}">
                            {{ $h->jumlah_kosong }}
                        </td>
                        <td style="text-align:center; font-weight:800; font-size:15px; color:#0052FF;" id="cell-nilai-{{ $h->id }}">
                            {{ number_format($h->nilai_akhir, 2) }}
                        </td>
                        <td id="cell-status-{{ $h->id }}">
                            @if($h->status_scan === 'valid')
                                <span class="badge" style="background:#ecfdf5; color:#059669; font-weight:600; padding:3px 8px; border-radius:6px; font-size:11px;">Valid</span>
                            @else
                                <span class="badge" style="background:#fffbeb; color:#d97706; font-weight:600; padding:3px 8px; border-radius:6px; font-size:11px;">Perlu Cek</span>
                            @endif
                        </td>
                        <td style="font-size:12px; color:#64748b;" id="cell-catatan-{{ $h->id }}">
                            {{ $h->catatan_scan ?? '-' }}
                        </td>
                        <td style="text-align:center;">
                            <button type="button" class="btn btn-secondary" onclick="openReviewModal({{ $h->id }}, '{{ addslashes($h->siswa?->nama_lengkap ?? 'Siswa') }}', {{ json_encode($h->jawaban_siswa ?? []) }})" style="padding:5px 10px; font-size:12px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">
                                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                <span>Koreksi</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align:center; padding:36px; color:#94a3b8;">
                            Belum ada lembar jawab yang dikoreksi untuk ujian ini. Silakan klik tombol <strong>Unggah Hasil Scan</strong> di atas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($hasils->hasPages())
            <div style="padding:14px 20px; border-top:1px solid #e2e8f0;">
                {{ $hasils->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Koreksi Manual -->
<div class="modal-overlay" id="reviewModal">
    <div class="modal-card">
        <div class="modal-head">
            <div>
                <h3 style="font-size:16px; font-weight:700; margin:0; color:#1e293b;" id="modalSiswaNama">Koreksi Lembar Siswa</h3>
                <div style="font-size:12px; color:#64748b; margin-top:2px;">
                    Ubah jawaban nomor tertentu jika terjadi arsiran ganda atau goresan samar pada LJK.
                </div>
            </div>
            <button type="button" onclick="closeReviewModal()" style="border:none; background:transparent; cursor:pointer; color:#94a3b8; font-size:20px; line-height:1;">
                &times;
            </button>
        </div>

        <div class="modal-body">
            <div id="modalAlertMsg" style="display:none; padding:10px 14px; border-radius:8px; margin-bottom:14px; font-size:12px;"></div>
            
            <div class="modal-answers-grid" id="modalAnswersGrid">
                <!-- Dinamis per nomor soal -->
            </div>
        </div>

        <div class="modal-foot">
            <button type="button" class="btn btn-secondary" onclick="closeReviewModal()" style="padding:7px 16px; font-size:13px; border-radius:8px;">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    const kuncis = @json($kuncis);
    const jumlahSoal = {{ $ujian->jumlah_soal }};
    const jumlahOpsi = {{ $ujian->jumlah_opsi }};
    const opsiLetters = ['A', 'B', 'C', 'D', 'E'].slice(0, jumlahOpsi);
    let activeHasilId = null;

    function openReviewModal(hasilId, namaSiswa, jawabanMap) {
        activeHasilId = hasilId;
        document.getElementById('modalSiswaNama').textContent = 'Koreksi Lembar: ' + namaSiswa;
        document.getElementById('modalAlertMsg').style.display = 'none';

        const grid = document.getElementById('modalAnswersGrid');
        grid.innerHTML = '';

        for (let no = 1; no <= jumlahSoal; no++) {
            const jawab = jawabanMap ? (jawabanMap[no] || null) : null;
            const kunci = kuncis[no] || '-';
            const isCorrect = jawab && kunci && jawab.toUpperCase() === kunci.toUpperCase();
            const isGanda = jawab === 'GANDA';
            const isWrong = !isCorrect && jawab !== null;

            let cardClass = 'answer-edit-box';
            if (isGanda) cardClass += ' is-ganda';
            else if (isWrong) cardClass += ' is-wrong';

            const box = document.createElement('div');
            box.className = cardClass;
            box.id = `box-soal-${no}`;

            let selectOptions = `<option value="null" ${jawab === null ? 'selected' : ''}>[Kosong]</option>`;
            opsiLetters.forEach(opt => {
                selectOptions += `<option value="${opt}" ${jawab === opt ? 'selected' : ''}>${opt}</option>`;
            });
            selectOptions += `<option value="GANDA" ${jawab === 'GANDA' ? 'selected' : ''}>[GANDA]</option>`;

            box.innerHTML = `
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                    <strong style="color:#1e293b;">#${no}</strong>
                    <span style="font-size:11px; color:#64748b;">Kunci: <strong style="color:#0052FF;">${kunci}</strong></span>
                </div>
                <div style="display:flex; align-items:center; gap:6px;">
                    <select onchange="saveAnswerChange(${no}, this.value)" style="width:100%; padding:4px 6px; font-size:12px; border-radius:6px; border:1px solid #cbd5e1; background:#fff; font-weight:600; color:#1e293b;">
                        ${selectOptions}
                    </select>
                </div>
            `;
            grid.appendChild(box);
        }

        document.getElementById('reviewModal').style.display = 'flex';
    }

    function closeReviewModal() {
        document.getElementById('reviewModal').style.display = 'none';
        activeHasilId = null;
    }

    function saveAnswerChange(nomorSoal, nilaiJawaban) {
        if (!activeHasilId) return;

        const alertBox = document.getElementById('modalAlertMsg');
        alertBox.style.display = 'block';
        alertBox.style.background = '#eff6ff';
        alertBox.style.color = '#1d4ed8';
        alertBox.style.border = '1px solid #bfdbfe';
        alertBox.textContent = `Menyimpan perubahan nomor ${nomorSoal}...`;

        const url = `/ujian/{{ $ujian->id }}/hasil/${activeHasilId}/manual`;

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                nomor_soal: nomorSoal,
                jawaban: nilaiJawaban
            })
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alertBox.style.background = '#ecfdf5';
                alertBox.style.color = '#065f46';
                alertBox.style.border = '1px solid #a7f3d0';
                alertBox.textContent = res.message;

                // Update baris tabel siswa
                const d = res.data;
                const cellBenar = document.getElementById(`cell-benar-${activeHasilId}`);
                const cellSalah = document.getElementById(`cell-salah-${activeHasilId}`);
                const cellKosong = document.getElementById(`cell-kosong-${activeHasilId}`);
                const cellNilai = document.getElementById(`cell-nilai-${activeHasilId}`);
                const cellStatus = document.getElementById(`cell-status-${activeHasilId}`);
                const cellCatatan = document.getElementById(`cell-catatan-${activeHasilId}`);

                if (cellBenar) cellBenar.textContent = d.jumlah_benar;
                if (cellSalah) cellSalah.textContent = d.jumlah_salah;
                if (cellKosong) cellKosong.textContent = d.jumlah_kosong;
                if (cellNilai) cellNilai.textContent = parseFloat(d.nilai_akhir).toFixed(2);
                if (cellCatatan) cellCatatan.textContent = d.catatan_scan || '-';

                if (cellStatus) {
                    if (d.status_scan === 'valid') {
                        cellStatus.innerHTML = '<span class="badge" style="background:#ecfdf5; color:#059669; font-weight:600; padding:3px 8px; border-radius:6px; font-size:11px;">Valid</span>';
                    } else {
                        cellStatus.innerHTML = '<span class="badge" style="background:#fffbeb; color:#d97706; font-weight:600; padding:3px 8px; border-radius:6px; font-size:11px;">Perlu Cek</span>';
                    }
                }
            } else {
                alertBox.style.background = '#fef2f2';
                alertBox.style.color = '#991b1b';
                alertBox.style.border = '1px solid #fecaca';
                alertBox.textContent = res.message || 'Gagal menyimpan jawaban.';
            }
        })
        .catch(err => {
            alertBox.style.background = '#fef2f2';
            alertBox.style.color = '#991b1b';
            alertBox.style.border = '1px solid #fecaca';
            alertBox.textContent = 'Terjadi kesalahan jaringan.';
        });
    }

    // Tutup jika klik overlay luar
    document.getElementById('reviewModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeReviewModal();
        }
    });
</script>
@endsection

