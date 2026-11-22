@extends('layouts.app')

@section('title', 'Link Penerimaan')

@section('header-actions')
<div style="display:flex; gap:10px; align-items:center;">
    <a href="{{ route('pendaftaran.index') }}" class="btn btn-secondary">
        Data Pendaftar
    </a>
    @if(!auth()->check() || auth()->user()->canDo('pendaftaran', 'create'))
    <a href="{{ route('penerimaan.create') }}" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:17px;height:17px;">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Buat Penerimaan
    </a>
    @endif
</div>
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:20px; width:100%; max-width:100%; min-width:0;">

    {{-- Stats Ringkas --}}
    <div class="stats-grid" style="margin-bottom:0;">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Total Event</span>
                <div class="stat-icon-wrapper stat-icon-total">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ number_format($stats['total_event'], 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Aktif</span>
                <div class="stat-icon-wrapper stat-icon-aktif">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ number_format($stats['aktif'], 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Ditutup</span>
                <div class="stat-icon-wrapper stat-icon-sakit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ number_format($stats['tutup'], 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Total Pendaftar</span>
                <div class="stat-icon-wrapper stat-icon-pulang">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ number_format($stats['pendaftar'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- Pencarian --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('penerimaan.index') }}" method="GET" class="filter-form">
            <div class="filter-group-left">
                @if(request('search'))
                    <a href="{{ route('penerimaan.index') }}" class="btn btn-sm btn-secondary filter-btn-reset">
                        <span>Reset</span>
                    </a>
                @endif
            </div>
            <div class="filter-search-wrap">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control filter-search-input" placeholder="Cari judul, slug, tahun...">
                <button type="submit" class="btn btn-secondary filter-search-btn" title="Cari">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    {{-- Tabel Event Penerimaan --}}
    <div class="table-card">
        <div class="table-wrapper">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="min-width: 210px;">Penerimaan</th>
                        <th style="min-width: 260px;">Link Pendaftaran</th>
                        <th style="min-width: 90px; white-space: nowrap;">Jenjang</th>
                        <th style="min-width: 150px;">Gelombang</th>
                        <th style="min-width: 110px; white-space: nowrap;">Pendaftar</th>
                        <th style="min-width: 120px; white-space: nowrap;">Status</th>
                        <th style="text-align:center; min-width: 140px; white-space: nowrap;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($penerimaans as $event)
                    @php
                        $state = $event->statusState();
                        $publicUrl = $event->publicUrl();
                        $pillClass = match($state['code']) {
                            'buka' => 'status-diterima',
                            'belum' => 'status-lulus-seleksi',
                            'penuh' => 'status-lulus-seleksi',
                            default => 'status-ditolak',
                        };
                        $jenjangClass = match($event->jenjang) {
                            'SMP' => 'jenjang-pill-smp',
                            'SMA' => 'jenjang-pill-sma',
                            default => 'jenjang-pill-reguler',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div style="font-weight:700; color:var(--text-primary);">{{ $event->judul }}</div>
                            <div style="font-size:12px; color:var(--text-muted); margin-top:2px;">{{ $event->tahun_ajaran }}</div>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:nowrap;">
                                <code style="font-size:12px; padding:4px 8px; border-radius:6px; background:var(--bg-surface); border:1px solid var(--border-light); color:var(--text-primary); max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $publicUrl }}">/spmb/{{ $event->slug }}</code>
                                <button type="button" class="action-btn action-btn-view js-copy-link" data-url="{{ $publicUrl }}" title="Salin Link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                    </svg>
                                </button>
                                <a href="{{ $publicUrl }}" target="_blank" class="action-btn action-btn-print" title="Buka Form Publik">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                        <polyline points="15 3 21 3 21 9"></polyline>
                                        <line x1="10" y1="14" x2="21" y2="3"></line>
                                    </svg>
                                </a>
                            </div>
                        </td>
                        <td>
                            <span class="jenjang-pill {{ $jenjangClass }}">{{ $event->jenjang }}</span>
                        </td>
                        <td>
                            @if($event->gelombangs->isEmpty())
                                <span style="font-size:12.5px; color:var(--text-muted);">Terbuka</span>
                            @else
                                <div style="display:flex; flex-direction:column; gap:2px;">
                                    @foreach($event->gelombangs->take(2) as $gel)
                                        <div style="font-size:12px; color:var(--text-secondary); white-space:nowrap;">
                                            <strong>{{ $gel->nama_gelombang }}</strong> · {{ $gel->tanggal_mulai->format('d/m/y') }}–{{ $gel->tanggal_selesai->format('d/m/y') }}
                                        </div>
                                    @endforeach
                                    @if($event->gelombangs->count() > 2)
                                        <div style="font-size:11.5px; color:var(--text-muted);">+{{ $event->gelombangs->count() - 2 }} lainnya</div>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('pendaftaran.index', ['penerimaan_id' => $event->id]) }}" style="text-decoration:none; display:inline-flex; flex-direction:column; gap:2px;">
                                <span style="font-weight:700; color:var(--electric-blue);">{{ $event->pendaftarans_count }} Orang</span>
                                <span style="font-size:11.5px; color:var(--text-muted);">
                                    {{ $event->diterima_count }} diterima
                                </span>
                            </a>
                        </td>
                        <td>
                            <span class="status-pill {{ $pillClass }}">{{ $state['label'] }}</span>
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group">
                                <a href="{{ route('pendaftaran.index', ['penerimaan_id' => $event->id]) }}" class="action-btn action-btn-view" title="Lihat Pendaftar">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                    </svg>
                                </a>

                                @if(!auth()->check() || auth()->user()->canDo('pendaftaran', 'edit'))
                                <form action="{{ route('penerimaan.toggle', $event->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="action-btn action-btn-print" title="{{ $event->is_active ? 'Tutup Pendaftaran' : 'Buka Pendaftaran' }}">
                                        @if($event->is_active)
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                            </svg>
                                        @else
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
                                            </svg>
                                        @endif
                                    </button>
                                </form>

                                <a href="{{ route('penerimaan.edit', $event->id) }}" class="action-btn action-btn-edit" title="Edit">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                @endif

                                @if(!auth()->check() || auth()->user()->canDo('pendaftaran', 'delete'))
                                <form action="{{ route('penerimaan.destroy', $event->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus link penerimaan ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn" style="color:#DC2626;" title="Hapus">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                            <path d="M10 11v6"></path>
                                            <path d="M14 11v6"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="cell-empty">
                            Belum ada link penerimaan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($penerimaans->hasPages())
        <div style="padding:16px 20px; border-top:1px solid var(--border-light);">
            {{ $penerimaans->links() }}
        </div>
        @endif
    </div>

    {{-- Tampilan Mobile --}}
    <div class="mobile-card-list">
        @forelse ($penerimaans as $event)
        @php
            $state = $event->statusState();
            $publicUrl = $event->publicUrl();
            $pillClass = match($state['code']) {
                'buka' => 'status-diterima',
                'belum' => 'status-lulus-seleksi',
                'penuh' => 'status-lulus-seleksi',
                default => 'status-ditolak',
            };
        @endphp
        <div class="mobile-card-item" style="flex-direction:column; align-items:stretch; gap:10px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
                <div>
                    <div class="mobile-card-title">{{ $event->judul }}</div>
                    <div style="font-size:12px; color:var(--text-muted);">{{ $event->tahun_ajaran }} · {{ $event->jenjang }} · {{ $event->pendaftarans_count }} pendaftar</div>
                </div>
                <span class="status-pill {{ $pillClass }}" style="font-size:11px; padding:3px 9px;">{{ $state['label'] }}</span>
            </div>
            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; padding:8px 10px; border-radius:8px; background:var(--bg-surface); border:1px solid var(--border-light);">
                <code style="font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">/spmb/{{ $event->slug }}</code>
                <div style="display:flex; gap:6px; flex-shrink:0;">
                    <button type="button" class="btn btn-sm btn-secondary js-copy-link" data-url="{{ $publicUrl }}">Salin</button>
                    <a href="{{ route('pendaftaran.index', ['penerimaan_id' => $event->id]) }}" class="btn btn-sm btn-primary">Pendaftar</a>
                    @if(!auth()->check() || auth()->user()->canDo('pendaftaran', 'edit'))
                    <a href="{{ route('penerimaan.edit', $event->id) }}" class="btn btn-sm btn-secondary">Edit</a>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="mobile-card-empty">Belum ada link penerimaan.</div>
        @endforelse
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-copy-link').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = this.getAttribute('data-url');
            if (!url) return;

            var notify = function () {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Link disalin',
                        text: url,
                        timer: 1800,
                        showConfirmButton: false
                    });
                }
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(notify).catch(function () {
                    window.prompt('Salin link:', url);
                });
            } else {
                window.prompt('Salin link:', url);
            }
        });
    });
});
</script>
@endpush
@endsection
