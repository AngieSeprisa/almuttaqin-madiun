@extends('layouts.app')

@section('title', 'Presensi Guru')

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px;">
    @if(auth()->user()->isYayasan() || auth()->user()->role === 'admin_lembaga' || auth()->user()->canDo('pengaturan_absensi', 'view'))
    <a href="{{ route('presensi.kiosk') }}" target="_blank" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none; padding:8px 14px; font-size:13px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
            <line x1="8" y1="21" x2="16" y2="21"></line>
            <line x1="12" y1="17" x2="12" y2="21"></line>
        </svg>
        <span>Buka Layar Kiosk QR</span>
    </a>
    @endif

    <button type="button" id="btnBukaIzin" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; padding:8px 14px; font-size:13px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <line x1="12" y1="11" x2="12" y2="17"></line>
            <line x1="9" y1="14" x2="15" y2="14"></line>
        </svg>
        <span>Form Izin / Sakit</span>
    </button>
</div>
@endsection

@section('content')
<div style="display:flex; flex-direction:column; gap:20px; max-width:960px; margin:0 auto;">

    {{-- Status Kartu Hari Ini & Live Clock --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
        
        {{-- Card Status Presensi Masuk & Pulang --}}
        <div class="form-section-card" style="padding:20px; display:flex; flex-direction:column; justify-content:space-between;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:14px;">
                <div>
                    <span style="font-size:12px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Presensi Hari Ini</span>
                    <h2 style="font-size:17px; font-weight:800; color:var(--text-primary); margin:2px 0 0 0;">
                        {{ Carbon\Carbon::today()->translatedFormat('l, d F Y') }}
                    </h2>
                </div>
                <div id="liveClock" style="font-size:16px; font-weight:800; color:var(--electric-blue); font-family:monospace; background:var(--bg-page); padding:4px 10px; border-radius:8px; border:1px solid var(--border-light);">
                    --:--:--
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:8px;">
                <div style="background:var(--bg-page); border:1px solid var(--border-subtle); border-radius:12px; padding:12px 14px;">
                    <span style="font-size:11.5px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">JAM MASUK</span>
                    <div id="displayJamMasuk" style="font-size:18px; font-weight:800; color:var(--text-primary); font-family:monospace;">
                        {{ $presensiHariIni && $presensiHariIni->jam_masuk ? substr($presensiHariIni->jam_masuk, 0, 5) : '--:--' }}
                    </div>
                    @if($presensiHariIni && $presensiHariIni->status)
                    <span id="displayStatusMasuk" style="font-size:11px; font-weight:700; color:{{ $presensiHariIni->status === 'hadir' ? '#059669' : ($presensiHariIni->status === 'terlambat' ? '#D97706' : '#2563EB') }}; display:inline-block; margin-top:2px;">
                        {{ $presensiHariIni->status_label }}
                    </span>
                    @endif
                </div>

                <div style="background:var(--bg-page); border:1px solid var(--border-subtle); border-radius:12px; padding:12px 14px;">
                    <span style="font-size:11.5px; font-weight:600; color:var(--text-muted); display:block; margin-bottom:4px;">JAM PULANG</span>
                    <div id="displayJamPulang" style="font-size:18px; font-weight:800; color:var(--text-primary); font-family:monospace;">
                        {{ $presensiHariIni && $presensiHariIni->jam_pulang ? substr($presensiHariIni->jam_pulang, 0, 5) : '--:--' }}
                    </div>
                    <span id="displayStatusPulang" style="font-size:11px; font-weight:600; color:var(--text-muted); display:inline-block; margin-top:2px;">
                        {{ $presensiHariIni && $presensiHariIni->jam_pulang ? 'Sudah Pulang' : 'Belum Pulang' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Card Deteksi Lokasi GPS --}}
        <div class="form-section-card" style="padding:20px; display:flex; flex-direction:column; justify-content:space-between;">
            <div>
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                    <span style="font-size:12px; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px;">Validasi Lokasi</span>
                    <button type="button" id="btnRefreshGps" style="background:none; border:none; cursor:pointer; color:var(--electric-blue); font-size:12px; font-weight:600; padding:2px;">
                        Perbarui GPS
                    </button>
                </div>
                <div id="gpsStatusCard" style="display:flex; align-items:center; gap:12px; padding:14px; border-radius:12px; background:rgba(0, 82, 255, 0.05); border:1px solid rgba(0, 82, 255, 0.15);">
                    <div id="gpsIconWrap" style="width:36px; height:36px; border-radius:50%; background:rgba(0,82,255,0.1); color:var(--electric-blue); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </div>
                    <div>
                        <div id="gpsStatusText" style="font-size:13.5px; font-weight:700; color:var(--text-primary);">
                            Mendeteksi Koordinat GPS...
                        </div>
                        <div id="gpsDistanceText" style="font-size:12px; color:var(--text-muted); margin-top:2px;">
                            Radius maksimum: {{ $pengaturan->radius_meter }} meter
                        </div>
                    </div>
                </div>
            </div>

            <div style="font-size:12px; color:var(--text-secondary); margin-top:10px; display:flex; align-items:center; gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;color:var(--text-muted);">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                <span>Lokasi: <strong>{{ $currentLembaga->nama }}</strong></span>
            </div>
        </div>
    </div>

    {{-- Scanner Kamera QR Code --}}
    <div class="form-section-card" style="padding:22px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:36px; height:36px; border-radius:10px; background:rgba(0,82,255,0.08); display:flex; align-items:center; justify-content:center; color:var(--electric-blue);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                </div>
                <div>
                    <h3 style="font-size:16px; font-weight:700; color:var(--text-primary); margin:0;">Pemindai Kamera</h3>
                    <span style="font-size:12px; color:var(--text-muted);">Arahkan kamera ponsel Anda ke QR Code di layar kantor</span>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:8px;">
                <input type="file" id="qrFileInput" accept="image/*" capture="environment" style="display:none;">
                <button type="button" id="btnUploadQr" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; font-size:13px;" title="Ambil foto QR dari kamera HP">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                    <span>Foto QR</span>
                </button>

                <button type="button" id="btnToggleCamera" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:8px 16px; font-size:13px; font-weight:600;">
                    <svg id="camIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                    <span id="camBtnText">Buka Kamera</span>
                </button>
            </div>
        </div>

        {{-- Area Render Scanner --}}
        <div id="scannerWrapper" style="position:relative; width:100%; max-width:440px; margin:0 auto; border-radius:16px; overflow:hidden; background:#0B0F17; min-height:280px; display:flex; flex-direction:column; align-items:center; justify-content:center; border:2px dashed var(--border-light);">
            <div id="qrReader" style="width:100%;"></div>

            {{-- Placeholder Sebelum Kamera Dibuka --}}
            <div id="scannerPlaceholder" style="padding:36px 20px; text-align:center; color:#94A3B8;">
                <div style="width:58px; height:58px; border-radius:50%; background:rgba(255,255,255,0.06); margin:0 auto 12px auto; display:flex; align-items:center; justify-content:center; color:#E2E8F0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:30px;height:30px;">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                </div>
                <div style="font-size:14px; font-weight:600; color:#F1F5F9; margin-bottom:4px;">Kamera Siap Digunakan</div>
                <div style="font-size:12.5px; color:#94A3B8; max-width:280px; margin:0 auto;">Tekan "Buka Kamera" atau "Foto QR" untuk memindai QR Code di layar kantor</div>
                <div style="font-size:11.5px; color:#94A3B8; max-width:320px; margin:12px auto 0 auto; padding:8px 12px; border-radius:8px; background:rgba(255,255,255,0.05); line-height:1.4;">
                    <span style="color:#60A5FA; font-weight:700;">Tips iPhone & Android:</span> Bisa langsung arahkan <strong>aplikasi Kamera bawaan HP</strong> ke layar monitor kantor.
                </div>
            </div>


            {{-- Scanning Frame Overlay (tampil saat kamera aktif) --}}
            <div id="scanningOverlay" style="display:none; position:absolute; inset:0; pointer-events:none; border:2px solid rgba(0,82,255,0.7); box-shadow:inset 0 0 20px rgba(0,82,255,0.3); border-radius:16px;"></div>
        </div>

        {{-- Switch Camera Option (Mobile) --}}
        <div id="cameraControls" style="display:none; margin-top:14px; text-align:center;">
            <button type="button" id="btnSwitchCamera" class="btn btn-secondary" style="font-size:12.5px; padding:6px 14px; display:inline-flex; align-items:center; gap:6px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                </svg>
                <span>Ganti Kamera</span>
            </button>
        </div>
    </div>

    {{-- Riwayat Presensi Pribadi (7 Hari Terakhir) --}}
    <div class="form-section-card" style="padding:22px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
            <h3 style="font-size:16px; font-weight:700; color:var(--text-primary); margin:0;">Riwayat Presensi Saya</h3>
            <span style="font-size:12.5px; color:var(--text-muted);">7 Hari Terakhir</span>
        </div>

        <div class="table-container" style="overflow-x:auto;">
            <table class="table" style="width:100%; border-collapse:collapse; text-align:left;">
                <thead>
                    <tr style="border-bottom:1px solid var(--border-light);">
                        <th style="padding:10px 14px; font-size:12px; font-weight:700; color:var(--text-secondary);">Tanggal</th>
                        <th style="padding:10px 14px; font-size:12px; font-weight:700; color:var(--text-secondary);">Masuk</th>
                        <th style="padding:10px 14px; font-size:12px; font-weight:700; color:var(--text-secondary);">Pulang</th>
                        <th style="padding:10px 14px; font-size:12px; font-weight:700; color:var(--text-secondary);">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($riwayatPribadi as $row)
                    <tr style="border-bottom:1px solid var(--border-subtle);">
                        <td style="padding:12px 14px; font-size:13px; font-weight:600; color:var(--text-primary);">
                            {{ $row->tanggal ? $row->tanggal->translatedFormat('d M Y') : '-' }}
                        </td>
                        <td style="padding:12px 14px; font-size:13px; font-family:monospace; color:var(--text-primary);">
                            {{ $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '-' }}
                        </td>
                        <td style="padding:12px 14px; font-size:13px; font-family:monospace; color:var(--text-primary);">
                            {{ $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '-' }}
                        </td>
                        <td style="padding:12px 14px;">
                            @php
                                $badgeStyle = match($row->status) {
                                    'hadir'     => 'background:rgba(16, 185, 129, 0.12); color:#059669;',
                                    'terlambat' => 'background:rgba(245, 158, 11, 0.12); color:#D97706;',
                                    'izin'      => 'background:rgba(37, 99, 235, 0.12); color:#2563EB;',
                                    'sakit'     => 'background:rgba(124, 58, 237, 0.12); color:#7C3AED;',
                                    default     => 'background:rgba(239, 68, 68, 0.12); color:#DC2626;',
                                };
                            @endphp
                            <span style="display:inline-block; padding:3px 8px; border-radius:12px; font-size:11.5px; font-weight:700; {{ $badgeStyle }}">
                                {{ $row->status_label }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="padding:20px; text-align:center; font-size:13px; color:var(--text-muted);">
                            Belum ada riwayat presensi tercatat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Pengajuan Izin / Sakit Mandiri --}}
<div id="modalIzin" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div style="background:var(--bg-surface); border-radius:16px; border:1px solid var(--border-light); width:100%; max-width:440px; box-shadow:0 20px 40px rgba(0,0,0,0.2); overflow:hidden;">
        <div style="padding:18px 22px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between;">
            <div>
                <h3 style="font-size:16px; font-weight:700; color:var(--text-primary); margin:0;">Form Izin / Sakit</h3>
                <span style="font-size:12.5px; color:var(--text-muted); display:block; margin-top:2px;">Kirim pengajuan ketidakhadiran</span>
            </div>
            <button type="button" id="btnCloseModalIzin" style="background:none; border:none; cursor:pointer; color:var(--text-muted); padding:4px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <form action="{{ route('presensi.izin') }}" method="POST" enctype="multipart/form-data" style="padding:22px; display:flex; flex-direction:column; gap:16px;">
            @csrf
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Jenis Ketidakhadiran</label>
                <select name="status" class="form-select" required
                        style="width:100%; padding:9px 12px; font-size:13.5px; border-radius:10px; border:1px solid var(--border-light); background:var(--bg-surface); color:var(--text-primary);">
                    <option value="izin">Izin (Keperluan Pribadi / Dinas Luar)</option>
                    <option value="sakit">Sakit</option>
                </select>
            </div>

            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Keterangan Alasan</label>
                <textarea name="keterangan" rows="3" required placeholder="Tuliskan alasan ketidakhadiran secara singkat..."
                          style="width:100%; padding:9px 12px; font-size:13px; border-radius:10px; border:1px solid var(--border-light); background:var(--bg-surface); color:var(--text-primary); resize:vertical;"></textarea>
            </div>

            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Lampiran / Surat Dokter (Opsional)</label>
                <input type="file" name="dokumen_pendukung" accept="image/*,.pdf"
                       style="width:100%; font-size:12.5px; color:var(--text-secondary);">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:8px;">
                <button type="button" id="btnCancelIzin" class="btn btn-secondary" style="padding:8px 16px; font-size:13px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding:8px 20px; font-size:13px; font-weight:600;">Kirim Pengajuan</button>
            </div>
        </form>
    </div>
</div>

{{-- HTML5-QRCode Scanner Library --}}
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Live Digital Clock
    var clockEl = document.getElementById('liveClock');
    function updateClock() {
        var now = new Date();
        var h = String(now.getHours()).padStart(2, '0');
        var m = String(now.getMinutes()).padStart(2, '0');
        var s = String(now.getSeconds()).padStart(2, '0');
        if (clockEl) clockEl.textContent = h + ':' + m + ':' + s;
    }
    updateClock();
    setInterval(updateClock, 1000);

    // 2. Geolocation Tracking
    var userLat = null;
    var userLng = null;
    var gpsStatusText = document.getElementById('gpsStatusText');
    var gpsDistanceText = document.getElementById('gpsDistanceText');
    var gpsIconWrap = document.getElementById('gpsIconWrap');
    var schoolLat = {{ $pengaturan->latitude ?? -7.327434 }};
    var schoolLng = {{ $pengaturan->longitude ?? 108.220761 }};
    var schoolRadius = {{ $pengaturan->radius_meter ?? 100 }};

    function calculateDistance(lat1, lon1, lat2, lon2) {
        var R = 6371000; // Radius bumi dalam meter
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLon = (lon2 - lon1) * Math.PI / 180;
        var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon/2) * Math.sin(dLon/2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return Math.round(R * c);
    }

    var modeLokasi = '{{ $pengaturan->mode_lokasi ?? "opsional" }}';
    var perluKunci = {{ $pengaturan->perluKunciRadius() ? 'true' : 'false' }};

    function detectLocation() {
        if (modeLokasi === 'nonaktif') {
            gpsStatusText.textContent = 'Mode Dynamic QR Aktif';
            gpsStatusText.style.color = '#0052FF';
            gpsDistanceText.textContent = 'Presensi diverifikasi via monitor kantor (Bebas kendala GPS)';
            gpsIconWrap.style.background = 'rgba(0,82,255,0.15)';
            gpsIconWrap.style.color = '#0052FF';
            return;
        }

        if (!navigator.geolocation) {
            if (perluKunci) {
                gpsStatusText.textContent = 'GPS tidak didukung browser';
                gpsStatusText.style.color = '#DC2626';
            } else {
                gpsStatusText.textContent = 'Siap Scan • Dynamic QR';
                gpsStatusText.style.color = '#0052FF';
                gpsDistanceText.textContent = 'Arahkan kamera langsung ke QR Code di layar kantor';
            }
            return;
        }

        gpsStatusText.textContent = 'Mendeteksi koordinat GPS...';

        navigator.geolocation.getCurrentPosition(
            function(pos) {
                userLat = pos.coords.latitude;
                userLng = pos.coords.longitude;
                var distance = calculateDistance(userLat, userLng, schoolLat, schoolLng);

                if (distance <= schoolRadius) {
                    gpsStatusText.textContent = 'Dalam Radius (' + distance + 'm)';
                    gpsStatusText.style.color = '#059669';
                    gpsDistanceText.textContent = 'Terverifikasi dalam area sekolah (Maks: ' + schoolRadius + 'm)';
                    gpsIconWrap.style.background = 'rgba(16,185,129,0.15)';
                    gpsIconWrap.style.color = '#10B981';
                } else if (perluKunci) {
                    gpsStatusText.textContent = 'Di Luar Radius (' + (distance >= 1000 ? Math.round(distance/1000) + ' km' : distance + ' m') + ')';
                    gpsStatusText.style.color = '#DC2626';
                    gpsDistanceText.textContent = 'Wajib berada dalam radius ' + schoolRadius + ' meter';
                    gpsIconWrap.style.background = 'rgba(239,68,68,0.15)';
                    gpsIconWrap.style.color = '#EF4444';
                } else {
                    gpsStatusText.textContent = 'Siap Scan • Terdeteksi (' + (distance >= 1000 ? Math.round(distance/1000) + ' km' : distance + ' m') + ')';
                    gpsStatusText.style.color = '#0052FF';
                    gpsDistanceText.textContent = 'Mode toleransi aktif • Cukup scan QR di layar kantor';
                    gpsIconWrap.style.background = 'rgba(0,82,255,0.12)';
                    gpsIconWrap.style.color = '#0052FF';
                }
            },
            function(err) {
                if (perluKunci) {
                    gpsStatusText.textContent = 'Izin Lokasi Belum Aktif';
                    gpsDistanceText.textContent = 'Aktifkan GPS browser untuk validasi radius';
                    gpsStatusText.style.color = '#D97706';
                } else {
                    gpsStatusText.textContent = 'GPS Dilewati • Siap Scan';
                    gpsStatusText.style.color = '#0052FF';
                    gpsDistanceText.textContent = 'Arahkan kamera ke QR Code di layar kantor';
                    gpsIconWrap.style.background = 'rgba(0,82,255,0.12)';
                    gpsIconWrap.style.color = '#0052FF';
                }
            },
            { enableHighAccuracy: true, timeout: 6000 }
        );
    }


    detectLocation();
    var btnRefreshGps = document.getElementById('btnRefreshGps');
    if (btnRefreshGps) btnRefreshGps.addEventListener('click', detectLocation);

    // 3. QR Camera Scanner
    var html5QrCode = null;
    var isScanning = false;
    var currentCameraIndex = 0;
    var camerasList = [];

    var btnToggleCamera = document.getElementById('btnToggleCamera');
    var camBtnText = document.getElementById('camBtnText');
    var placeholder = document.getElementById('scannerPlaceholder');
    var scanningOverlay = document.getElementById('scanningOverlay');
    var cameraControls = document.getElementById('cameraControls');
    var btnSwitchCamera = document.getElementById('btnSwitchCamera');
    var isProcessingScan = false;

    btnToggleCamera.addEventListener('click', function() {
        if (isScanning) {
            stopScanner();
        } else {
            startScanner();
        }
    });

    var btnUploadQr = document.getElementById('btnUploadQr');
    var qrFileInput = document.getElementById('qrFileInput');

    // Fallback: Pemindaian via Foto Kamera HP (100% didukung Safari & Chrome tanpa HTTPS)
    if (btnUploadQr && qrFileInput) {
        btnUploadQr.addEventListener('click', function() {
            qrFileInput.click();
        });

        qrFileInput.addEventListener('change', function(e) {
            if (!e.target.files || e.target.files.length === 0) return;
            var file = e.target.files[0];

            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("qrReader");
            }

            var isDark = document.documentElement.classList.contains('dark-mode');
            Swal.fire({
                title: 'Menganalisis Foto...',
                text: 'Memindai QR Code dari foto',
                allowOutsideClick: false,
                background: isDark ? '#141C28' : '#FFFFFF',
                color: isDark ? '#F8FAFC' : '#111827',
                didOpen: function() {
                    Swal.showLoading();
                }
            });

            html5QrCode.scanFile(file, false)
                .then(function(decodedText) {
                    Swal.close();
                    onScanSuccess(decodedText);
                })
                .catch(function(err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Membaca QR',
                        text: 'QR Code tidak terdeteksi pada foto. Pastikan layar monitor terlihat jelas dan tidak silau.',
                        confirmButtonColor: '#DC2626',
                        background: isDark ? '#141C28' : '#FFFFFF',
                        color: isDark ? '#F8FAFC' : '#111827'
                    });
                })
                .finally(function() {
                    qrFileInput.value = '';
                });
        });
    }

    function startScanner() {
        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("qrReader");
        }

        var isSecure = window.isSecureContext || window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

        // Deteksi jika browser (seperti Safari di iOS) memblokir streaming video live pada HTTP non-secure
        if (!isSecure && (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia)) {
            var isDark = document.documentElement.classList.contains('dark-mode');
            Swal.fire({
                icon: 'info',
                title: 'Gunakan Mode Foto QR',
                text: 'Browser Safari/Chrome membatasi streaming kamera langsung di jaringan lokal non-HTTPS. Mengalihkan ke pemotretan kamera...',
                timer: 2500,
                showConfirmButton: false,
                background: isDark ? '#141C28' : '#FFFFFF',
                color: isDark ? '#F8FAFC' : '#111827'
            });
            setTimeout(function() {
                if (qrFileInput) qrFileInput.click();
            }, 600);
            return;
        }

        Html5Qrcode.getCameras().then(function(devices) {
            if (devices && devices.length) {
                camerasList = devices;
                // Pilih kamera belakang secara default jika ada
                var backCamIndex = devices.findIndex(function(d) {
                    return d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('environment') || d.label.toLowerCase().includes('belakang');
                });
                currentCameraIndex = backCamIndex !== -1 ? backCamIndex : 0;
                var cameraId = camerasList[currentCameraIndex].id;

                placeholder.style.display = 'none';
                scanningOverlay.style.display = 'block';
                if (camerasList.length > 1) cameraControls.style.display = 'block';

                html5QrCode.start(
                    cameraId,
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 },
                        aspectRatio: 1.0
                    },
                    onScanSuccess,
                    function(err) { /* silent on each frame */ }
                ).then(function() {
                    isScanning = true;
                    camBtnText.textContent = 'Tutup Kamera';
                    btnToggleCamera.className = 'btn btn-secondary';
                }).catch(function(err) {
                    alert('Gagal membuka kamera: ' + err);
                    stopScanner();
                });
            } else {
                alert('Tidak ada kamera yang terdeteksi di perangkat ini.');
            }
        }).catch(function(err) {
            var errStr = String(err).toLowerCase();
            if (errStr.includes('secure context') || errStr.includes('https') || errStr.includes('not allowed')) {
                var isDark = document.documentElement.classList.contains('dark-mode');
                Swal.fire({
                    icon: 'info',
                    title: 'Akses Kamera Browser Dibatasi',
                    text: 'Safari memerlukan koneksi HTTPS untuk membuka kamera streaming langsung. Beralih ke mode Foto QR...',
                    confirmButtonText: 'Buka Kamera HP',
                    confirmButtonColor: '#0052FF',
                    background: isDark ? '#141C28' : '#FFFFFF',
                    color: isDark ? '#F8FAFC' : '#111827'
                }).then(function() {
                    if (qrFileInput) qrFileInput.click();
                });
            } else {
                alert('Tidak dapat mengakses kamera: ' + err);
            }
        });
    }


    function stopScanner() {
        if (html5QrCode && isScanning) {
            html5QrCode.stop().then(function() {
                isScanning = false;
                camBtnText.textContent = 'Buka Kamera';
                btnToggleCamera.className = 'btn btn-primary';
                placeholder.style.display = 'block';
                scanningOverlay.style.display = 'none';
                cameraControls.style.display = 'none';
            }).catch(function(err) {
                console.error(err);
            });
        }
    }

    if (btnSwitchCamera) {
        btnSwitchCamera.addEventListener('click', function() {
            if (camerasList.length > 1 && isScanning) {
                currentCameraIndex = (currentCameraIndex + 1) % camerasList.length;
                html5QrCode.stop().then(function() {
                    startScanner();
                });
            }
        });
    }

    // Callback saat QR Code berhasil dibaca
    function onScanSuccess(decodedText, decodedResult) {
        if (isProcessingScan) return;
        isProcessingScan = true;

        // Sound/Haptic feedback jika didukung
        if (navigator.vibrate) navigator.vibrate([100, 50, 100]);

        // Kirim ke server
        fetch('{{ route("presensi.scan") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                token: decodedText,
                latitude: userLat,
                longitude: userLng
            })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                var isDark = document.documentElement.classList.contains('dark-mode');
                Swal.fire({
                    icon: 'success',
                    title: data.action === 'masuk' ? 'Presensi Masuk Berhasil!' : (data.action === 'pulang' ? 'Presensi Pulang Berhasil!' : 'Sudah Presensi'),
                    text: data.message,
                    timer: 3500,
                    showConfirmButton: false,
                    background: isDark ? '#141C28' : '#FFFFFF',
                    color: isDark ? '#F8FAFC' : '#111827'
                });

                // Update UI status langsung
                if (data.action === 'masuk') {
                    document.getElementById('displayJamMasuk').textContent = data.jam;
                } else if (data.action === 'pulang') {
                    document.getElementById('displayJamPulang').textContent = data.jam;
                    var sp = document.getElementById('displayStatusPulang');
                    if (sp) sp.textContent = 'Sudah Pulang';
                }

                stopScanner();
                setTimeout(function() { window.location.reload(); }, 2000);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Presensi Gagal',
                    text: data.message || 'Terjadi kesalahan.',
                    confirmButtonColor: '#DC2626'
                });
            }
        })
        .catch(function(err) {
            alert('Gagal menghubungi server: ' + err);
        })
        .finally(function() {
            setTimeout(function() { isProcessingScan = false; }, 3000);
        });
    }

    // 4. Modal Izin / Sakit
    var modalIzin = document.getElementById('modalIzin');
    var btnBukaIzin = document.getElementById('btnBukaIzin');
    var btnCloseIzin = document.getElementById('btnCloseModalIzin');
    var btnCancelIzin = document.getElementById('btnCancelIzin');

    if (btnBukaIzin) btnBukaIzin.addEventListener('click', function() { modalIzin.style.display = 'flex'; });
    if (btnCloseIzin) btnCloseIzin.addEventListener('click', function() { modalIzin.style.display = 'none'; });
    if (btnCancelIzin) btnCancelIzin.addEventListener('click', function() { modalIzin.style.display = 'none'; });
    modalIzin.addEventListener('click', function(e) { if (e.target === modalIzin) modalIzin.style.display = 'none'; });
});
</script>
@endsection
