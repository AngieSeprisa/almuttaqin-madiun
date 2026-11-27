<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proyektor Ujian: {{ $ujian->nama_ujian }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            user-select: none;
        }

        /* HEADER */
        .proyektor-header {
            background: #1e293b;
            border-bottom: 2px solid #334155;
            padding: 14px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .header-info-title {
            font-size: 18px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.01em;
        }
        .header-info-meta {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 3px;
        }

        /* TIMER BOX */
        .timer-badge {
            background: #0052FF;
            color: #ffffff;
            border-radius: 12px;
            padding: 8px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 14px rgba(0, 82, 255, 0.4);
            transition: background 0.3s ease;
        }
        .timer-badge.warning {
            background: #d97706 !important;
            box-shadow: 0 4px 14px rgba(217, 119, 6, 0.4) !important;
        }
        .timer-badge.danger {
            background: #dc2626 !important;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.5) !important;
            animation: pulse-danger 0.8s infinite alternate;
        }
        @keyframes pulse-danger {
            from { transform: scale(1); }
            to { transform: scale(1.05); }
        }
        .timer-digits {
            font-family: "Courier New", Courier, monospace;
            font-size: 28px;
            font-weight: 900;
            letter-spacing: 0.05em;
        }

        /* MAIN QUESTION STAGE */
        .stage-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            padding: 30px 40px;
        }
        .question-card {
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 20px;
            padding: 36px 44px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .q-number-pill {
            display: inline-block;
            background: rgba(0, 82, 255, 0.18);
            border: 1px solid #0052FF;
            color: #60a5fa;
            font-size: 15px;
            font-weight: 800;
            padding: 6px 16px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 20px;
        }
        .q-number-pill.is-essay {
            background: rgba(168, 85, 247, 0.20);
            border-color: #a855f7;
            color: #d8b4fe;
        }
        .essay-prompt-box {
            background: rgba(15, 23, 42, 0.72);
            border: 2px dashed #475569;
            border-radius: 14px;
            padding: 22px 26px;
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 19px;
            color: #cbd5e1;
        }
        .drawer-section-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #94a3b8;
            margin: 16px 0 8px;
        }
        .num-btn.is-essay-btn {
            border-color: #7e22ce;
            color: #e9d5ff;
        }
        .num-btn.is-essay-btn.active,
        .num-btn.is-essay-btn:hover {
            background: #9333ea;
            border-color: #9333ea;
            color: #ffffff;
        }
        .q-text {
            font-size: 26px;
            line-height: 1.5;
            color: #ffffff;
            font-weight: 600;
            margin-bottom: 32px;
            white-space: pre-line;
        }

        /* OPTIONS GRID */
        .options-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }
        .opt-box {
            background: #0f172a;
            border: 2px solid #334155;
            border-radius: 14px;
            padding: 16px 22px;
            display: flex;
            align-items: center;
            gap: 18px;
            font-size: 20px;
            color: #e2e8f0;
            transition: all 0.2s ease;
        }
        .opt-letter {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #334155;
            color: #ffffff;
            font-weight: 800;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* BOTTOM PRESENTER TOOLBAR */
        .bottom-toolbar {
            background: #1e293b;
            border-top: 2px solid #334155;
            padding: 16px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
        }
        .btn-ctrl {
            background: #334155;
            color: #ffffff;
            border: 1px solid #475569;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .btn-ctrl:hover {
            background: #475569;
            transform: translateY(-1px);
        }
        .btn-ctrl.primary {
            background: #0052FF;
            border-color: #0052FF;
        }
        .btn-ctrl.primary:hover {
            background: #0043d4;
        }

        /* DRAWER NOMOR SOAL */
        .drawer-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }
        .drawer-card {
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 18px;
            padding: 26px;
            max-width: 640px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .number-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(54px, 1fr));
            gap: 10px;
            margin-top: 16px;
        }
        .num-btn {
            background: #0f172a;
            border: 1px solid #475569;
            color: #f8fafc;
            border-radius: 10px;
            height: 48px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
        }
        .num-btn:hover, .num-btn.active {
            background: #0052FF;
            border-color: #0052FF;
            color: #ffffff;
            transform: scale(1.05);
        }
    </style>
</head>
<body>

    <!-- TOP HEADER -->
    <header class="proyektor-header">
        <div>
            <div class="header-info-title">{{ $ujian->nama_ujian }}</div>
            <div class="header-info-meta">
                Mata Pelajaran: <strong>{{ $ujian->mapel?->mata_pelajaran ?? '-' }}</strong> &bull; Kelas: <strong>{{ $ujian->kelas?->nama_kelas ?? '-' }}</strong> &bull; Guru: <strong>{{ $ujian->guru?->name ?? '-' }}</strong> &bull; {{ $activeSlot }}
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:14px;">
            <div class="timer-badge" id="timerBadge">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span class="timer-digits" id="timerDisplay">01:30</span>
            </div>
        </div>
    </header>

    <!-- STAGE SOAL PER NOMOR -->
    <main class="stage-container">
        <div class="question-card" id="questionCard">
            <div class="q-number-pill" id="questionNumberPill">
                SOAL NOMOR 1 DARI {{ count($soals) }}
            </div>

            <div class="q-text" id="questionText">
                Memuat pertanyaan...
            </div>

            <div class="options-grid" id="optionsGrid">
                <!-- Populated via JavaScript -->
            </div>
        </div>
    </main>

    <!-- BOTTOM CONTROL TOOLBAR -->
    <footer class="bottom-toolbar">
        <div style="display:flex; align-items:center; gap:10px;">
            <button type="button" class="btn-ctrl" onclick="toggleNumberDrawer()">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span>Daftar Nomor</span>
            </button>

            <button type="button" class="btn-ctrl" onclick="addSeconds(30)">
                +30 Detik
            </button>
        </div>

        <div style="display:flex; align-items:center; gap:10px;">
            <button type="button" class="btn-ctrl" onclick="prevQuestion()" id="btnPrev">
                &larr; Soal Sebelumnya
            </button>

            <button type="button" class="btn-ctrl primary" onclick="togglePauseTimer()" id="btnPause">
                <span id="pauseIcon">⏸</span> <span id="pauseText">Jeda Timer</span>
            </button>

            <button type="button" class="btn-ctrl" onclick="nextQuestion()" id="btnNext">
                Soal Berikutnya &rarr;
            </button>
        </div>

        <div style="display:flex; align-items:center; gap:10px;">
            <button type="button" class="btn-ctrl" onclick="toggleFullScreen()">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
                </svg>
                <span>Layar Penuh</span>
            </button>

            <a href="{{ route('ujian.sesi') }}" class="btn-ctrl" style="text-decoration:none; background:#7f1d1d; border-color:#991b1b;">
                Keluar
            </a>
        </div>
    </footer>

    <!-- DRAWER NOMOR SOAL -->
    <div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawerOutside(event)">
        <div class="drawer-card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 style="font-size:18px; font-weight:800; color:#ffffff;">Pilih Nomor Soal</h3>
                <button type="button" onclick="toggleNumberDrawer()" style="background:none; border:none; color:#94a3b8; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <div class="number-grid" id="numberGrid">
                <!-- Numbers generated by JS -->
            </div>
        </div>
    </div>

    <script>
        const soalsData = @json($soals);
        const totalSoals = soalsData.length;
        const totalPg = soalsData.filter(s => (s.tipe_soal || 'pg') !== 'essay').length;
        const totalEssay = soalsData.filter(s => (s.tipe_soal || 'pg') === 'essay').length;
        let currentIndex = 0;
        let timeLeft = 0;
        let isPaused = false;
        let timerInterval = null;

        // Inisialisasi Audio Web API untuk nada bel halus
        let audioCtx = null;
        function playChime(freq = 660, type = 'sine') {
            try {
                if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                if (audioCtx.state === 'suspended') audioCtx.resume();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = type;
                osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
                gain.gain.setValueAtTime(0.12, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.6);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.6);
            } catch(e) {}
        }

        function renderQuestion(index) {
            if (index < 0 || index >= totalSoals) return;
            currentIndex = index;

            const q = soalsData[currentIndex];
            const pill = document.getElementById('questionNumberPill');
            const qText = document.getElementById('questionText');
            const optGrid = document.getElementById('optionsGrid');
            const isEssay = (q.tipe_soal || 'pg') === 'essay';

            pill.classList.toggle('is-essay', isEssay);
            if (isEssay) {
                pill.textContent = `SOAL ESSAY NOMOR ${q.nomor_soal} DARI ${totalEssay}`;
            } else if (totalEssay > 0) {
                pill.textContent = `SOAL PG NOMOR ${q.nomor_soal} DARI ${totalPg}`;
            } else {
                pill.textContent = `SOAL NOMOR ${q.nomor_soal} DARI ${totalSoals}`;
            }
            qText.textContent = q.pertanyaan;

            if (isEssay) {
                optGrid.innerHTML = `
                    <div class="essay-prompt-box">
                        <div style="width:44px; height:44px; border-radius:12px; background:rgba(168,85,247,0.22); color:#d8b4fe; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </div>
                        <div>
                            <div style="font-weight:700; color:#f8fafc; margin-bottom:2px;">Soal Uraian / Essay</div>
                            <div style="font-size:16px; color:#94a3b8;">Tuliskan jawaban Anda secara jelas dan rapi pada kolom Jawaban Essay nomor <strong>${q.nomor_soal}</strong> di Lembar Jawab.</div>
                        </div>
                    </div>
                `;
            } else {
                // Render opsi A-E
                const opsiList = [];
                if (q.opsi_a) opsiList.push({ letter: 'A', text: q.opsi_a });
                if (q.opsi_b) opsiList.push({ letter: 'B', text: q.opsi_b });
                if (q.opsi_c) opsiList.push({ letter: 'C', text: q.opsi_c });
                if (q.opsi_d) opsiList.push({ letter: 'D', text: q.opsi_d });
                if (q.opsi_e) opsiList.push({ letter: 'E', text: q.opsi_e });

                let html = '';
                opsiList.forEach(item => {
                    html += `
                        <div class="opt-box">
                            <div class="opt-letter">${item.letter}</div>
                            <div>${item.text}</div>
                        </div>
                    `;
                });
                optGrid.innerHTML = html;
            }

            // Tombol disable check
            document.getElementById('btnPrev').disabled = (currentIndex === 0);
            document.getElementById('btnNext').disabled = (currentIndex === totalSoals - 1);

            // Set Timer
            timeLeft = q.timer_detik || (isEssay ? {{ $ujian->timer_essay_default ?? 180 }} : {{ $ujian->timer_default ?? 90 }});
            updateTimerDisplay();
            startTimer();
            updateDrawerActiveNumber();
        }

        function updateTimerDisplay() {
            const display = document.getElementById('timerDisplay');
            const badge = document.getElementById('timerBadge');

            const m = Math.floor(timeLeft / 60);
            const s = timeLeft % 60;
            display.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;

            badge.classList.remove('warning', 'danger');
            if (timeLeft <= 10) {
                badge.classList.add('danger');
            } else if (timeLeft <= 25) {
                badge.classList.add('warning');
            }
        }

        function startTimer() {
            clearInterval(timerInterval);
            isPaused = false;
            document.getElementById('pauseIcon').textContent = '⏸';
            document.getElementById('pauseText').textContent = 'Jeda Timer';

            timerInterval = setInterval(() => {
                if (isPaused) return;

                if (timeLeft > 0) {
                    timeLeft--;
                    updateTimerDisplay();

                    if (timeLeft === 10) {
                        playChime(520); // Bunyi peringatan 10 detik terakhir
                    }
                } else {
                    // Waktu habis: bel & lanjut ke soal berikutnya
                    playChime(880, 'triangle');
                    clearInterval(timerInterval);
                    if (currentIndex < totalSoals - 1) {
                        nextQuestion();
                    } else {
                        alert('Seluruh soal telah selesai ditampilkan!');
                    }
                }
            }, 1000);
        }

        function togglePauseTimer() {
            isPaused = !isPaused;
            if (isPaused) {
                document.getElementById('pauseIcon').textContent = '▶';
                document.getElementById('pauseText').textContent = 'Lanjutkan';
            } else {
                document.getElementById('pauseIcon').textContent = '⏸';
                document.getElementById('pauseText').textContent = 'Jeda Timer';
            }
        }

        function addSeconds(sec) {
            timeLeft += sec;
            updateTimerDisplay();
        }

        function nextQuestion() {
            if (currentIndex < totalSoals - 1) {
                renderQuestion(currentIndex + 1);
            }
        }

        function prevQuestion() {
            if (currentIndex > 0) {
                renderQuestion(currentIndex - 1);
            }
        }

        function toggleNumberDrawer() {
            const drawer = document.getElementById('drawerOverlay');
            drawer.style.display = drawer.style.display === 'flex' ? 'none' : 'flex';
        }

        function closeDrawerOutside(e) {
            if (e.target.id === 'drawerOverlay') {
                toggleNumberDrawer();
            }
        }

        function jumpToQuestion(idx) {
            toggleNumberDrawer();
            renderQuestion(idx);
        }

        function initNumberGrid() {
            const grid = document.getElementById('numberGrid');
            if (totalPg > 0 && totalEssay > 0) {
                grid.style.display = 'block';
                let pgBtns = '';
                let essayBtns = '';
                soalsData.forEach((s, i) => {
                    if ((s.tipe_soal || 'pg') === 'essay') {
                        essayBtns += `<button type="button" class="num-btn is-essay-btn" id="numBtn-${i}" onclick="jumpToQuestion(${i})">E${s.nomor_soal}</button>`;
                    } else {
                        pgBtns += `<button type="button" class="num-btn" id="numBtn-${i}" onclick="jumpToQuestion(${i})">${s.nomor_soal}</button>`;
                    }
                });
                grid.innerHTML = `
                    <div class="drawer-section-title">Pilihan Ganda (${totalPg} Soal)</div>
                    <div class="number-grid" style="margin-top:6px;">${pgBtns}</div>
                    <div class="drawer-section-title">Essay / Uraian (${totalEssay} Soal)</div>
                    <div class="number-grid" style="margin-top:6px;">${essayBtns}</div>
                `;
                return;
            }

            let html = '';
            soalsData.forEach((s, i) => {
                const isEssay = (s.tipe_soal || 'pg') === 'essay';
                html += `<button type="button" class="num-btn ${isEssay ? 'is-essay-btn' : ''}" id="numBtn-${i}" onclick="jumpToQuestion(${i})">${isEssay ? 'E' + s.nomor_soal : s.nomor_soal}</button>`;
            });
            grid.innerHTML = html;
        }

        function updateDrawerActiveNumber() {
            for (let i = 0; i < totalSoals; i++) {
                const btn = document.getElementById(`numBtn-${i}`);
                if (btn) {
                    btn.classList.toggle('active', i === currentIndex);
                }
            }
        }

        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen().catch(() => {});
            }
        }

        // Keyboard Shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.code === 'ArrowRight') {
                nextQuestion();
            } else if (e.code === 'ArrowLeft') {
                prevQuestion();
            } else if (e.code === 'Space') {
                e.preventDefault();
                togglePauseTimer();
            } else if (e.code === 'KeyF') {
                toggleFullScreen();
            }
        });

        // Initialize on load
        window.addEventListener('DOMContentLoaded', () => {
            initNumberGrid();
            renderQuestion(0);
        });
    </script>
</body>
</html>

