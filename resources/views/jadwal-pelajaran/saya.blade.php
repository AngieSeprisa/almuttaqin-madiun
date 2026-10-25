@extends('layouts.app')

@section('title', 'Jadwal Mengajar Saya')

@section('header-actions')
<a href="{{ route('jadwal-pelajaran.index') }}" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
        <line x1="3" y1="9" x2="21" y2="9"></line>
        <line x1="9" y1="21" x2="9" y2="9"></line>
    </svg>
    <span>Master Jadwal Grid</span>
</a>
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

    @if($errors->any())
    <div class="alert-banner alert-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
            <strong>Validasi Penginputan Jadwal Gagal:</strong>
            <ul style="margin:4px 0 0 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Stats Cards --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px;">
        <div class="form-section-card" style="padding:16px; display:flex; align-items:center; gap:14px;">
            <div style="width:44px; height:44px; border-radius:10px; background:rgba(16, 185, 129, 0.12); color:#10B981; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-muted, #64748b); font-weight:500;">Total Jam Mengajar</div>
                <div style="font-size:22px; font-weight:700; color:var(--text-body, #1e293b); line-height:1.2;">
                    {{ $totalJam }} <span style="font-size:13px; font-weight:500; color:var(--text-muted, #94a3b8);">JP / Minggu</span>
                </div>
            </div>
        </div>

        <div class="form-section-card" style="padding:16px; display:flex; align-items:center; gap:14px;">
            <div style="width:44px; height:44px; border-radius:10px; background:rgba(59, 130, 246, 0.12); color:#3B82F6; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-muted, #64748b); font-weight:500;">Mengajar SMP</div>
                <div style="font-size:22px; font-weight:700; color:#3B82F6; line-height:1.2;">
                    {{ $totalJamSMP }} <span style="font-size:13px; font-weight:500; color:var(--text-muted, #94a3b8);">JP</span>
                </div>
            </div>
        </div>

        <div class="form-section-card" style="padding:16px; display:flex; align-items:center; gap:14px;">
            <div style="width:44px; height:44px; border-radius:10px; background:rgba(139, 92, 246, 0.12); color:#8B5CF6; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                </svg>
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-muted, #64748b); font-weight:500;">Mengajar SMA</div>
                <div style="font-size:22px; font-weight:700; color:#8B5CF6; line-height:1.2;">
                    {{ $totalJamSMA }} <span style="font-size:13px; font-weight:500; color:var(--text-muted, #94a3b8);">JP</span>
                </div>
            </div>
        </div>

        <div class="form-section-card" style="padding:16px; display:flex; align-items:center; gap:14px;">
            <div style="width:44px; height:44px; border-radius:10px; background:{{ $hasLocked ? 'rgba(239, 68, 68, 0.12)' : 'rgba(16, 185, 129, 0.12)' }}; color:{{ $hasLocked ? '#EF4444' : '#10B981' }}; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:22px;height:22px;">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-muted, #64748b); font-weight:500;">Status Input</div>
                <div style="font-size:14px; font-weight:700; color:{{ $hasLocked ? '#EF4444' : '#10B981' }}; line-height:1.2;">
                    {{ $hasLocked ? 'Terkunci Final' : 'Bebas Diedit' }}
                </div>
                <div style="font-size:11px; color:var(--text-muted, #94a3b8); margin-top:2px;">
                    TA {{ $tahunAjaran }} ({{ $semester }})
                </div>
            </div>
        </div>
    </div>

    {{-- Main 2-Column Content --}}
    <div style="display:grid; grid-template-columns:380px 1fr; gap:20px; align-items:start;" class="jadwal-grid-layout">
        
        {{-- Left Column: Input Form Mandiri --}}
        <div class="form-section-card" style="padding:20px;">
            <div style="border-bottom:1px solid var(--border-color, #e5e7eb); padding-bottom:14px; margin-bottom:18px;">
                <h3 style="font-size:16px; font-weight:700; color:var(--text-body, #1e293b); margin:0; display:flex; align-items:center; gap:8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px; color:var(--primary, #10B981);">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                    <span>Input Jadwal Mengajar</span>
                </h3>
                <p style="font-size:12px; color:var(--text-muted, #64748b); margin:3px 0 0 0;">
                    Pilih jenjang, rombel kelas, mapel yang diampu, hari, dan rentang jam pelajaran.
                </p>
            </div>

            {{-- Info Sinkronisasi Guru Sekolah --}}
            <div style="background:var(--bg-subtle, #f8fafc); border:1px solid var(--border-color, #e2e8f0); border-radius:10px; padding:12px 14px; margin-bottom:16px;">
                <div style="font-size:11px; font-weight:700; color:var(--text-muted, #64748b); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px; display:flex; align-items:center; justify-content:space-between;">
                    <span style="display:flex; align-items:center; gap:6px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px; color:var(--primary, #10B981);">
                            <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                            <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                        </svg>
                        <span>Mata Pelajaran Diampu (Guru Sekolah)</span>
                    </span>
                    <a href="{{ route('guru-sekolah.index') }}" style="font-size:11px; color:var(--primary, #059669); text-decoration:none; font-weight:600;" title="Buka Menu Guru Sekolah">
                        Menu Guru &rarr;
                    </a>
                </div>
                @if($hasAssignedMapel)
                    <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                        @foreach($teacherMapels as $tmp)
                        <span class="badge" style="background:rgba(16, 185, 129, 0.12); color:#059669; font-weight:700; font-size:12.5px; padding:3px 9px; border-radius:6px; border:1px solid rgba(16, 185, 129, 0.25);">
                            ★ {{ $tmp }}
                        </span>
                        @endforeach
                        <span class="badge" style="background:#f1f5f9; color:#475569; font-size:11px; padding:3px 8px; border-radius:6px;">
                            {{ ucfirst($mapelStatus ?? 'Aktif') }}
                        </span>
                    </div>
                @else
                    <div style="font-size:12px; color:#d97706; display:flex; align-items:center; gap:6px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px; flex-shrink:0;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span>Belum ada penetapan mata pelajaran di menu Guru Sekolah.</span>
                    </div>
                @endif
            </div>

            <form action="{{ route('jadwal-pelajaran.saya.store') }}" method="POST" id="formInputJadwal" style="display:flex; flex-direction:column; gap:16px;">
                @csrf

                {{-- Pilihan Jenjang --}}
                <div class="form-group">
                    <label class="form-label" style="font-weight:600; font-size:13px; margin-bottom:6px; display:block;">
                        1. Jenjang Sekolah <span style="color:#ef4444;">*</span>
                    </label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <label style="border:2px solid #e2e8f0; border-radius:8px; padding:10px 12px; display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:13px; transition:all 0.15s ease;" id="labelJenjangSMP">
                            <input type="radio" name="jenjang_select" value="SMP" checked onchange="switchJenjang('SMP')" style="accent-color:#10B981;">
                            <span>SMP Al-Muttaqin</span>
                        </label>
                        <label style="border:2px solid #e2e8f0; border-radius:8px; padding:10px 12px; display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:13px; transition:all 0.15s ease;" id="labelJenjangSMA">
                            <input type="radio" name="jenjang_select" value="SMA" onchange="switchJenjang('SMA')" style="accent-color:#10B981;">
                            <span>SMA Al-Muttaqin</span>
                        </label>
                    </div>
                </div>

                {{-- Rombel Kelas --}}
                <div class="form-group">
                    <label for="classroom_id" class="form-label" style="font-weight:600; font-size:13px; margin-bottom:6px; display:block;">
                        2. Kelas / Rombel Tujuan <span style="color:#ef4444;">*</span>
                    </label>
                    <select name="classroom_id" id="classroom_id" class="form-control" required style="width:100%;">
                        <option value="">-- Pilih Kelas --</option>
                    </select>
                </div>

                {{-- Mata Pelajaran (Tersinkron dengan Plotting Mapel & Guru Sekolah) --}}
                <div class="form-group">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px;">
                        <label for="subject_id" class="form-label" style="font-weight:600; font-size:13px; margin:0;">
                            3. Mata Pelajaran <span style="color:#ef4444;">*</span>
                        </label>
                        <span style="font-size:11px; color:var(--text-muted, #64748b);">
                            Sumber: Plotting Mapel
                        </span>
                    </div>
                    <select name="subject_id" id="subject_id" class="form-control" required style="width:100%;">
                        <option value="">-- Pilih Mata Pelajaran --</option>
                    </select>

                    {{-- Notice bila belum ada mapel yang di-plot untuk jenjang ini --}}
                    <div id="noMapelAlert" style="display:none; margin-top:8px; padding:10px 12px; border-radius:8px; background:rgba(239, 68, 68, 0.08); border:1px solid rgba(239, 68, 68, 0.25); color:#dc2626; font-size:12px;">
                        <div>Mata pelajaran yang Anda ampu di Guru Sekolah belum terdaftar pada jenjang ini di menu <strong>Plotting Mata Pelajaran</strong>.</div>
                        @if($isPrivileged)
                        <div style="margin-top:6px;">
                            <a href="{{ route('plotting-mapel.index') }}" class="btn btn-secondary" style="font-size:11.5px; padding:4px 10px; text-decoration:none; display:inline-block;">
                                Buka Plotting Mata Pelajaran &rarr;
                            </a>
                        </div>
                        @endif
                    </div>

                    @if($isPrivileged)
                    <div style="margin-top:6px;">
                        <label style="font-size:11.5px; color:var(--text-muted, #64748b); display:flex; align-items:center; gap:6px; cursor:pointer;">
                            <input type="checkbox" id="toggleShowAllMapel" onchange="toggleAllMapelMode(this.checked)" style="accent-color:#10B981;">
                            <span>Tampilkan seluruh mapel dari Plotting Mata Pelajaran (Mode Pengurus)</span>
                        </label>
                    </div>
                    @endif
                </div>

                {{-- Hari Mengajar --}}
                <div class="form-group">
                    <label for="hari" class="form-label" style="font-weight:600; font-size:13px; margin-bottom:6px; display:block;">
                        4. Hari Mengajar <span style="color:#ef4444;">*</span>
                    </label>
                    <select name="hari" id="hari" class="form-control" required style="width:100%;">
                        @foreach($days as $day)
                            <option value="{{ $day }}">{{ $day }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Rentang Jam (Jam Mulai s/d Jam Selesai) --}}
                <div class="form-group">
                    <label class="form-label" style="font-weight:600; font-size:13px; margin-bottom:6px; display:block;">
                        5. Rentang Jam Pelajaran (JP) <span style="color:#ef4444;">*</span>
                    </label>
                    <div style="display:grid; grid-template-columns:1fr auto 1fr; gap:10px; align-items:center;">
                        <div>
                            <select name="jam_mulai" id="jam_mulai" class="form-control" required style="width:100%;">
                                @for($i = 1; $i <= 8; $i++)
                                    <option value="{{ $i }}">Jam Ke-{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div style="font-size:12px; font-weight:600; color:var(--text-muted, #64748b);">s/d</div>
                        <div>
                            <select name="jam_selesai" id="jam_selesai" class="form-control" required style="width:100%;">
                                @for($i = 1; $i <= 8; $i++)
                                    <option value="{{ $i }}">Jam Ke-{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div style="font-size:11.5px; color:var(--text-muted, #64748b); margin-top:5px;" id="jamPreviewText">
                        Total alokasi: <strong>1 Jam Pelajaran (JP)</strong>
                    </div>
                </div>

                {{-- Catatan Opsional --}}
                <div class="form-group">
                    <label for="catatan" class="form-label" style="font-weight:600; font-size:13px; margin-bottom:6px; display:block;">
                        Catatan Khusus (Opsional)
                    </label>
                    <input type="text" name="catatan" id="catatan" class="form-control" placeholder="Contoh: Lab Komputer / Perpustakaan" maxlength="255" style="width:100%;">
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:11px; font-weight:700; font-size:14px; margin-top:6px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <span>Simpan Jadwal Mengajar</span>
                </button>
            </form>
        </div>

        {{-- Right Column: Unified Weekly Schedule --}}
        <div style="display:flex; flex-direction:column; gap:16px;">
            <div class="form-section-card" style="padding:18px 20px;">
                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; border-bottom:1px solid var(--border-color, #e5e7eb); padding-bottom:12px; margin-bottom:16px;">
                    <div>
                        <h3 style="font-size:16px; font-weight:700; color:var(--text-body, #1e293b); margin:0;">
                            Kalender Mengajar Mingguan Saya
                        </h3>
                        <p style="font-size:12.5px; color:var(--text-muted, #64748b); margin:2px 0 0 0;">
                            Penggabungan jadwal mengajar di SMP dan SMA Al-Muttaqin
                        </p>
                    </div>
                    <div>
                        <span class="badge" style="background:var(--primary-subtle, #ecfdf5); color:var(--primary, #059669); font-weight:700; padding:5px 12px; border-radius:6px; font-size:12.5px;">
                            {{ $mySchedules->count() }} Slot Terjadwal
                        </span>
                    </div>
                </div>

                @if($mySchedules->isEmpty())
                <div style="padding:40px 20px; text-align:center; color:var(--text-muted, #64748b); background:var(--bg-subtle, #f8fafc); border-radius:8px; border:1px dashed var(--border-color, #cbd5e1);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:48px;height:48px; margin-bottom:12px; color:#94a3b8;">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <div style="font-weight:700; font-size:15px; color:var(--text-body, #1e293b); margin-bottom:4px;">
                        Belum Ada Jadwal Mengajar
                    </div>
                    <p style="font-size:13px; max-width:400px; margin:0 auto;">
                        Silakan gunakan formulir di sebelah kiri untuk menginput jadwal mengajar Anda secara mandiri untuk Semester Gasal 2026/2027.
                    </p>
                </div>
                @else
                <div style="display:flex; flex-direction:column; gap:14px;">
                    @php
                        $groupedByDay = $mySchedules->groupBy(fn($s) => $s->timeSlot->hari);
                    @endphp

                    @foreach($days as $day)
                        @php
                            $daySchedules = $groupedByDay->get($day, collect());
                        @endphp
                        @if($daySchedules->isNotEmpty())
                        <div style="background:var(--bg-subtle, #f8fafc); border:1px solid var(--border-color, #e2e8f0); border-radius:10px; overflow:hidden;">
                            {{-- Day Header --}}
                            <div style="padding:10px 14px; background:var(--bg-subtle, #f1f5f9); border-bottom:1px solid var(--border-color, #e2e8f0); display:flex; align-items:center; justify-content:space-between;">
                                <div style="font-weight:700; font-size:13.5px; color:var(--text-body, #1e293b); display:flex; align-items:center; gap:8px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px; color:var(--primary, #10B981);">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <span>{{ $day }}</span>
                                </div>
                                <span class="badge" style="background:#ffffff; border:1px solid var(--border-color, #cbd5e1); color:var(--text-body, #334155); font-weight:600; font-size:11.5px; padding:2px 8px; border-radius:4px;">
                                    {{ $daySchedules->count() }} JP
                                </span>
                            </div>

                            {{-- Schedule items for this day --}}
                            <div style="padding:10px 14px; display:flex; flex-direction:column; gap:8px;">
                                @foreach($daySchedules as $sched)
                                <div style="background:#ffffff; border:1px solid var(--border-color, #e2e8f0); border-radius:8px; padding:10px 14px; display:flex; align-items:center; justify-content:space-between; gap:12px; transition:all 0.15s ease;">
                                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                                        {{-- Jenjang Badge --}}
                                        @if($sched->classroom->jenjang === 'SMP')
                                            <span class="badge" style="background:rgba(59, 130, 246, 0.12); color:#2563eb; font-weight:700; font-size:11.5px; padding:3px 8px; border-radius:6px;">
                                                SMP
                                            </span>
                                        @else
                                            <span class="badge" style="background:rgba(139, 92, 246, 0.12); color:#7c3aed; font-weight:700; font-size:11.5px; padding:3px 8px; border-radius:6px;">
                                                SMA
                                            </span>
                                        @endif

                                        {{-- Jam & Waktu --}}
                                        <div style="min-width:110px;">
                                            <div style="font-weight:700; font-size:12.5px; color:var(--text-body, #1e293b);">
                                                Jam Ke-{{ $sched->timeSlot->jam_ke }}
                                            </div>
                                            <div style="font-size:11px; color:var(--text-muted, #64748b);">
                                                {{ substr($sched->timeSlot->waktu_mulai, 0, 5) }} - {{ substr($sched->timeSlot->waktu_selesai, 0, 5) }}
                                            </div>
                                        </div>

                                        {{-- Kelas & Mapel --}}
                                        <div>
                                            <div style="display:flex; align-items:center; gap:6px;">
                                                <span class="badge" style="background:var(--bg-subtle, #f1f5f9); color:var(--text-body, #1e293b); font-weight:700; font-size:12px; padding:2px 8px; border-radius:4px; border:1px solid var(--border-color, #cbd5e1);">
                                                    Kelas {{ $sched->classroom->nama_kelas }}
                                                </span>
                                                <span style="font-weight:700; font-size:13px; color:var(--text-body, #1e293b);">
                                                    {{ $sched->subject->nama_pelajaran }}
                                                </span>
                                                <span class="badge" style="background:var(--primary-subtle, #ecfdf5); color:var(--primary, #059669); font-weight:700; font-size:10.5px; padding:1px 5px; border-radius:4px;">
                                                    {{ $sched->subject->kode }}
                                                </span>
                                            </div>
                                            @if($sched->catatan)
                                            <div style="font-size:11.5px; color:var(--text-muted, #64748b); margin-top:2px;">
                                                Catatan: {{ $sched->catatan }}
                                            </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Action: Delete --}}
                                    <div>
                                        @if($sched->is_locked)
                                            <span class="badge" style="background:#fef2f2; color:#ef4444; font-size:11px; font-weight:600; padding:4px 8px; border-radius:4px; display:inline-flex; align-items:center; gap:4px;" title="Terkunci oleh Admin/Yayasan">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;">
                                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                                </svg>
                                                <span>Terkunci</span>
                                            </span>
                                        @else
                                            <form action="{{ route('jadwal-pelajaran.destroy', $sched->id) }}" method="POST" style="margin:0;" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan/menghapus jadwal ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-secondary" style="color:#ef4444; border-color:#fecaca; background:#fef2f2; padding:5px 10px; font-size:12px; display:inline-flex; align-items:center; gap:4px;" title="Batalkan Jadwal">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                    </svg>
                                                    <span>Hapus</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
                @endif
            </div>
        </div>

    </div>

</div>

{{-- Embedded Master Data for Instant Dynamic Dropdown Switching --}}
<script>
    const masterData = {
        SMP: {
            classrooms: {!! json_encode($smpClassrooms->map(fn($c) => ['id' => $c->id, 'nama' => $c->nama_kelas, 'tingkat' => $c->tingkat])) !!},
            assignedSubjects: {!! json_encode($smpSubjects->map(fn($s) => ['id' => $s->id, 'kode' => $s->kode, 'nama' => $s->nama_pelajaran ?? $s->mata_pelajaran, 'is_assigned' => $s->is_guru_assigned ?? false])) !!},
            allSubjects: {!! json_encode($allSmpSubjects->map(fn($s) => ['id' => $s->id, 'kode' => $s->kode, 'nama' => $s->nama_pelajaran ?? $s->mata_pelajaran, 'is_assigned' => $s->is_guru_assigned ?? false])) !!}
        },
        SMA: {
            classrooms: {!! json_encode($smaClassrooms->map(fn($c) => ['id' => $c->id, 'nama' => $c->nama_kelas, 'tingkat' => $c->tingkat])) !!},
            assignedSubjects: {!! json_encode($smaSubjects->map(fn($s) => ['id' => $s->id, 'kode' => $s->kode, 'nama' => $s->nama_pelajaran ?? $s->mata_pelajaran, 'is_assigned' => $s->is_guru_assigned ?? false])) !!},
            allSubjects: {!! json_encode($allSmaSubjects->map(fn($s) => ['id' => $s->id, 'kode' => $s->kode, 'nama' => $s->nama_pelajaran ?? $s->mata_pelajaran, 'is_assigned' => $s->is_guru_assigned ?? false])) !!}
        }
    };

    let currentJenjang = 'SMP';
    let showAllMapelMode = false;

    function toggleAllMapelMode(showAll) {
        showAllMapelMode = !!showAll;
        populateSubjects(currentJenjang);
    }

    function populateSubjects(jenjang) {
        const subSelect = document.getElementById('subject_id');
        const alertBox = document.getElementById('noMapelAlert');
        const submitBtn = document.querySelector('#formInputJadwal button[type="submit"]');

        const assignedList = masterData[jenjang].assignedSubjects || [];
        const allList = masterData[jenjang].allSubjects || [];
        const list = showAllMapelMode ? allList : assignedList;

        subSelect.innerHTML = '<option value="">-- Pilih Mata Pelajaran --</option>';

        if (!list || list.length === 0) {
            if (alertBox) alertBox.style.display = 'block';
            subSelect.disabled = true;
            if (submitBtn) submitBtn.disabled = true;
            return;
        }

        if (alertBox) alertBox.style.display = 'none';
        subSelect.disabled = false;
        if (submitBtn) submitBtn.disabled = false;

        list.forEach(sub => {
            const opt = document.createElement('option');
            opt.value = sub.id;
            const badge = sub.is_assigned ? ' ★ [Diampu]' : '';
            opt.textContent = `${sub.kode ? sub.kode + ' - ' : ''}${sub.nama}${badge}`;
            subSelect.appendChild(opt);
        });

        // Auto-select if only 1 subject available
        if (list.length === 1) {
            subSelect.value = list[0].id;
        }
    }

    function switchJenjang(jenjang) {
        currentJenjang = jenjang;
        const clsSelect = document.getElementById('classroom_id');
        const labelSMP = document.getElementById('labelJenjangSMP');
        const labelSMA = document.getElementById('labelJenjangSMA');

        if (jenjang === 'SMP') {
            labelSMP.style.borderColor = '#10B981';
            labelSMP.style.background = '#ECFDF5';
            labelSMA.style.borderColor = '#e2e8f0';
            labelSMA.style.background = '#ffffff';
        } else {
            labelSMA.style.borderColor = '#10B981';
            labelSMA.style.background = '#ECFDF5';
            labelSMP.style.borderColor = '#e2e8f0';
            labelSMP.style.background = '#ffffff';
        }

        // Update Classrooms
        clsSelect.innerHTML = '<option value="">-- Pilih Kelas --</option>';
        masterData[jenjang].classrooms.forEach(cls => {
            const opt = document.createElement('option');
            opt.value = cls.id;
            opt.textContent = `Kelas ${cls.nama} (Tingkat ${cls.tingkat})`;
            clsSelect.appendChild(opt);
        });

        // Update Subjects
        populateSubjects(jenjang);
    }

    // Rentang Jam sync
    const jamMulaiSelect = document.getElementById('jam_mulai');
    const jamSelesaiSelect = document.getElementById('jam_selesai');
    const jamPreviewText = document.getElementById('jamPreviewText');

    function updateJamPreview() {
        const start = parseInt(jamMulaiSelect.value, 10);
        let end = parseInt(jamSelesaiSelect.value, 10);

        if (end < start) {
            jamSelesaiSelect.value = start;
            end = start;
        }

        const count = (end - start) + 1;
        jamPreviewText.innerHTML = `Total alokasi: <strong>${count} Jam Pelajaran (JP)</strong> (${start === end ? 'Jam Ke-' + start : 'Jam Ke-' + start + ' s/d Jam Ke-' + end})`;
    }

    jamMulaiSelect.addEventListener('change', function() {
        if (parseInt(jamSelesaiSelect.value, 10) < parseInt(this.value, 10)) {
            jamSelesaiSelect.value = this.value;
        }
        updateJamPreview();
    });
    jamSelesaiSelect.addEventListener('change', updateJamPreview);

    // Initial render
    document.addEventListener('DOMContentLoaded', function() {
        switchJenjang('SMP');
        updateJamPreview();
    });
</script>

<style>
    @media (max-width: 900px) {
        .jadwal-grid-layout {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endsection
