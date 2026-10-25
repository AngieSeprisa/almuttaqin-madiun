@extends('layouts.app')

@section('title', 'Master Jadwal Pelajaran (SMP & SMA)')

@section('header-actions')
<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
    <a href="{{ route('jadwal-pelajaran.saya') }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
        </svg>
        <span>Jadwal Mengajar Saya</span>
    </a>

    <a href="{{ route('jadwal-pelajaran.cetak', ['jenjang' => $jenjang]) }}" target="_blank" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        <span>Cetak Jadwal (PDF)</span>
    </a>

    @if(Auth::user()->isYayasan() || Auth::user()->role === 'admin_lembaga')
    <form action="{{ route('jadwal-pelajaran.toggle-lock') }}" method="POST" id="formToggleLock" style="display:inline;">
        @csrf
        <input type="hidden" name="jenjang" value="{{ $jenjang }}">
        <input type="hidden" name="is_locked" value="{{ $isLocked ? '0' : '1' }}">
        @if($isLocked)
        <button type="submit" class="btn btn-secondary" style="color:#DC2626; border-color:#FECACA; background:#FEF2F2;" onclick="return confirm('Buka kunci jadwal {{ $jenjang }}? Guru akan dapat kembali mengisi atau mengubah jadwal.')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px; display:inline-block; vertical-align:middle; margin-right:4px;">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
            </svg>
            <span>Buka Kunci ({{ $jenjang }})</span>
        </button>
        @else
        <button type="submit" class="btn btn-secondary" style="color:#059669; border-color:#A7F3D0; background:#ECFDF5;" onclick="return confirm('Kunci final jadwal {{ $jenjang }}? Setelah dikunci, jadwal tidak dapat diubah oleh guru pengampu.')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px; display:inline-block; vertical-align:middle; margin-right:4px;">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <span>Kunci Final ({{ $jenjang }})</span>
        </button>
        @endif
    </form>
    @endif
</div>
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:20px; width:100%;">

    {{-- Feedback Alerts --}}
    @if(session('success'))
    <div class="alert-banner alert-success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    @if(session('error'))
    <div class="alert-banner alert-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>{{ session('error') }}</div>
    </div>
    @endif

    {{-- Top Switcher: Tab SMP vs SMA --}}
    <div class="form-section-card" style="padding:12px 18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px;">
            {{-- Jenjang Segmented Buttons --}}
            <div style="display:inline-flex; background:var(--bg-subtle, #f3f4f6); padding:4px; border-radius:10px; border:1px solid var(--border-color, #e5e7eb);">
                <a href="{{ route('jadwal-pelajaran.index', ['jenjang' => 'SMP', 'hari' => $hariFilter]) }}" 
                   style="display:inline-flex; align-items:center; gap:8px; padding:8px 18px; border-radius:8px; font-weight:700; font-size:13.5px; text-decoration:none; transition:all 0.15s ease; {{ $jenjang === 'SMP' ? 'background:var(--primary, #10B981); color:#ffffff; box-shadow:0 1px 3px rgba(0,0,0,0.1);' : 'color:var(--text-muted, #64748b);' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                    <span>Jadwal SMP (Kelas 7, 8, 9)</span>
                </a>
                <a href="{{ route('jadwal-pelajaran.index', ['jenjang' => 'SMA', 'hari' => $hariFilter]) }}" 
                   style="display:inline-flex; align-items:center; gap:8px; padding:8px 18px; border-radius:8px; font-weight:700; font-size:13.5px; text-decoration:none; transition:all 0.15s ease; {{ $jenjang === 'SMA' ? 'background:var(--primary, #10B981); color:#ffffff; box-shadow:0 1px 3px rgba(0,0,0,0.1);' : 'color:var(--text-muted, #64748b);' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                    </svg>
                    <span>Jadwal SMA (Kelas 10, 11, 12)</span>
                </a>
            </div>

            {{-- Filter Hari Pills --}}
            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                <span style="font-size:12.5px; font-weight:600; color:var(--text-muted, #64748b); margin-right:4px;">Pilih Hari:</span>
                <a href="{{ route('jadwal-pelajaran.index', ['jenjang' => $jenjang, 'hari' => 'Semua']) }}" 
                   class="badge" style="padding:6px 12px; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none; cursor:pointer; {{ $hariFilter === 'Semua' ? 'background:var(--primary, #10B981); color:#ffffff;' : 'background:var(--bg-subtle, #f1f5f9); color:var(--text-body, #334155); border:1px solid var(--border-color, #e2e8f0);' }}">
                   Semua Hari
                </a>
                @foreach($validDays as $day)
                <a href="{{ route('jadwal-pelajaran.index', ['jenjang' => $jenjang, 'hari' => $day]) }}" 
                   class="badge" style="padding:6px 12px; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none; cursor:pointer; {{ $hariFilter === $day ? 'background:var(--primary, #10B981); color:#ffffff;' : 'background:var(--bg-subtle, #f1f5f9); color:var(--text-body, #334155); border:1px solid var(--border-color, #e2e8f0);' }}">
                   {{ $day }}
                </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Stats Cards Row --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:16px;">
        {{-- Card 1: Terjadwal --}}
        <div class="form-section-card" style="padding:16px; display:flex; align-items:center; gap:14px;">
            <div style="width:44px; height:44px; border-radius:10px; background:rgba(16, 185, 129, 0.12); color:#10B981; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-muted, #64748b); font-weight:500;">Jam Terjadwal</div>
                <div style="font-size:20px; font-weight:700; color:var(--text-body, #1e293b); line-height:1.2;">
                    {{ $stats['total_terjadwal'] }} <span style="font-size:12.5px; font-weight:500; color:var(--text-muted, #94a3b8);">/ {{ $stats['total_slot_kbm'] }} Jam</span>
                </div>
            </div>
        </div>

        {{-- Card 2: Persentase --}}
        <div class="form-section-card" style="padding:16px; display:flex; align-items:center; gap:14px;">
            <div style="width:44px; height:44px; border-radius:10px; background:rgba(59, 130, 246, 0.12); color:#3B82F6; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;">
                    <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                    <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                </svg>
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-muted, #64748b); font-weight:500;">Keterisian Jadwal</div>
                <div style="font-size:20px; font-weight:700; color:var(--text-body, #1e293b); line-height:1.2;">
                    {{ $stats['persentase'] }}%
                </div>
            </div>
        </div>

        {{-- Card 3: Guru Terlibat --}}
        <div class="form-section-card" style="padding:16px; display:flex; align-items:center; gap:14px;">
            <div style="width:44px; height:44px; border-radius:10px; background:rgba(139, 92, 246, 0.12); color:#8B5CF6; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-muted, #64748b); font-weight:500;">Guru Mengajar</div>
                <div style="font-size:20px; font-weight:700; color:var(--text-body, #1e293b); line-height:1.2;">
                    {{ $stats['total_guru'] }} <span style="font-size:12.5px; font-weight:500; color:var(--text-muted, #94a3b8);">Guru ({{ $stats['total_kelas'] }} Kelas)</span>
                </div>
            </div>
        </div>

        {{-- Card 4: Status Kunci --}}
        <div class="form-section-card" style="padding:16px; display:flex; align-items:center; gap:14px;">
            <div style="width:44px; height:44px; border-radius:10px; background:{{ $isLocked ? 'rgba(239, 68, 68, 0.12)' : 'rgba(16, 185, 129, 0.12)' }}; color:{{ $isLocked ? '#EF4444' : '#10B981' }}; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                @if($isLocked)
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
                </svg>
                @endif
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-muted, #64748b); font-weight:500;">Status Finalisasi</div>
                <div style="font-size:15px; font-weight:700; color:{{ $isLocked ? '#EF4444' : '#10B981' }}; line-height:1.2;">
                    {{ $isLocked ? 'Terkunci Final' : 'Terbuka (Input Aktif)' }}
                </div>
                <div style="font-size:11px; color:var(--text-muted, #94a3b8); margin-top:2px;">
                    TA {{ $tahunAjaran }} - {{ $semester }}
                </div>
            </div>
        </div>
    </div>

    {{-- Master Jadwal Grid Table Card --}}
    <div class="form-section-card" style="padding:0; overflow:hidden;">
        <div style="padding:16px 20px; border-bottom:1px solid var(--border-color, #e5e7eb); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; background:var(--bg-subtle, #f8fafc);">
            <div>
                <h3 style="font-size:16px; font-weight:700; color:var(--text-body, #1e293b); margin:0;">
                    Matriks Jadwal Pelajaran {{ $jenjang }} Al-Muttaqin
                </h3>
                <p style="font-size:12.5px; color:var(--text-muted, #64748b); margin:2px 0 0 0;">
                    Semester Gasal TA 2026/2027 • Menampilkan slot waktu dan alokasi guru per rombongan belajar
                </p>
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <span class="badge" style="background:var(--primary-subtle, #ecfdf5); color:var(--primary, #059669); font-weight:600; padding:4px 10px; border-radius:6px; font-size:12px;">
                    {{ $classrooms->count() }} Rombel Kelas
                </span>
            </div>
        </div>

        {{-- Table Container with responsive scroll --}}
        <div style="width:100%; overflow-x:auto;">
            <table class="data-table" style="width:100%; border-collapse:collapse; min-width:850px; font-size:13px;">
                <thead>
                    <tr style="background:var(--bg-subtle, #f1f5f9); border-bottom:2px solid var(--border-color, #cbd5e1);">
                        <th style="padding:12px 14px; text-align:center; width:90px; font-weight:700; color:var(--text-body, #334155); border-right:1px solid var(--border-color, #e2e8f0);">
                            Jam Ke-
                        </th>
                        <th style="padding:12px 14px; text-align:center; width:130px; font-weight:700; color:var(--text-body, #334155); border-right:1px solid var(--border-color, #e2e8f0);">
                            Waktu
                        </th>
                        @foreach($classrooms as $cls)
                        <th style="padding:12px 14px; text-align:center; font-weight:700; color:var(--text-body, #334155); border-right:1px solid var(--border-color, #e2e8f0); min-width:130px;">
                            <div style="font-size:14px; color:var(--primary, #059669);">{{ $cls->nama_kelas }}</div>
                            <div style="font-size:11px; font-weight:500; color:var(--text-muted, #64748b);">Tingkat {{ $cls->tingkat }}</div>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($timeSlotsByDay as $dayName => $slots)
                        {{-- Day Header Divider --}}
                        <tr style="background:linear-gradient(90deg, #10B981 0%, #059669 100%); color:#ffffff;">
                            <td colspan="{{ $classrooms->count() + 2 }}" style="padding:8px 16px; font-weight:700; font-size:13px; letter-spacing:0.5px; text-transform:uppercase;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <span>HARI {{ $dayName }}</span>
                                </div>
                            </td>
                        </tr>

                        @foreach($slots as $slot)
                            @if($slot->is_break)
                                {{-- Non-KBM / Break / Upacara Row Spanned --}}
                                <tr style="background:var(--bg-subtle, #fef9c3); border-bottom:1px solid var(--border-color, #e2e8f0);">
                                    <td style="padding:8px 10px; text-align:center; font-weight:600; color:#854d0e; border-right:1px solid var(--border-color, #e2e8f0); font-size:12px;">
                                        {{ $slot->jam_ke === 0 ? '—' : 'Jam ' . $slot->jam_ke }}
                                    </td>
                                    <td style="padding:8px 10px; text-align:center; font-weight:600; color:#854d0e; border-right:1px solid var(--border-color, #e2e8f0); font-size:12px;">
                                        {{ substr($slot->waktu_mulai, 0, 5) }} - {{ substr($slot->waktu_selesai, 0, 5) }}
                                    </td>
                                    <td colspan="{{ $classrooms->count() }}" style="padding:8px 14px; text-align:center; font-weight:600; color:#854d0e; font-size:12.5px;">
                                        <span style="display:inline-flex; align-items:center; gap:6px;">
                                            @if(str_contains(strtolower($slot->keterangan ?? ''), 'upacara'))
                                                🚩 {{ $slot->keterangan }}
                                            @elseif(str_contains(strtolower($slot->keterangan ?? ''), 'sholat') || str_contains(strtolower($slot->keterangan ?? ''), 'dhuhur') || str_contains(strtolower($slot->keterangan ?? ''), 'isoma'))
                                                🕌 {{ $slot->keterangan }}
                                            @else
                                                ☕ {{ $slot->keterangan ?? 'Istirahat' }}
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @else
                                {{-- KBM Regular Teaching Row --}}
                                <tr style="border-bottom:1px solid var(--border-color, #e2e8f0);">
                                    <td style="padding:10px 8px; text-align:center; font-weight:700; color:var(--text-body, #1e293b); border-right:1px solid var(--border-color, #e2e8f0); background:var(--bg-subtle, #f8fafc);">
                                        <span class="badge" style="background:#e0e7ff; color:#3730a3; font-weight:700; font-size:12px; padding:3px 8px; border-radius:6px;">
                                            Jam {{ $slot->jam_ke }}
                                        </span>
                                    </td>
                                    <td style="padding:10px 8px; text-align:center; font-weight:600; color:var(--text-muted, #64748b); border-right:1px solid var(--border-color, #e2e8f0); font-size:12px; background:var(--bg-subtle, #f8fafc);">
                                        {{ substr($slot->waktu_mulai, 0, 5) }} - {{ substr($slot->waktu_selesai, 0, 5) }}
                                    </td>

                                    {{-- Class Columns --}}
                                    @foreach($classrooms as $cls)
                                        @php
                                            $sched = $matrix[$dayName][$slot->id][$cls->id] ?? null;
                                        @endphp
                                        <td style="padding:8px; border-right:1px solid var(--border-color, #e2e8f0); vertical-align:top; {{ $sched ? 'background:rgba(16, 185, 129, 0.04);' : '' }}">
                                            @if($sched)
                                                <div style="background:#ffffff; border:1px solid var(--border-color, #cbd5e1); border-left:3px solid var(--primary, #10B981); border-radius:6px; padding:8px 10px; box-shadow:0 1px 2px rgba(0,0,0,0.04); display:flex; flex-direction:column; gap:4px; position:relative;">
                                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:6px;">
                                                        <span class="badge" style="background:var(--primary-subtle, #ecfdf5); color:var(--primary, #059669); font-weight:700; font-size:11px; padding:2px 6px; border-radius:4px;">
                                                            {{ $sched->subject->kode }}
                                                        </span>
                                                        @if(!$isLocked && (Auth::user()->isYayasan() || Auth::user()->role === 'admin_lembaga' || $sched->teacher_id === Auth::id()))
                                                        <form action="{{ route('jadwal-pelajaran.destroy', $sched->id) }}" method="POST" style="margin:0;" onsubmit="return confirm('Hapus jadwal ini?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" style="background:none; border:none; cursor:pointer; color:#ef4444; padding:0; display:flex;" title="Hapus Jadwal">
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;">
                                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                        @endif
                                                    </div>
                                                    <div style="font-weight:700; font-size:12px; color:var(--text-body, #1e293b); line-height:1.2;">
                                                        {{ $sched->subject->nama_pelajaran }}
                                                    </div>
                                                    <div style="font-size:11px; color:var(--text-muted, #64748b); display:flex; align-items:center; gap:4px;">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px; flex-shrink:0;">
                                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                            <circle cx="12" cy="7" r="4"></circle>
                                                        </svg>
                                                        <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $sched->teacher->name ?? '-' }}">
                                                            {{ $sched->teacher->name ?? '-' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @else
                                                <div style="height:100%; min-height:48px; border:1px dashed var(--border-color, #e2e8f0); border-radius:6px; display:flex; align-items:center; justify-content:center; color:var(--text-muted, #94a3b8); font-size:11.5px; background:var(--bg-subtle, #fafafa);">
                                                    <span>—</span>
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endif
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="{{ $classrooms->count() + 2 }}" style="padding:30px; text-align:center; color:var(--text-muted, #64748b);">
                                Belum ada slot waktu untuk filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Teacher Legend Card --}}
    @if(!empty($teacherLegends))
    <div class="form-section-card" style="padding:18px 20px;">
        <h4 style="font-size:14px; font-weight:700; color:var(--text-body, #1e293b); margin:0 0 12px 0; display:flex; align-items:center; gap:8px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px; color:var(--primary, #10B981);">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <line x1="20" y1="8" x2="20" y2="14"></line>
                <line x1="23" y1="11" x2="17" y2="11"></line>
            </svg>
            <span>Daftar Guru Pengampu Terjadwal ({{ $jenjang }})</span>
        </h4>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:10px;">
            @foreach($teacherLegends as $tId => $leg)
            <div style="padding:8px 12px; background:var(--bg-subtle, #f8fafc); border:1px solid var(--border-color, #e2e8f0); border-radius:8px; display:flex; align-items:center; justify-content:space-between; gap:10px;">
                <div style="font-size:12.5px; font-weight:600; color:var(--text-body, #1e293b); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    {{ $leg['nama_guru'] }}
                </div>
                <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
                    <span class="badge" style="background:var(--primary-subtle, #ecfdf5); color:var(--primary, #059669); font-size:11px; font-weight:700; padding:2px 6px; border-radius:4px;">
                        {{ $leg['kode'] }}
                    </span>
                    <span style="font-size:11px; color:var(--text-muted, #64748b);">
                        {{ $leg['mapel'] }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
