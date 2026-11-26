@extends('layouts.app')

@section('title', 'Rekap Pelanggaran')

@section('header-actions')
@if(!auth()->check() || auth()->user()->canDo('pelanggaran_input', 'create'))
<a href="{{ route('pelanggaran.input') }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:6px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
    </svg>
    <span>Input Pelanggaran</span>
</a>
@endif
@endsection

@push('styles')
<style>
    .plg-kelas-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        background: var(--bg-page, #f1f5f9);
        color: var(--text-secondary, #475569);
        border: 1px solid var(--border-light, #e2e8f0);
    }
</style>
@endpush

@section('content')
<div style="display:flex; flex-direction:column; gap:16px; width:100%;">

    {{-- Filter Card --}}
    <div class="filter-card" style="display:none;">
        <form action="{{ route('pelanggaran.rekap') }}" method="GET">
            @if($kelasList->isNotEmpty())
            <select name="kelas" class="form-select" aria-label="Kelas" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $kls)
                    <option value="{{ $kls }}" {{ request('kelas') === $kls ? 'selected' : '' }}>{{ $kls }}</option>
                @endforeach
            </select>
            @endif
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau pelanggaran...">
            <a href="{{ route('pelanggaran.rekap') }}" class="filter-btn-reset" title="Reset">Reset</a>
        </form>
    </div>

    {{-- Tabel Rekap Pelanggaran --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="width:52px; text-align:center;">No</th>
                        <th style="width:140px;">Tanggal</th>
                        <th style="width:220px;">Nama</th>
                        <th style="width:130px;">Kelas</th>
                        <th>Pelanggaran</th>
                        @if(!auth()->check() || auth()->user()->canDo('pelanggaran_rekap', 'edit') || auth()->user()->canDo('pelanggaran_rekap', 'delete'))
                        <th style="width:110px; text-align:center;">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @php
                        $canEditRekap = !auth()->check() || auth()->user()->canDo('pelanggaran_rekap', 'edit');
                        $canDeleteRekap = !auth()->check() || auth()->user()->canDo('pelanggaran_rekap', 'delete');
                        $showAksiCol = $canEditRekap || $canDeleteRekap;
                    @endphp
                    @forelse($pelanggarans as $idx => $item)
                    <tr>
                        <td style="text-align:center; font-weight:600; color:var(--text-secondary);">
                            {{ ($pelanggarans->firstItem() ?? 1) + $idx }}
                        </td>
                        <td style="white-space:nowrap; color:var(--text-secondary); font-weight:500;">
                            {{ $item->created_at ? $item->created_at->translatedFormat('d M Y') : '-' }}
                        </td>
                        <td style="font-weight:700; color:var(--text-primary);">
                            {{ $item->nama_siswa }}
                        </td>
                        <td>
                            <span class="plg-kelas-badge">{{ $item->kelas ?: '-' }}</span>
                        </td>
                        <td style="color:var(--text-primary); line-height:1.5;">
                            {{ $item->pelanggaran }}
                        </td>
                        @if($showAksiCol)
                        <td style="text-align:center;">
                            <div class="action-btn-group" style="justify-content:center;">
                                @if($canEditRekap)
                                <button type="button" class="action-btn action-btn-edit" data-modal-target="modalEditPelanggaran{{ $item->id }}" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                                @endif
                                @if($canDeleteRekap)
                                <form action="{{ route('pelanggaran.destroy', $item->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="action-btn action-btn-delete js-swal-confirm"
                                            data-title="Hapus Data Pelanggaran"
                                            data-html="Hapus data pelanggaran <strong>{{ e($item->nama_siswa) }}</strong>?"
                                            data-icon="warning"
                                            data-confirm-text="Hapus"
                                            data-confirm-color="#DC2626"
                                            data-cancel-text="Batal"
                                            title="Hapus">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $showAksiCol ? 6 : 5 }}" style="text-align:center; padding:42px 16px; color:var(--text-secondary); font-weight:600;">
                            Belum ada data pelanggaran
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pelanggarans->hasPages())
        <div style="padding:14px 18px; border-top:1px solid var(--border-light);">
            {{ $pelanggarans->links() }}
        </div>
        @endif
    </div>

</div>

{{-- Modal Edit Pelanggaran --}}
@if(!auth()->check() || auth()->user()->canDo('pelanggaran_rekap', 'edit'))
@foreach($pelanggarans as $item)
<div class="app-modal" id="modalEditPelanggaran{{ $item->id }}" role="dialog" aria-modal="true">
    <div class="modal-dialog" style="max-width:480px;">
        <form action="{{ route('pelanggaran.update', $item->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <div class="modal-header-title">
                    <h3 class="modal-title">Edit Pelanggaran</h3>
                </div>
                <button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="modal-body" style="text-align:left; display:flex; flex-direction:column; gap:14px;">
                <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Nama</label>
                        <input type="text" class="form-control" value="{{ $item->nama_siswa }}" readonly style="background:var(--nav-hover-bg);">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kelas</label>
                        <input type="text" class="form-control" value="{{ $item->kelas ?: '-' }}" readonly style="background:var(--nav-hover-bg);">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Pelanggaran <span class="required-mark">*</span></label>
                    <textarea name="pelanggaran" rows="4" class="form-control" required>{{ $item->pelanggaran }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endif
@endsection
