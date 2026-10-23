@extends('layouts.app')

@section('title', 'Pengaturan Absensi Guru')

@section('header-actions')
<div style="display:flex; align-items:center; gap:10px;">
    @if($lembagas->count() > 1)
    <div style="display:flex; align-items:center; gap:8px;">
        <select class="form-select" onchange="window.location.href='{{ route('pengaturan-absensi.index') }}?lembaga=' + this.value"
                style="padding:8px 14px; font-size:13px; font-weight:600; border-radius:10px; background:var(--bg-surface); color:var(--text-primary); border:1px solid var(--border-light);">
            @foreach($lembagas as $lem)
            <option value="{{ $lem->kode }}" {{ $currentLembaga->id === $lem->id ? 'selected' : '' }}>
                {{ $lem->nama_singkat }}
            </option>
            @endforeach
        </select>
    </div>
    @endif

    <a href="{{ route('presensi.kiosk') }}" target="_blank" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none; padding:8px 14px; font-size:13px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
            <line x1="8" y1="21" x2="16" y2="21"></line>
            <line x1="12" y1="17" x2="12" y2="21"></line>
        </svg>
        <span>Layar Kiosk QR</span>
    </a>
</div>
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:20px; max-width:1100px;">

    {{-- Alert Notifikasi --}}
    @if(session('success'))
    <div class="alert-banner alert-success" style="display:flex; align-items:center; gap:12px; padding:12px 18px; border-radius:12px; background:rgba(16, 185, 129, 0.1); border:1px solid rgba(16, 185, 129, 0.25); color:#065F46;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px; height:18px; flex-shrink:0; color:#10B981;">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span style="font-size:13.5px; font-weight:600;">{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="alert-banner" style="display:flex; align-items:center; gap:12px; padding:12px 18px; border-radius:12px; background:rgba(239, 68, 68, 0.1); border:1px solid rgba(239, 68, 68, 0.25); color:#991B1B;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px; height:18px; flex-shrink:0; color:#EF4444;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span style="font-size:13.5px; font-weight:600;">{{ $errors->first() }}</span>
    </div>
    @endif

    <form action="{{ route('pengaturan-absensi.update') }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="lembaga_id" value="{{ $currentLembaga->id }}">

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:20px; align-items:start;">

            {{-- Kolom Kiri: Waktu & Jam Kerja --}}
            <div class="form-section-card" style="padding:22px;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:20px; padding-bottom:14px; border-bottom:1px solid var(--border-subtle);">
                    <div style="width:36px; height:36px; border-radius:10px; background:rgba(0, 82, 255, 0.08); display:flex; align-items:center; justify-content:center; color:var(--electric-blue);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div>
                        <h2 style="font-size:16px; font-weight:700; color:var(--text-primary); margin:0;">Jam Masuk & Pulang</h2>
                        <span style="font-size:12.5px; color:var(--text-muted);">{{ $currentLembaga->nama }}</span>
                    </div>
                </div>

                <div style="display:flex; flex-direction:column; gap:18px;">
                    <div>
                        <label for="jam_masuk" style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">
                            Jam Masuk
                        </label>
                        <input type="time" id="jam_masuk" name="jam_masuk" class="form-control"
                               value="{{ old('jam_masuk', $pengaturan->jam_masuk ? substr($pengaturan->jam_masuk, 0, 5) : '07:00') }}"
                               required style="width:100%; padding:10px 14px; font-size:14px; border-radius:10px; border:1px solid var(--border-light); background:var(--bg-surface); color:var(--text-primary);">
                    </div>

                    <div>
                        <label for="toleransi_terlambat_menit" style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">
                            Toleransi Keterlambatan (Menit)
                        </label>
                        <div style="position:relative;">
                            <input type="number" id="toleransi_terlambat_menit" name="toleransi_terlambat_menit" min="0" max="180" class="form-control"
                                   value="{{ old('toleransi_terlambat_menit', $pengaturan->toleransi_terlambat_menit ?? 15) }}"
                                   required style="width:100%; padding:10px 14px; padding-right:60px; font-size:14px; border-radius:10px; border:1px solid var(--border-light); background:var(--bg-surface); color:var(--text-primary);">
                            <span style="position:absolute; right:14px; top:50%; transform:translateY(-50%); font-size:12.5px; font-weight:600; color:var(--text-muted);">menit</span>
                        </div>
                    </div>

                    <div>
                        <label for="jam_pulang" style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">
                            Jam Pulang
                        </label>
                        <input type="time" id="jam_pulang" name="jam_pulang" class="form-control"
                               value="{{ old('jam_pulang', $pengaturan->jam_pulang ? substr($pengaturan->jam_pulang, 0, 5) : '15:30') }}"
                               required style="width:100%; padding:10px 14px; font-size:14px; border-radius:10px; border:1px solid var(--border-light); background:var(--bg-surface); color:var(--text-primary);">
                    </div>

                    <div style="margin-top:10px; padding:14px; border-radius:10px; background:var(--bg-page); border:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between; gap:12px;">
                        <div>
                            <span style="font-size:13px; font-weight:600; color:var(--text-primary); display:block;">Kunci Token QR</span>
                            <span style="font-size:11.5px; color:var(--text-muted); font-family:monospace;">{{ substr($pengaturan->qr_secret_key ?: 'default', 0, 16) }}...</span>
                        </div>
                        <button type="submit" formaction="{{ route('pengaturan-absensi.regenerate-secret') }}" formmethod="POST" class="btn btn-secondary" style="font-size:12px; padding:6px 12px;">
                            Reset Kunci
                        </button>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Titik Koordinat & Radius Geofencing --}}
            <div class="form-section-card" style="padding:22px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:20px; padding-bottom:14px; border-bottom:1px solid var(--border-subtle);">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:36px; height:36px; border-radius:10px; background:rgba(16, 185, 129, 0.08); display:flex; align-items:center; justify-content:center; color:#10B981;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                        <div>
                            <h2 style="font-size:16px; font-weight:700; color:var(--text-primary); margin:0;">Titik Koordinat & Radius</h2>
                            <span style="font-size:12.5px; color:var(--text-muted);">Area Validasi Scan Guru</span>
                        </div>
                    </div>

                    <button type="button" id="btnGetLocation" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; font-size:12px; padding:6px 12px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <span>Deteksi Lokasi GPS</span>
                    </button>
                </div>

                <div style="display:flex; flex-direction:column; gap:16px;">
                    <div>
                        <label for="mode_lokasi" style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">
                            Validasi Lokasi (GPS)
                        </label>
                        <select name="mode_lokasi" id="mode_lokasi" class="form-select"
                                style="width:100%; padding:10px 14px; font-size:13.5px; border-radius:10px; border:1px solid var(--border-light); background:var(--bg-surface); color:var(--text-primary);">
                            <option value="opsional" {{ old('mode_lokasi', $pengaturan->mode_lokasi ?? 'opsional') === 'opsional' ? 'selected' : '' }}>
                                Opsional - Catat Jarak Saja (Rekomendasi: Bebas Kendala GPS di Dalam Ruangan)
                            </option>
                            <option value="wajib" {{ old('mode_lokasi', $pengaturan->mode_lokasi ?? 'opsional') === 'wajib' ? 'selected' : '' }}>
                                Wajib - Harus Dalam Radius (Ketat)
                            </option>
                            <option value="nonaktif" {{ old('mode_lokasi', $pengaturan->mode_lokasi ?? 'opsional') === 'nonaktif' ? 'selected' : '' }}>
                                Nonaktif - Cukup Scan Dynamic QR di Layar Kantor
                            </option>
                        </select>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">

                        <div>
                            <label for="latitude" style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">
                                Latitude
                            </label>
                            <input type="text" id="latitude" name="latitude" class="form-control"
                                   value="{{ old('latitude', $pengaturan->latitude ?? -7.327434) }}"
                                   required style="width:100%; padding:10px 14px; font-size:13.5px; font-family:monospace; border-radius:10px; border:1px solid var(--border-light); background:var(--bg-surface); color:var(--text-primary);">
                        </div>
                        <div>
                            <label for="longitude" style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">
                                Longitude
                            </label>
                            <input type="text" id="longitude" name="longitude" class="form-control"
                                   value="{{ old('longitude', $pengaturan->longitude ?? 108.220761) }}"
                                   required style="width:100%; padding:10px 14px; font-size:13.5px; font-family:monospace; border-radius:10px; border:1px solid var(--border-light); background:var(--bg-surface); color:var(--text-primary);">
                        </div>
                    </div>

                    <div>
                        <label for="radius_meter" style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">
                            Radius Toleransi Absensi (Meter)
                        </label>
                        <div style="position:relative;">
                            <input type="number" id="radius_meter" name="radius_meter" min="10" max="5000" class="form-control"
                                   value="{{ old('radius_meter', $pengaturan->radius_meter ?? 100) }}"
                                   required style="width:100%; padding:10px 14px; padding-right:60px; font-size:14px; border-radius:10px; border:1px solid var(--border-light); background:var(--bg-surface); color:var(--text-primary);">
                            <span style="position:absolute; right:14px; top:50%; transform:translateY(-50%); font-size:12.5px; font-weight:600; color:var(--text-muted);">meter</span>
                        </div>
                    </div>

                    {{-- Interaktif Map Container --}}
                    <div>
                        <div id="map" style="height:220px; width:100%; border-radius:12px; border:1px solid var(--border-light); z-index:1;"></div>
                        <span id="mapHelpText" style="display:block; font-size:11.5px; color:var(--text-muted); margin-top:6px; text-align:right;">
                            Klik pada peta untuk mengubah posisi koordinat
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:12px;">
            <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:10px 24px; font-size:14px; font-weight:600;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>Simpan Pengaturan</span>
            </button>
        </div>
    </form>
</div>

{{-- Leaflet Map Scripts --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var latInput = document.getElementById('latitude');
    var lngInput = document.getElementById('longitude');
    var radiusInput = document.getElementById('radius_meter');
    var btnGetLocation = document.getElementById('btnGetLocation');

    var currentLat = parseFloat(latInput.value) || -7.327434;
    var currentLng = parseFloat(lngInput.value) || 108.220761;
    var currentRadius = parseInt(radiusInput.value) || 100;

    // Inisialisasi Leaflet Map
    var map = L.map('map').setView([currentLat, currentLng], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    var marker = L.marker([currentLat, currentLng], { draggable: true }).addTo(map);
    var circle = L.circle([currentLat, currentLng], {
        color: '#0052FF',
        fillColor: '#0052FF',
        fillOpacity: 0.15,
        radius: currentRadius
    }).addTo(map);

    function updateCoordinates(lat, lng) {
        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);
        marker.setLatLng([lat, lng]);
        circle.setLatLng([lat, lng]);
    }

    marker.on('dragend', function(e) {
        var pos = marker.getLatLng();
        updateCoordinates(pos.lat, pos.lng);
    });

    map.on('click', function(e) {
        updateCoordinates(e.latlng.lat, e.latlng.lng);
    });

    radiusInput.addEventListener('input', function() {
        var rad = parseInt(this.value) || 50;
        circle.setRadius(rad);
    });

    latInput.addEventListener('change', function() {
        var lat = parseFloat(this.value);
        var lng = parseFloat(lngInput.value);
        if (!isNaN(lat) && !isNaN(lng)) {
            updateCoordinates(lat, lng);
            map.panTo([lat, lng]);
        }
    });

    lngInput.addEventListener('change', function() {
        var lat = parseFloat(latInput.value);
        var lng = parseFloat(this.value);
        if (!isNaN(lat) && !isNaN(lng)) {
            updateCoordinates(lat, lng);
            map.panTo([lat, lng]);
        }
    });

    // Deteksi Geolocation GPS Saat Ini
    if (btnGetLocation) {
        btnGetLocation.addEventListener('click', function() {
            if (!navigator.geolocation) {
                alert('Browser Anda tidak mendukung deteksi lokasi (GPS).');
                return;
            }

            btnGetLocation.disabled = true;
            btnGetLocation.textContent = 'Mendeteksi...';

            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    var lat = pos.coords.latitude;
                    var lng = pos.coords.longitude;
                    updateCoordinates(lat, lng);
                    map.setView([lat, lng], 17);
                    btnGetLocation.disabled = false;
                    btnGetLocation.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="3"></circle></svg><span>Deteksi Lokasi GPS</span>';
                },
                function(err) {
                    alert('Gagal mengambil lokasi: ' + err.message);
                    btnGetLocation.disabled = false;
                    btnGetLocation.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="3"></circle></svg><span>Deteksi Lokasi GPS</span>';
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        });
    }

    // Fix map resize render
    setTimeout(function() {
        map.invalidateSize();
    }, 300);
});
</script>
@endsection
