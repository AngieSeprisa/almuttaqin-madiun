@extends('layouts.app')

@section('title', 'Tambah Pengguna')

@section('header-actions')
<a href="{{ route('pengguna.index') }}" class="btn btn-secondary">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>
    <span>Kembali ke Daftar</span>
</a>
@endsection

@section('content')
<div style="width: 100%; display: flex; flex-direction: column; gap: 20px;">

    @if ($errors->any())
    <div class="alert-banner alert-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
            <strong>Gagal menyimpan pengguna:</strong>
            <ul style="margin: 4px 0 0 16px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <form action="{{ route('pengguna.store') }}" method="POST" style="display:flex; flex-direction:column; gap:20px;">
        @csrf

        {{-- Seksi Informasi Akun --}}
        <div class="form-section-card">
            <div class="form-section-header">
                <div class="form-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div class="form-section-title-wrap">
                    <h2 class="form-section-title">Informasi Pengguna</h2>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
                <div class="login-field">
                    <label for="name" class="login-label">Nama Lengkap <span style="color:#EF4444;">*</span></label>
                    <input type="text" name="name" id="name" class="wizard-input" value="{{ old('name') }}" placeholder="Contoh: Ustadz Ahmad, S.Pd." required>
                </div>

                <div class="login-field">
                    <label for="email" class="login-label">Alamat Email <span style="color:#EF4444;">*</span></label>
                    <input type="email" name="email" id="email" class="wizard-input" value="{{ old('email') }}" placeholder="nama@almuttaqin.id" required>
                </div>

                <div class="login-field">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                        <label for="password" class="login-label" style="margin-bottom:0;">Kata Sandi Awal <span style="color:#EF4444;">*</span></label>
                        <div style="display:flex; gap:6px;">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="setDefaultPassword('Muttaqin2026!')" style="font-size:11px; padding:2px 8px;">
                                Sandi Default
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="generateRandomPassword()" style="font-size:11px; padding:2px 8px;">
                                Acak Sandi
                            </button>
                        </div>
                    </div>
                    <input type="text" name="password" id="password" class="wizard-input" value="{{ old('password', 'Muttaqin2026!') }}" placeholder="Minimal 6 karakter" required>
                    <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; font-size:12px; font-weight:500; color:var(--text-secondary); margin-top:6px;">
                        <input type="checkbox" name="must_change_password" value="1" {{ old('must_change_password', '1') == '1' ? 'checked' : '' }} style="accent-color:var(--electric-blue); width:14px; height:14px;">
                        Wajibkan guru/pengguna ganti kata sandi saat login pertama
                    </label>
                </div>

                <div class="login-field">
                    <label for="role" class="login-label">Peran Sistem <span style="color:#EF4444;">*</span></label>
                    <select name="role" id="role" class="wizard-select" required onchange="handleRoleChange(this.value)">
                        <option value="guru" {{ old('role', 'guru') === 'guru' ? 'selected' : '' }}>Guru / Pengajar</option>
                        <option value="admin_lembaga" {{ old('role') === 'admin_lembaga' ? 'selected' : '' }}>Admin Lembaga</option>
                        <option value="yayasan" {{ old('role') === 'yayasan' ? 'selected' : '' }}>Yayasan / Super Admin</option>
                    </select>
                </div>

                <div class="login-field" style="grid-column: 1 / -1;">
                    <label for="jabatan" class="login-label">Jabatan / Amanah Tugas</label>
                    <input type="text" name="jabatan" id="jabatan" class="wizard-input" value="{{ old('jabatan') }}" placeholder="Contoh: Guru PAI & Pembina Asrama">
                </div>
            </div>

            <div style="margin-top:16px; padding-top:14px; border-top:1px solid var(--border-light);">
                <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:13.5px; font-weight:500;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} style="accent-color:var(--electric-blue); width:16px; height:16px;">
                    Akun Aktif (Dapat masuk ke sistem)
                </label>
            </div>
        </div>

        {{-- Seksi Unit Lembaga (Ruang Kerja) --}}
        <div class="form-section-card">
            <div class="form-section-header">
                <div class="form-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </div>
                <div class="form-section-title-wrap">
                    <h2 class="form-section-title">Akses Ruang Kerja (Lembaga)</h2>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap:12px;">
                @foreach($lembagas as $lem)
                <label style="display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:10px; border:1px solid var(--border-light); background:var(--bg-surface); cursor:pointer;">
                    <input type="checkbox" name="lembagas[]" value="{{ $lem->id }}" 
                           {{ in_array($lem->id, old('lembagas', [])) ? 'checked' : '' }}
                           class="permission-checkbox js-lembaga-check">
                    <span style="font-size:13.5px; font-weight:600; color:var(--text-primary);">{{ $lem->nama_singkat }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Seksi Matriks Hak Akses Menu --}}
        <div class="form-section-card">
            <div class="form-section-header" style="justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <div style="display:flex; align-items:center; gap:14px;">
                    <div class="form-section-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <div class="form-section-title-wrap">
                        <h2 class="form-section-title">Matriks Hak Akses Menu</h2>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="applyPreset('guru')" style="font-size:12px; padding:6px 12px;">
                        Template Guru
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="applyPreset('admin')" style="font-size:12px; padding:6px 12px;">
                        Template Admin
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" id="btnToggleAllPerms" style="font-size:12px; padding:6px 12px;">
                        Centang Semua
                    </button>
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table class="permission-table">
                    <thead>
                        <tr>
                            <th style="width:36%;">Menu</th>
                            <th>Lihat (View)</th>
                            <th>Tambah (Create)</th>
                            <th>Ubah (Edit)</th>
                            <th>Hapus (Delete)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($menus as $code => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>
                                <input type="checkbox" name="permissions[{{ $code }}][view]" value="1" 
                                       {{ old("permissions.{$code}.view") ? 'checked' : '' }}
                                       class="permission-checkbox js-perm-check">
                            </td>
                            <td>
                                <input type="checkbox" name="permissions[{{ $code }}][create]" value="1"
                                       {{ old("permissions.{$code}.create") ? 'checked' : '' }}
                                       class="permission-checkbox js-perm-check">
                            </td>
                            <td>
                                <input type="checkbox" name="permissions[{{ $code }}][edit]" value="1"
                                       {{ old("permissions.{$code}.edit") ? 'checked' : '' }}
                                       class="permission-checkbox js-perm-check">
                            </td>
                            <td>
                                <input type="checkbox" name="permissions[{{ $code }}][delete]" value="1"
                                       {{ old("permissions.{$code}.delete") ? 'checked' : '' }}
                                       class="permission-checkbox js-perm-check">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tombol Aksi --}}
        <div style="display:flex; align-items:center; justify-content:flex-end; gap:12px;">
            <a href="{{ route('pengguna.index') }}" class="btn btn-secondary" style="text-decoration:none;">
                Batal
            </a>
            <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
                Simpan Pengguna
            </button>
        </div>
    </form>

</div>

<script>
    function setDefaultPassword(val) {
        const input = document.getElementById('password');
        if (input) input.value = val;
    }

    function generateRandomPassword() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#';
        let pass = '';
        for (let i = 0; i < 8; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        setDefaultPassword(pass);
    }

    function applyPreset(type) {
        const perms = {
            guru: {
                siswa: ['view'],
                pendaftaran: [],
                perizinan_sambang: ['view', 'create', 'edit'],
                perizinan_sakit: ['view', 'create', 'edit'],
                pengguna: []
            },
            admin: {
                siswa: ['view', 'create', 'edit'],
                pendaftaran: ['view', 'create', 'edit'],
                perizinan_sambang: ['view', 'create', 'edit'],
                perizinan_sakit: ['view', 'create', 'edit'],
                pengguna: ['view']
            }
        };

        const config = perms[type];
        if (!config) return;

        document.querySelectorAll('.js-perm-check').forEach(function(cb) {
            cb.checked = false;
        });

        for (const [menu, actions] of Object.entries(config)) {
            actions.forEach(function(action) {
                const selector = `input[name="permissions[${menu}][${action}]"]`;
                const cb = document.querySelector(selector);
                if (cb) cb.checked = true;
            });
        }
    }

    function handleRoleChange(role) {
        if (role === 'guru') {
            applyPreset('guru');
        } else if (role === 'admin_lembaga') {
            applyPreset('admin');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var btnToggle = document.getElementById('btnToggleAllPerms');
        var checkboxes = document.querySelectorAll('.js-perm-check');
        var isAllChecked = false;

        if (btnToggle) {
            btnToggle.addEventListener('click', function() {
                isAllChecked = !isAllChecked;
                checkboxes.forEach(function(cb) {
                    cb.checked = isAllChecked;
                });
                btnToggle.textContent = isAllChecked ? 'Hapus Semua' : 'Centang Semua';
            });
        }

        // Auto apply guru preset on first load if creating new user
        var roleSelect = document.getElementById('role');
        if (roleSelect && roleSelect.value === 'guru' && !document.querySelector('.js-perm-check:checked')) {
            applyPreset('guru');
        }
    });
</script>
@endsection
