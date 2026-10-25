@extends('layouts.app')

@section('title', 'Profil Pengguna')

@section('header-actions')
<a href="{{ route('user') }}" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
    </svg>
    <span>Keamanan & Password</span>
</a>
@endsection

@section('content')
<div class="page-full-container">

    {{-- Pesan Sukses / Error --}}
    @if (session('success'))
    <div class="alert-banner alert-success" style="display:flex; align-items:center; gap:12px; padding:12px 18px; border-radius:12px; background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.25); color:#065F46;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px; height:18px; flex-shrink:0; color:#10B981;">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span style="font-size:13.5px; font-weight:600;">{{ session('success') }}</span>
    </div>
    @endif

    @if ($errors->any())
    <div class="alert-banner alert-error" style="display:flex; align-items:flex-start; gap:12px; padding:14px 18px; border-radius:12px; background:rgba(239, 68, 68, 0.08); border:1px solid rgba(239, 68, 68, 0.2); color:#991B1B;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px; height:18px; flex-shrink:0; margin-top:2px; color:#EF4444;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div style="font-size:13.5px;">
            <div style="font-weight:700; margin-bottom:4px;">Gagal menyimpan perubahan:</div>
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Kartu Ringkasan Akun Pengguna & Tombol Keamanan (Roda Gigi) --}}
    <div class="profile-card-clean">
        <div class="profile-hero-top">
            <div class="profile-user-left">
                <div class="profile-avatar-box {{ $theme['kode'] }}" style="overflow:hidden; display:flex; align-items:center; justify-content:center;">
                    @if($user->foto_url)
                        <img src="{{ $user->foto_url }}" alt="{{ $user->name }}" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">
                    @else
                        {{ $user->initials }}
                    @endif
                </div>
                <div>
                    <h2 class="profile-name-text">{{ $user->name }}</h2>
                    <div class="profile-meta-row">
                        @php
                            $roleBadgeClass = match($user->role) {
                                'yayasan' => 'yayasan',
                                'admin_lembaga' => 'smp',
                                default => 'pondok',
                            };
                            $roleLabel = match($user->role) {
                                'yayasan' => 'Yayasan / Super Admin',
                                'admin_lembaga' => 'Admin Lembaga',
                                default => 'Guru / Pengajar',
                            };
                        @endphp
                        <span class="badge-lembaga badge-lembaga-{{ $roleBadgeClass }}">
                            {{ $roleLabel }}
                        </span>
                        @if($user->primaryLembaga)
                            <span class="badge-lembaga badge-lembaga-{{ $user->primaryLembaga->kode }}">
                                {{ $user->primaryLembaga->singkatan }}
                            </span>
                        @endif
                        @if($user->is_active)
                            <span style="font-size:12px; font-weight:600; color:#10B981; display:inline-flex; align-items:center; gap:5px; margin-left:4px;">
                                <span style="width:7px; height:7px; border-radius:50%; background:#10B981;"></span>
                                Aktif
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="profile-actions-right">
                {{-- Tombol Roda Gigi untuk Keamanan & Password (Buka /user) --}}
                <a href="{{ route('user') }}" class="btn-gear-security" title="Buka Pengaturan Keamanan & Kata Sandi">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="gear-icon">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    <span>Keamanan &amp; Password</span>
                </a>

                {{-- Tombol Keluar dari Akun --}}
                <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn-clean-logout" title="Keluar dari akun">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Card Registrasi Mata Pelajaran Guru (Pengajuan ke Yayasan) --}}
    <div class="profile-card-clean" style="border-left: 4px solid var(--electric-blue, #0284c7);">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:18px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:38px; height:38px; border-radius:10px; background:rgba(2, 132, 199, 0.1); color:var(--electric-blue, #0284c7); display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px;">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--text-primary);">Registrasi Mata Pelajaran Pengampu</h3>
                    <p style="margin:0; font-size:12.5px; color:var(--text-secondary);">Daftarkan mata pelajaran yang Anda ampu untuk diverifikasi dan disetujui oleh Yayasan</p>
                </div>
            </div>

            {{-- Badge Status Pengajuan Saat Ini --}}
            <div>
                @if($user->mapel_status === 'disetujui')
                    <span style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; background:rgba(16, 185, 129, 0.12); color:#065F46; font-size:12.5px; font-weight:700; border:1px solid rgba(16, 185, 129, 0.3);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px; color:#10B981;">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Resmi Disetujui Yayasan</span>
                    </span>
                @elseif($user->mapel_status === 'menunggu')
                    <span style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; background:rgba(245, 158, 11, 0.12); color:#B45309; font-size:12.5px; font-weight:700; border:1px solid rgba(245, 158, 11, 0.35);">
                        <span style="width:8px; height:8px; border-radius:50%; background:#F59E0B; display:inline-block; animation:pulse 1.5s infinite;"></span>
                        <span>Menunggu Persetujuan Yayasan</span>
                    </span>
                @elseif($user->mapel_status === 'ditolak')
                    <span style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; background:rgba(239, 68, 68, 0.1); color:#B91C1C; font-size:12.5px; font-weight:700; border:1px solid rgba(239, 68, 68, 0.25);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px;">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        <span>Perlu Revisi / Ditolak</span>
                    </span>
                @else
                    <span style="display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; background:rgba(100, 116, 139, 0.1); color:#475569; font-size:12.5px; font-weight:600; border:1px solid rgba(100, 116, 139, 0.2);">
                        <span>Belum Mengajukan</span>
                    </span>
                @endif
            </div>
        </div>

        {{-- Kotak Keterangan Status --}}
        @if($user->mapel_status === 'disetujui')
            <div style="background:rgba(16, 185, 129, 0.06); border:1px solid rgba(16, 185, 129, 0.2); border-radius:12px; padding:16px 20px; margin-bottom:20px;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                    <div>
                        <div style="font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#065F46;">Mata Pelajaran Aktif Anda:</div>
                        <div style="font-size:20px; font-weight:800; color:#047857; margin-top:4px; display:flex; align-items:center; gap:8px;">
                            <span>{{ $user->mata_pelajaran }}</span>
                            <svg viewBox="0 0 24 24" fill="#10B981" stroke="#fff" stroke-width="2" style="width:20px; height:20px;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="9 12 11 14 15 10"></polyline>
                            </svg>
                        </div>
                        <div style="font-size:12.5px; color:var(--text-secondary); margin-top:6px;">
                            Status telah diverifikasi oleh Yayasan
                            @if($user->mapel_diverifikasi_at)
                                pada {{ $user->mapel_diverifikasi_at->translatedFormat('d F Y, H:i') }} WIB
                            @endif
                            @if($user->mapelDiverifikasiBy)
                                (Verifikator: {{ $user->mapelDiverifikasiBy->name }})
                            @endif
                        </div>
                        @if($user->mapel_catatan)
                        <div style="font-size:12.5px; color:var(--text-primary); margin-top:8px; background:rgba(255,255,255,0.7); padding:6px 12px; border-radius:8px; border:1px solid rgba(16, 185, 129, 0.2); display:inline-block;">
                            Catatan Yayasan: <em>"{{ $user->mapel_catatan }}"</em>
                        </div>
                        @endif
                    </div>

                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('formRegistrasiMapelWrap').style.display = document.getElementById('formRegistrasiMapelWrap').style.display === 'none' ? 'block' : 'none';"
                            style="font-size:12.5px; padding:8px 14px; border-radius:10px; display:inline-flex; align-items:center; gap:6px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px; height:14px;">
                            <path d="M12 20h9"></path>
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                        </svg>
                        <span>Ajukan Perubahan Mata Pelajaran</span>
                    </button>
                </div>
            </div>
        @elseif($user->mapel_status === 'menunggu')
            <div style="background:rgba(245, 158, 11, 0.08); border:1px solid rgba(245, 158, 11, 0.3); border-radius:12px; padding:16px 20px; margin-bottom:20px;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
                    <div>
                        <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#B45309;">Pengajuan Sedang Ditinjau Yayasan:</div>
                        <div style="font-size:20px; font-weight:800; color:#D97706; margin-top:4px;">
                            {{ $user->mapel_diajukan }}
                        </div>
                        <div style="font-size:12.5px; color:var(--text-secondary); margin-top:6px;">
                            Diajukan {{ $user->mapel_diajukan_at ? $user->mapel_diajukan_at->diffForHumans() : 'baru saja' }}
                            ({{ $user->mapel_diajukan_at ? $user->mapel_diajukan_at->format('d/m/Y H:i') : '' }}).
                            Pihak Yayasan akan segera memverifikasi dan menyetujui pengajuan Anda di menu <strong>Guru Sekolah</strong>.
                        </div>
                        @if($user->mapel_catatan)
                        <div style="font-size:12.5px; color:var(--text-primary); margin-top:8px; background:rgba(255,255,255,0.7); padding:6px 12px; border-radius:8px; border:1px solid rgba(245, 158, 11, 0.2); display:inline-block;">
                            Catatan yang Anda sertakan: <em>"{{ $user->mapel_catatan }}"</em>
                        </div>
                        @endif
                    </div>

                    <form action="{{ route('profile.batal-mapel') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pengajuan mata pelajaran ini?');">
                        @csrf
                        <button type="submit" class="btn btn-secondary" style="font-size:12.5px; padding:8px 14px; border-radius:10px; color:#EF4444; border-color:rgba(239, 68, 68, 0.3);">
                            Batalkan Pengajuan
                        </button>
                    </form>
                </div>
            </div>
        @elseif($user->mapel_status === 'ditolak')
            <div style="background:rgba(239, 68, 68, 0.08); border:1px solid rgba(239, 68, 68, 0.25); border-radius:12px; padding:16px 20px; margin-bottom:20px;">
                <div style="font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#B91C1C;">Pengajuan Mata Pelajaran Perlu Revisi / Ditolak:</div>
                <div style="font-size:18px; font-weight:800; color:#DC2626; margin-top:4px;">
                    {{ $user->mapel_diajukan }}
                </div>
                <div style="font-size:13px; color:var(--text-primary); margin-top:8px; background:rgba(255,255,255,0.8); padding:8px 14px; border-radius:8px; border:1px solid rgba(239, 68, 68, 0.2);">
                    <strong>Alasan / Catatan dari Yayasan:</strong> "{{ $user->mapel_catatan ?: 'Mohon perbaiki atau ajukan mata pelajaran yang sesuai.' }}"
                </div>
                <p style="margin:8px 0 0 0; font-size:12px; color:var(--text-secondary);">
                    Silakan gunakan formulir di bawah ini untuk mengajukan kembali mata pelajaran Anda yang sesuai.
                </p>
            </div>
        @else
            <div style="background:rgba(2, 132, 199, 0.05); border:1px solid rgba(2, 132, 199, 0.15); border-radius:12px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:center; gap:12px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px; height:20px; color:var(--electric-blue); flex-shrink:0;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <div style="font-size:13px; color:var(--text-secondary);">
                    Anda belum mendaftarkan mata pelajaran yang Anda ampu ke Yayasan. Pilih mata pelajaran di bawah ini (misal: <strong>Informatika</strong>) lalu klik <strong>Ajukan ke Yayasan</strong> agar yayasan dapat memverifikasi status mengajar Anda.
                </div>
            </div>
        @endif

        {{-- Form Registrasi / Pengajuan Mata Pelajaran --}}
        <div id="formRegistrasiMapelWrap" style="{{ $user->mapel_status === 'disetujui' ? 'display:none;' : 'display:block;' }}">
            <form action="{{ route('profile.registrasi-mapel') }}" method="POST" style="display:flex; flex-direction:column; gap:16px;">
                @csrf

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px;">
                    {{-- Input / Select Mata Pelajaran --}}
                    <div class="clean-input-group">
                        <label for="registrasi_mata_pelajaran" class="clean-input-label">
                            Pilih / Ketik Mata Pelajaran yang Diampu <span style="color:#EF4444;">*</span>
                        </label>
                        <input type="text" name="mata_pelajaran" id="registrasi_mata_pelajaran" list="listMapelProfile" class="clean-input-control" required
                               value="{{ old('mata_pelajaran', $user->mapel_status === 'menunggu' ? $user->mapel_diajukan : ($user->mata_pelajaran ?: 'Informatika')) }}"
                               placeholder="Contoh: Informatika, Matematika, Fiqih...">
                        <datalist id="listMapelProfile">
                            <option value="Informatika">
                            <option value="Matematika">
                            <option value="Bahasa Indonesia">
                            <option value="Bahasa Inggris">
                            <option value="Ilmu Pengetahuan Alam (IPA)">
                            <option value="Fisika">
                            <option value="Biologi">
                            <option value="Kimia">
                            <option value="Ilmu Pengetahuan Sosial (IPS)">
                            <option value="Sejarah Indonesia">
                            <option value="Geografi">
                            <option value="Ekonomi">
                            <option value="Sosiologi">
                            <option value="Pendidikan Agama Islam (PAI)">
                            <option value="Al-Qur'an Hadits">
                            <option value="Fiqih">
                            <option value="Akidah Akhlak">
                            <option value="Sejarah Kebudayaan Islam (SKI)">
                            <option value="Bahasa Arab">
                            <option value="Tahfidz Al-Qur'an">
                            <option value="Nahwu & Shorof">
                            <option value="Pendidikan Pancasila & Kewarganegaraan (PPKn)">
                            <option value="Pendidikan Jasmani, Olahraga & Kesehatan (PJOK)">
                            <option value="Seni Budaya">
                            <option value="Prakarya & Kewirausahaan">
                            <option value="Bimbingan Konseling (BK)">
                        </datalist>
                        <small style="color:var(--text-muted); font-size:11.5px; margin-top:4px; display:block;">
                            Pilih dari rekomendasi mata pelajaran di atas atau ketik langsung jika belum tersedia.
                        </small>
                    </div>

                    {{-- Catatan / Keterangan Pengajuan --}}
                    <div class="clean-input-group">
                        <label for="registrasi_catatan" class="clean-input-label">Catatan / Keterangan Tambahan untuk Yayasan (Opsional)</label>
                        <input type="text" name="catatan" id="registrasi_catatan" class="clean-input-control"
                               value="{{ old('catatan', $user->mapel_status === 'menunggu' ? $user->mapel_catatan : '') }}"
                               placeholder="Contoh: Mengajar kelas VII dan VIII SMP IT Al-Muttaqin">
                        <small style="color:var(--text-muted); font-size:11.5px; margin-top:4px; display:block;">
                            Sertakan jenjang kelas atau detail tugas mengajar jika relevan.
                        </small>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; align-items:center; gap:10px; margin-top:4px;">
                    @if($user->mapel_status === 'disetujui')
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('formRegistrasiMapelWrap').style.display='none';" style="padding:9px 16px; border-radius:10px; font-size:13px;">
                        Tutup
                    </button>
                    @endif
                    <button type="submit" class="btn {{ $theme['btn_class'] ?? 'btn-primary' }}" style="padding:10px 22px; border-radius:10px; font-size:13.5px; font-weight:700; display:inline-flex; align-items:center; gap:8px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px; height:16px;">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                        <span>
                            @if($user->mapel_status === 'disetujui')
                                Ajukan Perubahan Mata Pelajaran ke Yayasan
                            @elseif($user->mapel_status === 'menunggu')
                                Perbarui Pengajuan Mata Pelajaran
                            @elseif($user->mapel_status === 'ditolak')
                                Ajukan Ulang ke Yayasan
                            @else
                                Ajukan Mata Pelajaran ke Yayasan
                            @endif
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Form Biodata Diri & Kontak (Dapat Diedit Mandiri) --}}
    <div class="profile-card-clean">
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:24px;">
            @csrf
            @method('PUT')

            {{-- Bagian Foto Profil --}}
            <div style="padding:16px 20px; border-radius:12px; border:1px solid var(--border-light); background:var(--bg-page); display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                <div style="width:68px; height:68px; border-radius:50%; overflow:hidden; background:#0F172A; color:#FFFFFF; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:22px; flex-shrink:0; border:2px solid var(--border-medium);">
                    @if($user->foto_url)
                        <img id="previewFotoProfil" src="{{ $user->foto_url }}" alt="{{ $user->name }}" style="width:100%; height:100%; object-fit:cover;">
                    @else
                        <span id="initialsFotoProfil">{{ $user->initials }}</span>
                        <img id="previewFotoProfil" src="" alt="{{ $user->name }}" style="width:100%; height:100%; object-fit:cover; display:none;">
                    @endif
                </div>
                <div style="flex:1; min-width:220px;">
                    <label for="fotoInput" style="font-weight:700; font-size:13.5px; color:var(--text-primary); display:block; margin-bottom:4px;">Foto Profil</label>
                    <p style="font-size:12px; color:var(--text-muted); margin:0 0 10px 0;">Format JPG, PNG, atau WEBP (maks. 5MB). Otomatis dikompresi agar ringan dan cepat.</p>
                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                        <input type="file" name="foto" id="fotoInput" accept="image/*" class="clean-input-control" style="font-size:12.5px; padding:6px 10px; max-width:280px;" onchange="previewAvatar(event)">
                        @if($user->foto)
                            <label style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; color:#EF4444; cursor:pointer;">
                                <input type="checkbox" name="hapus_foto" value="1" id="hapusFotoCheckbox">
                                <span>Hapus foto (gunakan inisial)</span>
                            </label>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Bagian 1: Biodata Diri --}}
            <div>
                <div class="profile-section-heading">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span>Biodata Diri</span>
                </div>

                <div class="profile-biodata-grid">
                    <div class="clean-input-group">
                        <label for="name" class="clean-input-label">Nama Lengkap <span style="color:#EF4444;">*</span></label>
                        <input type="text" name="name" id="name" class="clean-input-control" value="{{ old('name', $user->name) }}" required placeholder="Nama lengkap beserta gelar jika ada">
                    </div>

                    <div class="clean-input-group">
                        <label for="nip" class="clean-input-label">NIP / NUPTK</label>
                        <input type="text" name="nip" id="nip" class="clean-input-control" value="{{ old('nip', $user->nip) }}" placeholder="Nomor Induk Pegawai / NUPTK">
                    </div>

                    <div class="clean-input-group">
                        <label for="jabatan" class="clean-input-label">Jabatan / Amanah Tugas</label>
                        <input type="text" name="jabatan" id="jabatan" class="clean-input-control" value="{{ old('jabatan', $user->jabatan) }}" placeholder="Contoh: Guru Matematika / Tata Usaha">
                    </div>

                    <div class="clean-input-group">
                        <label for="jenis_kelamin" class="clean-input-label">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="jenis_kelamin" class="clean-input-control">
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="Laki-laki" {{ old('jenis_kelamin', $user->jenis_kelamin) === 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="Perempuan" {{ old('jenis_kelamin', $user->jenis_kelamin) === 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>

                    <div class="clean-input-group">
                        <label for="tempat_lahir" class="clean-input-label">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" id="tempat_lahir" class="clean-input-control" value="{{ old('tempat_lahir', $user->tempat_lahir) }}" placeholder="Kota / Kabupaten tempat lahir">
                    </div>

                    <div class="clean-input-group">
                        <label for="tanggal_lahir" class="clean-input-label">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" id="tanggal_lahir" class="clean-input-control" value="{{ old('tanggal_lahir', $user->tanggal_lahir ? date('Y-m-d', strtotime($user->tanggal_lahir)) : '') }}">
                    </div>
                </div>
            </div>

            {{-- Bagian 2: Kontak --}}
            <div>
                <div class="profile-section-heading">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    <span>Informasi Kontak</span>
                </div>

                <div class="profile-kontak-grid">
                    <div class="clean-input-group">
                        <label for="email" class="clean-input-label">Alamat Email <span style="color:#EF4444;">*</span></label>
                        <input type="email" name="email" id="email" class="clean-input-control" value="{{ old('email', $user->email) }}" required placeholder="email@almuttaqin.id">
                    </div>

                    <div class="clean-input-group">
                        <label for="telepon" class="clean-input-label">Nomor WhatsApp / HP</label>
                        <input type="text" name="telepon" id="telepon" class="clean-input-control" value="{{ old('telepon', $user->telepon) }}" placeholder="Contoh: 081234567890">
                    </div>
                </div>

                <div class="clean-input-group" style="margin-top:16px;">
                    <label for="alamat" class="clean-input-label">Alamat Lengkap</label>
                    <textarea name="alamat" id="alamat" rows="3" class="clean-input-control" placeholder="Tuliskan alamat lengkap tempat tinggal...">{{ old('alamat', $user->alamat) }}</textarea>
                </div>
            </div>

            {{-- Tombol Aksi Simpan --}}
            <div style="display:flex; justify-content:flex-end; align-items:center; gap:12px; margin-top:8px; padding-top:16px; border-top:1px solid var(--border-subtle, #F1F5F9);">
                <button type="submit" class="btn {{ $theme['btn_class'] }}" style="padding:10px 24px; border-radius:10px; font-size:13.5px; display:inline-flex; align-items:center; gap:8px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px; height:16px;">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>

</div>

<script>
function previewAvatar(event) {
    const file = event.target.files[0];
    if (file) {
        const preview = document.getElementById('previewFotoProfil');
        const initials = document.getElementById('initialsFotoProfil');
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
        if (initials) initials.style.display = 'none';
    }
}
</script>
@endsection
