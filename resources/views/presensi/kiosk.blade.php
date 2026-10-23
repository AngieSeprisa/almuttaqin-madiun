<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layar Kiosk QR Presensi - {{ $currentLembaga->nama }}</title>
    <link rel="icon" type="image/png" href="{{ $currentLembaga->logo_url }}">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-kiosk: #0A0F1D;
            --surface-kiosk: #131B2E;
            --surface-card: #1A243B;
            --border-kiosk: #23314F;
            --accent-blue: #0052FF;
            --text-main: #FFFFFF;
            --text-sub: #94A3B8;
        }
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Plus Jakarta Sans', sans-serif; }
        body {
            background-color: var(--bg-kiosk);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        .kiosk-header {
            padding: 20px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-kiosk);
            background: rgba(19, 27, 46, 0.7);
            backdrop-filter: blur(12px);
        }
        .brand-zone {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .brand-logo {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            object-fit: contain;
            background: #fff;
            padding: 4px;
        }
        .brand-title {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.3px;
        }
        .brand-subtitle {
            font-size: 13px;
            color: var(--text-sub);
        }
        .kiosk-body {
            flex: 1;
            padding: 32px;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 32px;
            max-width: 1300px;
            margin: 0 auto;
            width: 100%;
            align-items: center;
        }
        .qr-station-card {
            background: var(--surface-kiosk);
            border: 1px solid var(--border-kiosk);
            border-radius: 24px;
            padding: 36px 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            position: relative;
        }
        .qr-holder {
            background: #FFFFFF;
            padding: 18px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 82, 255, 0.2);
            margin: 20px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 256px;
            min-height: 256px;
        }
        .timer-bar-wrap {
            width: 100%;
            max-width: 280px;
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }
        .timer-bar-fill {
            height: 100%;
            background: var(--accent-blue);
            width: 100%;
            transition: width 1s linear;
        }
        .attendees-card {
            background: var(--surface-kiosk);
            border: 1px solid var(--border-kiosk);
            border-radius: 24px;
            padding: 28px;
            height: 100%;
            max-height: 540px;
            display: flex;
            flex-direction: column;
        }
        .attendees-list {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 16px;
        }
        .attendee-item {
            background: var(--surface-card);
            border: 1px solid var(--border-kiosk);
            border-radius: 14px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .btn-kiosk {
            background: var(--surface-card);
            color: var(--text-main);
            border: 1px solid var(--border-kiosk);
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-kiosk:hover {
            background: var(--border-kiosk);
        }
        @media (max-width: 900px) {
            .kiosk-body { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <header class="kiosk-header">
        <div class="brand-zone">
            <img src="{{ $currentLembaga->logo_url }}" alt="Logo" class="brand-logo">
            <div>
                <div class="brand-title">{{ $currentLembaga->nama }}</div>
                <div class="brand-subtitle">Layar Kiosk Presensi Mandiri Guru</div>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:12px;">
            <div id="kioskClock" style="font-size:20px; font-weight:800; font-family:monospace; color:var(--accent-blue); background:rgba(0,82,255,0.1); padding:6px 14px; border-radius:10px; border:1px solid rgba(0,82,255,0.2);">
                --:--:--
            </div>
            <button type="button" class="btn-kiosk" id="btnFullscreen">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                    <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                </svg>
                <span>Layar Penuh</span>
            </button>
            <a href="{{ route('beranda') }}" class="btn-kiosk">
                Kembali
            </a>
        </div>
    </header>

    <main class="kiosk-body">
        {{-- Area QR Code --}}
        <div class="qr-station-card">
            <span style="font-size:12px; font-weight:700; color:var(--text-sub); text-transform:uppercase; letter-spacing:1px;">
                Scan Menggunakan HP Masing-Masing
            </span>
            <h1 style="font-size:24px; font-weight:800; margin:6px 0 0 0;">
                Presensi Kehadiran Guru
            </h1>

            <div class="qr-holder" id="qrContainer">
                <div id="qrcode"></div>
            </div>

            <div style="display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--text-sub);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px; color:var(--accent-blue);">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span id="countdownText">Memperbarui kode QR dalam 20s...</span>
            </div>

            <div class="timer-bar-wrap">
                <div class="timer-bar-fill" id="timerBar"></div>
            </div>
        </div>

        {{-- Area Live Kehadiran Hari Ini --}}
        <div class="attendees-card">
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <h2 style="font-size:18px; font-weight:800; margin:0;">Kehadiran Terbaru Hari Ini</h2>
                    <span style="font-size:12.5px; color:var(--text-sub);">Real-time live updates</span>
                </div>
                <div style="width:10px; height:10px; border-radius:50%; background:#10B981; box-shadow:0 0 10px #10B981;"></div>
            </div>

            <div class="attendees-list" id="attendeesList">
                @forelse($hadirHariIni as $h)
                <div class="attendee-item">
                    <div>
                        <div style="font-size:14px; font-weight:700;">{{ $h->user->name ?? '-' }}</div>
                        <div style="font-size:12px; color:var(--text-sub);">
                            Masuk: {{ $h->jam_masuk ? substr($h->jam_masuk, 0, 5) : '-' }} WIB
                        </div>
                    </div>
                    <span style="font-size:11px; font-weight:700; padding:4px 8px; border-radius:6px; background:rgba(16,185,129,0.15); color:#10B981;">
                        {{ $h->status_label }}
                    </span>
                </div>
                @empty
                <div style="text-align:center; padding:40px 10px; color:var(--text-sub); font-size:13px;">
                    Belum ada guru yang melakukan presensi hari ini.
                </div>
                @endforelse
            </div>
        </div>
    </main>

    {{-- QRCode.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Live Clock
        var clockEl = document.getElementById('kioskClock');
        function updateClock() {
            var now = new Date();
            var h = String(now.getHours()).padStart(2, '0');
            var m = String(now.getMinutes()).padStart(2, '0');
            var s = String(now.getSeconds()).padStart(2, '0');
            if (clockEl) clockEl.textContent = h + ':' + m + ':' + s + ' WIB';
        }
        updateClock();
        setInterval(updateClock, 1000);

        // 2. Fullscreen Toggle
        var btnFullscreen = document.getElementById('btnFullscreen');
        if (btnFullscreen) {
            btnFullscreen.addEventListener('click', function() {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen();
                } else {
                    document.exitFullscreen();
                }
            });
        }

        // 3. Dynamic QR Code Generator
        var qrCodeInstance = null;
        var qrContainer = document.getElementById('qrcode');
        var countdownText = document.getElementById('countdownText');
        var timerBar = document.getElementById('timerBar');
        var attendeesList = document.getElementById('attendeesList');
        var secondsLeft = 20;

        function refreshQrCode() {
            fetch('{{ route("presensi.kiosk-data") }}')
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (qrContainer) {
                        qrContainer.innerHTML = '';
                        qrCodeInstance = new QRCode(qrContainer, {
                            text: data.qr_data,
                            width: 256,
                            height: 256,
                            colorDark: "#0A0F1D",
                            colorLight: "#FFFFFF",
                            correctLevel: QRCode.CorrectLevel.M
                        });
                    }

                    // Render daftar kehadiran terbaru
                    if (data.hadir && data.hadir.length > 0) {
                        var html = '';
                        data.hadir.forEach(function(item) {
                            html += '<div class="attendee-item">' +
                                '<div>' +
                                    '<div style="font-size:14px; font-weight:700;">' + item.nama + '</div>' +
                                    '<div style="font-size:12px; color:var(--text-sub);">Masuk: ' + item.masuk + ' WIB' + (item.pulang !== '-' ? ' • Pulang: ' + item.pulang : '') + '</div>' +
                                '</div>' +
                                '<span style="font-size:11px; font-weight:700; padding:4px 8px; border-radius:6px; background:rgba(16,185,129,0.15); color:#10B981;">' +
                                    item.status +
                                '</span>' +
                            '</div>';
                        });
                        attendeesList.innerHTML = html;
                    }

                    secondsLeft = 20;
                    timerBar.style.width = '100%';
                })
                .catch(function(err) {
                    console.error('Error fetching kiosk data:', err);
                });
        }

        refreshQrCode();

        // Interval Countdown Bar
        setInterval(function() {
            secondsLeft--;
            if (secondsLeft <= 0) {
                refreshQrCode();
            } else {
                countdownText.textContent = 'Memperbarui kode QR dalam ' + secondsLeft + 's...';
                var pct = (secondsLeft / 20) * 100;
                timerBar.style.width = pct + '%';
            }
        }, 1000);
    });
    </script>
</body>
</html>
