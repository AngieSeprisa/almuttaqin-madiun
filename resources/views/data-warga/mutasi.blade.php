@extends('layouts.app')

@section('title', 'Mutasi')

@section('header-actions')
@if(!auth()->check() || auth()->user()->canDo('mutasi_warga', 'create'))
<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
    <button type="button" class="btn btn-primary" data-modal-target="modalTambahMutasi">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Tambah Siswa Mutasi</span>
    </button>
</div>
@endif
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

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
            <strong>Terjadi kesalahan:</strong>
            <ul style="margin: 4px 0 0 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Kartu Statistik --}}
    @php
        $mutasiPalette = [
            'smp'     => '#2563eb',
            'sma'     => '#047857',
            'sdit'    => '#0891b2',
            'ra'      => '#7c3aed',
            'reguler' => '#d97706',
        ];
        $mutasiFallback = ['#4f46e5', '#0891b2', '#ea580c', '#db2777'];
    @endphp
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:14px;">
        <div class="form-section-card" style="padding:16px 18px;">
            <div style="font-size:12px; font-weight:600; color:var(--text-secondary);">
                Total Siswa Mutasi{{ !empty($scopedFormal) ? ' (' . $scopedFormal->nama_singkat . ')' : '' }}
            </div>
            <div style="font-size:26px; font-weight:800; color:var(--text-primary); margin-top:4px;">{{ number_format($totalMutasi) }}</div>
        </div>
        @foreach($mutasiStats ?? [] as $mIdx => $mStat)
            @php
                $mColor = $mutasiPalette[$mStat['kode']] ?? $mutasiFallback[$mIdx % count($mutasiFallback)];
            @endphp
            <div class="form-section-card" style="padding:16px 18px;">
                <div style="font-size:12px; font-weight:600; color:{{ $mColor }};">{{ $mStat['label'] }}</div>
                <div style="font-size:26px; font-weight:800; color:{{ $mColor }}; margin-top:4px;">{{ number_format($mStat['count']) }}</div>
            </div>
        @endforeach
    </div>

    {{-- Filter & Pencarian --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('data-warga.mutasi') }}" method="GET" class="filter-form">
            <div class="filter-group-left">
                @if(count($jenjangList ?? []) > 1)
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>
                    <select name="jenjang" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Jenjang</option>
                        @foreach($jenjangList as $j)
                            <option value="{{ $j }}" {{ request('jenjang') === $j ? 'selected' : '' }}>{{ $j }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if(request('search') || (count($jenjangList ?? []) > 1 && request('jenjang')))
                <a href="{{ route('data-warga.mutasi') }}" class="btn btn-sm btn-secondary filter-btn-reset" title="Reset Filter">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    <span>Reset</span>
                </a>
                @endif
            </div>

            <div class="filter-search-wrap">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control filter-search-input" placeholder="Cari nama siswa, tujuan mutasi, atau keterangan...">
                <button type="submit" class="btn btn-secondary filter-search-btn" title="Cari">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    {{-- Tabel Siswa Mutasi --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width:52px;">No</th>
                        <th>Nama Lengkap</th>
                        <th>Jenjang</th>
                        <th>Kamar / Kelas</th>
                        <th>Tanggal Mutasi</th>
                        <th>Tujuan Mutasi</th>
                        <th>Keterangan</th>
                        <th style="text-align:center; width:150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($siswaMutasi as $idx => $item)
                    @php
                        $j = $item->jenjang ?? ($item->pendaftaran?->jenjang ?? 'Reguler');
                        $badgeColor = match($j) {
                            'SMP' => 'smp',
                            'SMA' => 'sma',
                            default => 'pondok',
                        };
                    @endphp
                    <tr>
                        <td>{{ $siswaMutasi->firstItem() + $idx }}</td>
                        <td>
                            <div style="font-weight:700; color:var(--text-primary); font-size:13.5px;">{{ $item->nama_lengkap }}</div>
                        </td>
                        <td>
                            <span class="badge-lembaga badge-lembaga-{{ $badgeColor }}">{{ $j }}</span>
                        </td>
                        <td>
                            {{ implode(' • ', array_filter([$item->kamar, $item->kelas])) ?: '-' }}
                        </td>
                        <td>{{ $item->tanggal_mutasi ? $item->tanggal_mutasi->translatedFormat('d M Y') : '-' }}</td>
                        <td>{{ $item->tujuan_mutasi ?: '-' }}</td>
                        <td>{{ $item->keterangan_status ?: '-' }}</td>
                        <td style="text-align:center;">
                            <div class="action-btn-group">
                                <a href="{{ route('siswa.show', $item->id) }}" class="action-btn action-btn-view" title="Lihat Biodata">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>

                                @if(!auth()->check() || auth()->user()->canDo('mutasi_warga', 'edit'))
                                <button type="button"
                                        class="action-btn action-btn-edit"
                                        data-modal-target="modalEditMutasi{{ $item->id }}"
                                        title="Edit Mutasi">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>

                                <form action="{{ route('data-warga.kembalikan-aktif', $item->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="button"
                                            class="action-btn action-btn-delete js-swal-confirm"
                                            data-title="Kembalikan ke Siswa Aktif?"
                                            data-html="Kembalikan status <strong>{{ $item->nama_lengkap }}</strong> menjadi siswa aktif?"
                                            data-icon="question"
                                            data-confirm-text="Ya, Kembalikan"
                                            data-confirm-color="#2563eb"
                                            data-cancel-text="Batal"
                                            title="Kembalikan ke Siswa Aktif">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="1 4 1 10 7 10"></polyline>
                                            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>

                            {{-- Modal Edit Mutasi --}}
                            <div class="app-modal" id="modalEditMutasi{{ $item->id }}" role="dialog" aria-modal="true">
                                <div class="modal-dialog" style="max-width:480px;">
                                    <form action="{{ route('data-warga.mutasi.update', $item->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h3 class="modal-title">Edit Data Mutasi</h3>
                                            <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">&times;</button>
                                        </div>
                                        <div class="modal-body" style="text-align:left; display:flex; flex-direction:column; gap:12px;">
                                            <div class="form-group" style="margin:0;">
                                                <label class="form-label">Nama Siswa</label>
                                                <input type="text" value="{{ $item->nama_lengkap }}" class="form-control" disabled>
                                            </div>
                                            <div class="form-group" style="margin:0;">
                                                <label class="form-label">Tanggal Mutasi <span class="required-mark">*</span></label>
                                                <input type="date" name="tanggal_mutasi" value="{{ $item->tanggal_mutasi ? $item->tanggal_mutasi->format('Y-m-d') : date('Y-m-d') }}" class="form-control" required>
                                            </div>
                                            <div class="form-group" style="margin:0;">
                                                <label class="form-label">Tujuan Mutasi</label>
                                                <input type="text" name="tujuan_mutasi" value="{{ $item->tujuan_mutasi }}" class="form-control">
                                            </div>
                                            <div class="form-group" style="margin:0;">
                                                <label class="form-label">Keterangan</label>
                                                <textarea name="keterangan_status" rows="2" class="form-control">{{ $item->keterangan_status }}</textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="padding:36px 16px; text-align:center;">
                            <div style="font-size:14px; font-weight:700; color:var(--text-primary);">Belum ada data siswa mutasi</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($siswaMutasi->hasPages())
        <div style="padding:12px 16px; border-top:1px solid var(--border-subtle);">
            {{ $siswaMutasi->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Modal Tambah Siswa Mutasi --}}
<div class="app-modal" id="modalTambahMutasi" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:480px;">
        <form action="{{ route('data-warga.mutasi.store') }}" method="POST">
            @csrf
            <div class="modal-header">
                <h3 class="modal-title">Tambah Siswa Mutasi</h3>
                <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">&times;</button>
            </div>
            <div class="modal-body" style="text-align:left; display:flex; flex-direction:column; gap:12px;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Pilih Siswa <span class="required-mark">*</span></label>
                    <select name="siswa_id" class="form-select" required>
                        <option value="">-- Pilih Siswa --</option>
                        @foreach($siswaAktifList as $sw)
                            <option value="{{ $sw->id }}">{{ $sw->nama_lengkap }} — {{ $sw->jenjang ?: 'Reguler' }} • {{ $sw->kamar ?: '-' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Tanggal Mutasi <span class="required-mark">*</span></label>
                    <input type="date" name="tanggal_mutasi" value="{{ date('Y-m-d') }}" class="form-control" required>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Tujuan Mutasi</label>
                    <input type="text" name="tujuan_mutasi" class="form-control">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan_status" rows="2" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection

