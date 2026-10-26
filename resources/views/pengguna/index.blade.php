@extends('layouts.app')

@section('title', 'Pengguna & Hak Akses')

@section('header-actions')
@if(auth()->user()->canDo('pengguna', 'create'))
<a href="{{ route('pengguna.create') }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
    </svg>
    <span>Tambah Pengguna</span>
</a>
@endif
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:20px;">

    {{-- Alert Banner Sukses & Kredensial untuk WhatsApp --}}
    @if(session('success'))
    <div class="alert-banner alert-success" style="display:flex; align-items:center; gap:12px; padding:12px 18px; border-radius:12px; background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.25); color:#065F46;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px; height:18px; flex-shrink:0; color:#10B981;">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span style="font-size:13.5px; font-weight:600;">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('user_credentials'))
    @php
        $creds = session('user_credentials');
        $waText = "Assalamu'alaikum Wr. Wb.\n\n"
            . "Yth. Ustadz/Ustadzah *" . $creds['name'] . "*,\n"
            . "Berikut adalah akses akun Sistem Informasi Al-Muttaqin Anda:\n\n"
            . "🔗 Link Login: " . $creds['login_url'] . "\n"
            . "📧 Email: " . $creds['email'] . "\n"
            . "🔑 Kata Sandi Sementara: " . $creds['password'] . "\n\n"
            . "Catatan:\n"
            . "Demi keamanan, silakan segera ubah kata sandi ini setelah login melalui menu Keamanan & Password, serta lengkapi data profil Anda.\n\n"
            . "Jazakumullah Khairan.";
    @endphp
    <div class="profile-card-clean" style="border-left:4px solid var(--electric-blue); background:#FFFFFF; padding:20px;">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:14px; flex-wrap:wrap;">
            <div>
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                    <span style="padding:4px 8px; border-radius:6px; background:rgba(0,82,255,0.1); color:var(--electric-blue); font-size:12px; font-weight:700;">
                        {{ $creds['action'] === 'created' ? 'AKUN BARU' : 'RESET SANDI' }}
                    </span>
                    <strong style="font-size:15px; color:var(--text-primary);">Kredensial Siap Dibagikan ke Guru / Pengguna</strong>
                </div>
                <div style="font-size:13px; color:var(--text-secondary); margin-bottom:12px;">
                    Salin informasi berikut atau langsung kirimkan ke WhatsApp guru yang bersangkutan.
                </div>

                <div style="background:var(--bg-page); border:1px solid var(--border-light); border-radius:10px; padding:12px 16px; font-family:monospace; font-size:13px; display:flex; flex-direction:column; gap:4px; max-width:480px;">
                    <div><strong>Nama:</strong> {{ $creds['name'] }}</div>
                    <div><strong>Email:</strong> {{ $creds['email'] }}</div>
                    <div><strong>Kata Sandi Sementara:</strong> <span style="background:rgba(0,82,255,0.1); color:var(--electric-blue); font-weight:700; padding:2px 6px; border-radius:4px;">{{ $creds['password'] }}</span></div>
                </div>
            </div>

            <div style="display:flex; flex-direction:column; gap:8px; align-items:flex-end;">
                <button type="button" class="btn btn-primary" id="btnCopyWa" onclick="copyWaText()" style="display:inline-flex; align-items:center; gap:8px; font-size:13px; padding:8px 16px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                    <span id="btnCopyWaText">Salin Format WhatsApp</span>
                </button>
                <a href="https://wa.me/?text={{ rawurlencode($waText) }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; font-size:12.5px; padding:7px 14px; text-decoration:none; color:#059669; border-color:rgba(5, 150, 105, 0.3);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    <span>Kirim via WhatsApp Web</span>
                </a>
            </div>
        </div>
    </div>
    <textarea id="hiddenWaText" style="display:none;">{{ $waText }}</textarea>
    @endif

    {{-- Filter & Search Card --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('pengguna.index') }}" method="GET" class="filter-form">
            <div class="filter-group-left">
                
                {{-- Filter Lembaga --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>
                    <select name="lembaga_id" id="filter_pengguna_lembaga" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Lembaga</option>
                        @foreach($lembagas as $lem)
                            <option value="{{ $lem->id }}" {{ request('lembaga_id') == $lem->id ? 'selected' : '' }}>
                                {{ $lem->nama_singkat }}
                            </option>
                        @endforeach
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                {{-- Filter Peran --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                    <select name="role" id="filter_pengguna_role" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Peran</option>
                        <option value="yayasan" {{ request('role') === 'yayasan' ? 'selected' : '' }}>Yayasan / Super Admin</option>
                        <option value="admin_lembaga" {{ request('role') === 'admin_lembaga' ? 'selected' : '' }}>Admin Lembaga</option>
                        <option value="guru" {{ request('role') === 'guru' ? 'selected' : '' }}>Guru / Pengajar</option>
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                @if(request('search') || request('role') || request('lembaga_id'))
                    <a href="{{ route('pengguna.index') }}" class="btn btn-sm btn-secondary filter-btn-reset" title="Reset Filter">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        <span>Reset</span>
                    </a>
                @endif
            </div>

            {{-- Pencarian --}}
            <div class="filter-search-wrap">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..."
                       class="form-control filter-search-input">
                <button type="submit" class="btn btn-secondary filter-search-btn" title="Cari">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    {{-- Tabel Pengguna --}}
    <div class="form-section-card" style="padding:0; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table class="permission-table" style="border:none; border-radius:0;">
                <thead>
                    <tr>
                        <th style="padding-left:22px;">Pengguna</th>
                        <th>Peran</th>
                        <th>Jabatan</th>
                        <th>Ruang Kerja Lembaga</th>
                        <th>Status</th>
                        <th style="text-align:right; padding-right:22px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td style="padding-left:22px;">
                            <div style="display:flex; align-items:center; gap:12px;">
                                @if($user->foto_url)
                                    <img src="{{ $user->foto_url }}" alt="{{ $user->name }}" style="width:36px; height:36px; border-radius:10px; object-fit:cover; flex-shrink:0; border:1px solid var(--border-light);">
                                @else
                                    <div class="user-avatar" style="width:36px; height:36px; border-radius:10px; font-size:14px; flex-shrink:0;">
                                        {{ $user->initials }}
                                    </div>
                                @endif
                                <div>
                                    <div style="font-weight:700; color:var(--text-primary);">{{ $user->name }}</div>
                                    <div style="font-size:12px; color:var(--text-muted);">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @php
                                $roleClass = match($user->role) {
                                    'yayasan' => 'yayasan',
                                    'admin_lembaga' => 'smp',
                                    default => 'pondok',
                                };
                            @endphp
                            <span class="badge-lembaga badge-lembaga-{{ $roleClass }}">
                                {{ $user->role === 'yayasan' ? 'Yayasan' : ($user->role === 'admin_lembaga' ? 'Admin Lembaga' : 'Guru') }}
                            </span>
                        </td>
                        <td style="color:var(--text-secondary); font-size:13px;">
                            {{ $user->jabatan ?? '-' }}
                        </td>
                        <td>
                            <div style="display:flex; flex-wrap:wrap; gap:4px; justify-content:center;">
                                @if($user->isYayasan())
                                    <span class="badge-lembaga badge-lembaga-yayasan">Semua Lembaga</span>
                                @else
                                    @forelse($user->lembagas as $lem)
                                        <span class="badge-lembaga badge-lembaga-{{ $lem->kode }}">
                                            {{ $lem->singkatan }}
                                        </span>
                                    @empty
                                        <span style="font-size:12px; color:var(--text-muted);">-</span>
                                    @endforelse
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($user->is_active)
                                <span style="display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600; color:#10B981;">
                                    <span style="width:7px; height:7px; border-radius:50%; background-color:#10B981;"></span>
                                    Aktif
                                </span>
                            @else
                                <span style="display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600; color:#EF4444;">
                                    <span style="width:7px; height:7px; border-radius:50%; background-color:#EF4444;"></span>
                                    Nonaktif
                                </span>
                            @endif
                        </td>
                        <td style="text-align:right; padding-right:22px;">
                            <div style="display:flex; align-items:center; justify-content:flex-end; gap:8px;">
                                @if(auth()->user()->canDo('pengguna', 'edit'))
                                {{-- Tombol Reset Sandi Cepat --}}
                                <button type="button" class="btn btn-sm btn-secondary" onclick="openResetModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ addslashes($user->email) }}')" title="Reset Kata Sandi Pengguna" style="padding:6px 10px; font-size:12px; display:inline-flex; align-items:center; gap:5px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px; color:var(--text-secondary);">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                    <span>Reset Sandi</span>
                                </button>

                                <a href="{{ route('pengguna.edit', $user->id) }}" class="btn btn-sm btn-secondary" title="Atur Hak Akses" style="padding:6px 10px; font-size:12px; text-decoration:none; display:inline-flex; align-items:center; gap:5px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                    <span>Edit</span>
                                </a>
                                @endif

                                @if(auth()->user()->canDo('pengguna', 'delete') && auth()->id() !== $user->id)
                                <form action="{{ route('pengguna.destroy', $user->id) }}" method="POST" style="margin:0;" onsubmit="return confirm('Hapus akun pengguna {{ $user->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-secondary" style="padding:6px 8px; color:#DC2626; border-color:rgba(220, 38, 38, 0.2);" title="Hapus Pengguna">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:36px; color:var(--text-muted);">
                            Tidak ada data pengguna ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div style="padding:14px 22px; border-top:1px solid var(--border-light);">
            {{ $users->links() }}
        </div>
        @endif
    </div>

</div>

{{-- Modal Reset Kata Sandi --}}
<div id="resetModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.5); backdrop-filter:blur(3px); align-items:center; justify-content:center; padding:16px;">
    <div class="profile-card-clean" style="max-width:440px; width:100%; box-shadow:0 20px 40px rgba(0,0,0,0.2); position:relative;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid var(--border-subtle); padding-bottom:12px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:32px; height:32px; border-radius:8px; background:rgba(0,82,255,0.1); color:var(--electric-blue); display:flex; align-items:center; justify-content:center;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <h3 style="margin:0; font-size:16px; font-weight:700; color:var(--text-primary);">Reset Kata Sandi</h3>
            </div>
            <button type="button" onclick="closeResetModal()" style="background:none; border:none; cursor:pointer; color:var(--text-muted); padding:4px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <form id="resetModalForm" method="POST" action="">
            @csrf
            <div style="margin-bottom:14px; font-size:13px; color:var(--text-secondary);">
                Reset sandi untuk akun <strong id="resetModalUserName" style="color:var(--text-primary);"></strong> (<span id="resetModalUserEmail"></span>).
            </div>

            <div class="clean-input-group" style="margin-bottom:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <label for="resetPasswordInput" class="clean-input-label">Kata Sandi Baru</label>
                    <button type="button" onclick="generateModalRandomPassword()" class="btn btn-sm btn-secondary" style="font-size:11px; padding:2px 8px;">
                        Acak Sandi
                    </button>
                </div>
                <input type="text" name="password" id="resetPasswordInput" value="Muttaqin2026!" required class="clean-input-control" placeholder="Minimal 6 karakter">
                <span style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">
                    Pengguna akan diwajibkan mengganti kata sandi ini saat pertama kali login.
                </span>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="closeResetModal()" class="btn btn-secondary" style="font-size:13px; padding:8px 16px;">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary" style="font-size:13px; padding:8px 18px;">
                    Simpan & Reset Sandi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function copyWaText() {
        const text = document.getElementById('hiddenWaText').value;
        navigator.clipboard.writeText(text).then(function() {
            const btnText = document.getElementById('btnCopyWaText');
            btnText.textContent = '✓ Format Tersalin!';
            setTimeout(function() {
                btnText.textContent = 'Salin Format WhatsApp';
            }, 3000);
        });
    }

    function openResetModal(userId, userName, userEmail) {
        const modal = document.getElementById('resetModal');
        const form = document.getElementById('resetModalForm');
        document.getElementById('resetModalUserName').textContent = userName;
        document.getElementById('resetModalUserEmail').textContent = userEmail;
        document.getElementById('resetPasswordInput').value = 'Muttaqin2026!';
        form.action = '/pengguna/' + userId + '/reset-password';
        modal.style.display = 'flex';
    }

    function closeResetModal() {
        document.getElementById('resetModal').style.display = 'none';
    }

    function generateModalRandomPassword() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#';
        let pass = '';
        for (let i = 0; i < 8; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('resetPasswordInput').value = pass;
    }
</script>
@endsection
