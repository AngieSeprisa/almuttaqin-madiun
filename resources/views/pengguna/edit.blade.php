@extends('layouts.app')

@section('title', 'Edit Pengguna & Hak Akses')

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
            <strong>Gagal memperbarui pengguna:</strong>
            <ul style="margin: 4px 0 0 16px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <form action="{{ route('pengguna.update', $pengguna->id) }}" method="POST" style="display:flex; flex-direction:column; gap:20px;">
        @csrf
        @method('PUT')

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
                    <input type="text" name="name" id="name" class="wizard-input" value="{{ old('name', $pengguna->name) }}" required>
                </div>

                <div class="login-field">
                    <label for="email" class="login-label">Alamat Email <span style="color:#EF4444;">*</span></label>
                    <input type="email" name="email" id="email" class="wizard-input" value="{{ old('email', $pengguna->email) }}" required>
                </div>

                <div class="login-field">
                    <label for="password" class="login-label">Kata Sandi Baru</label>
                    <input type="password" name="password" id="password" class="wizard-input" placeholder="Kosongkan jika tidak diubah">
                </div>

                <div class="login-field">
                    <label for="role" class="login-label">Peran Sistem <span style="color:#EF4444;">*</span></label>
                    <select name="role" id="role" class="wizard-select" required>
                        <option value="guru" {{ old('role', $pengguna->role) === 'guru' ? 'selected' : '' }}>Guru / Pengajar</option>
                        <option value="admin_lembaga" {{ old('role', $pengguna->role) === 'admin_lembaga' ? 'selected' : '' }}>Admin Lembaga</option>
                        <option value="yayasan" {{ old('role', $pengguna->role) === 'yayasan' ? 'selected' : '' }}>Yayasan / Super Admin</option>
                    </select>
                </div>

                <div class="login-field" style="grid-column: 1 / -1;">
                    <label for="jabatan" class="login-label">Jabatan / Tugas</label>
                    <input type="text" name="jabatan" id="jabatan" class="wizard-input" value="{{ old('jabatan', $pengguna->jabatan) }}" placeholder="Contoh: Guru Matematika SMP & Pengasuh Pondok">
                </div>
            </div>

            <div style="margin-top:16px; padding-top:14px; border-top:1px solid var(--border-light);">
                <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:13.5px; font-weight:500;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $pengguna->is_active ? '1' : '0') == '1' ? 'checked' : '' }} style="accent-color:var(--electric-blue); width:16px; height:16px;">
                    Akun Aktif
                </label>
            </div>
        </div>

        {{-- Seksi Unit Lembaga --}}
        <div class="form-section-card">
            <div class="form-section-header">
                <div class="form-section-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </div>
                <div class="form-section-title-wrap">
                    <h2 class="form-section-title">Akses Ruang Kerja</h2>
                </div>
            </div>

            @php
                $assignedLembagas = old('lembagas', $pengguna->lembagas->pluck('id')->toArray());
            @endphp
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap:12px;">
                @foreach($lembagas as $lem)
                <label style="display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:10px; border:1px solid var(--border-light); background:var(--bg-surface); cursor:pointer;">
                    <input type="checkbox" name="lembagas[]" value="{{ $lem->id }}" 
                           {{ in_array($lem->id, $assignedLembagas) ? 'checked' : '' }}
                           class="permission-checkbox">
                    <span style="font-size:13.5px; font-weight:600; color:var(--text-primary);">{{ $lem->nama_singkat }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Seksi Matriks Hak Akses Menu --}}
        <div class="form-section-card">
            <div class="form-section-header" style="justify-content:space-between; align-items:center;">
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
                <button type="button" class="btn btn-sm btn-secondary" id="btnToggleAllPerms" style="font-size:12px; padding:6px 12px;">
                    Centang Semua
                </button>
            </div>

            <div style="overflow-x:auto;">
                <table class="permission-table">
                    <thead>
                        <tr>
                            <th style="width:40%;">Menu</th>
                            <th style="text-align:center;">Lihat</th>
                            <th style="text-align:center;">Tambah</th>
                            <th style="text-align:center;">Ubah</th>
                            <th style="text-align:center;">Hapus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $groupsToRender = $menuGroups ?? ['Menu' => $menus];
                        @endphp
                        @foreach($groupsToRender as $groupName => $groupItems)
                        <tr style="background:var(--bg-page);">
                            <td colspan="4" style="font-weight:700; font-size:12.5px; color:var(--text-primary); text-transform:uppercase; letter-spacing:0.04em; padding:10px 14px;">
                                {{ $groupName }}
                            </td>
                            <td style="text-align:center; padding:8px 14px;">
                                <button type="button" class="btn btn-sm btn-secondary js-toggle-group" data-group="{{ \Illuminate\Support\Str::slug($groupName) }}" style="font-size:11px; padding:3px 10px;">
                                    Pilih Grup
                                </button>
                            </td>
                        </tr>
                        @foreach($groupItems as $code => $label)
                        @php
                            $pView   = old("permissions.{$code}.view", $userPerms[$code]['view'] ?? false);
                            $pCreate = old("permissions.{$code}.create", $userPerms[$code]['create'] ?? false);
                            $pEdit   = old("permissions.{$code}.edit", $userPerms[$code]['edit'] ?? false);
                            $pDelete = old("permissions.{$code}.delete", $userPerms[$code]['delete'] ?? false);
                            $groupSlug = \Illuminate\Support\Str::slug($groupName);
                        @endphp
                        <tr>
                            <td style="padding-left:26px; font-weight:500;">{{ $label }}</td>
                            <td style="text-align:center;">
                                <input type="checkbox" name="permissions[{{ $code }}][view]" value="1" 
                                       {{ $pView ? 'checked' : '' }}
                                       data-group="{{ $groupSlug }}"
                                       data-code="{{ $code }}"
                                       data-action="view"
                                       class="permission-checkbox js-perm-check">
                            </td>
                            <td style="text-align:center;">
                                <input type="checkbox" name="permissions[{{ $code }}][create]" value="1"
                                       {{ $pCreate ? 'checked' : '' }}
                                       data-group="{{ $groupSlug }}"
                                       data-code="{{ $code }}"
                                       data-action="create"
                                       class="permission-checkbox js-perm-check">
                            </td>
                            <td style="text-align:center;">
                                <input type="checkbox" name="permissions[{{ $code }}][edit]" value="1"
                                       {{ $pEdit ? 'checked' : '' }}
                                       data-group="{{ $groupSlug }}"
                                       data-code="{{ $code }}"
                                       data-action="edit"
                                       class="permission-checkbox js-perm-check">
                            </td>
                            <td style="text-align:center;">
                                <input type="checkbox" name="permissions[{{ $code }}][delete]" value="1"
                                       {{ $pDelete ? 'checked' : '' }}
                                       data-group="{{ $groupSlug }}"
                                       data-code="{{ $code }}"
                                       data-action="delete"
                                       class="permission-checkbox js-perm-check">
                            </td>
                        </tr>
                        @endforeach
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
                Simpan Perubahan
            </button>
        </div>
    </form>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var btnToggle = document.getElementById('btnToggleAllPerms');
        var checkboxes = document.querySelectorAll('.js-perm-check');

        function syncToggleAllText() {
            if (!btnToggle || checkboxes.length === 0) return;
            var allChecked = Array.from(checkboxes).every(function(cb) { return cb.checked; });
            btnToggle.textContent = allChecked ? 'Hapus Semua' : 'Centang Semua';
        }

        if (btnToggle) {
            syncToggleAllText();
            btnToggle.addEventListener('click', function() {
                var allChecked = Array.from(checkboxes).every(function(cb) { return cb.checked; });
                var nextState = !allChecked;
                checkboxes.forEach(function(cb) {
                    cb.checked = nextState;
                });
                syncToggleAllText();
            });
        }

        document.querySelectorAll('.js-toggle-group').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var group = btn.getAttribute('data-group');
                var groupCheckboxes = document.querySelectorAll('.js-perm-check[data-group="' + group + '"]');
                var allGroupChecked = Array.from(groupCheckboxes).every(function(cb) { return cb.checked; });
                var nextState = !allGroupChecked;
                groupCheckboxes.forEach(function(cb) {
                    cb.checked = nextState;
                });
                syncToggleAllText();
            });
        });

        // Saat memilih Tambah/Ubah/Hapus pada suatu submenu, otomatis pastikan Lihat ikut tercentang
        checkboxes.forEach(function(cb) {
            cb.addEventListener('change', function() {
                var code = cb.getAttribute('data-code');
                var action = cb.getAttribute('data-action');
                if (cb.checked && action !== 'view' && code) {
                    var viewCb = document.querySelector('.js-perm-check[data-code="' + code + '"][data-action="view"]');
                    if (viewCb) viewCb.checked = true;
                }
                syncToggleAllText();
            });
        });
    });
</script>
@endsection
