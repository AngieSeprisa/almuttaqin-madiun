<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\Presensi;
use App\Models\PresensiGuru;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RekapAbsensiController extends Controller
{
    /**
     * Submenu: Rekap Absensi Guru.
     */
    public function guru(Request $request)
    {
        $user = auth()->user();
        $lembagas = $user->accessibleLembagas();

        // Filter Lembaga (Dukung "Semua Lembaga" jika lembaga_id dikirim kosong/all)
        $isSemuaLembaga = $request->exists('lembaga_id') && (
            $request->get('lembaga_id') === '' || 
            $request->get('lembaga_id') === 'all' || 
            is_null($request->get('lembaga_id'))
        );

        if ($isSemuaLembaga) {
            $lembagaId = null;
        } elseif ($request->filled('lembaga_id')) {
            $lembagaId = (int) $request->get('lembaga_id');
        } else {
            $currentLembaga = $user->currentLembaga() ?: $lembagas->first();
            $lembagaId = $currentLembaga ? $currentLembaga->id : null;
        }

        // Filter Periode
        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);
        $tanggal = $request->get('tanggal');
        $status = $request->get('status');
        $search = $request->get('search');

        // Sinkronisasi otomatis data tap RFID multi-lokasi ke presensi guru
        $this->syncRfidTapsToPresensiGuru($tanggal, $bulan, $tahun);

        // Query Presensi Terpadu yang Disesuaikan Ketat Berdasarkan Lembaga Terdaftar Guru
        $query = $this->buildPresensiQuery($lembagaId, $tanggal, $bulan, $tahun, $status, $search);

        // Summary Statistik (sebelum paginate)
        $summaryQuery = clone $query;
        $ringkasan = [
            'total'     => (clone $summaryQuery)->count(),
            'hadir'     => (clone $summaryQuery)->where('status', 'hadir')->count(),
            'terlambat' => (clone $summaryQuery)->where('status', 'terlambat')->count(),
            'izin'      => (clone $summaryQuery)->where('status', 'izin')->count(),
            'sakit'     => (clone $summaryQuery)->where('status', 'sakit')->count(),
            'alpa'      => (clone $summaryQuery)->where('status', 'alpa')->count(),
        ];

        $perPage = (int) $request->get('per_page', 15);
        if ($perPage < 5 || $perPage > 200) {
            $perPage = 15;
        }

        $presensis = $query->orderBy('tanggal', 'desc')
            ->orderBy('jam_masuk', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return view('rekap-absensi.guru', compact(
            'presensis',
            'lembagas',
            'lembagaId',
            'bulan',
            'tahun',
            'tanggal',
            'status',
            'search',
            'ringkasan'
        ));
    }

    /**
     * Koreksi manual data presensi oleh admin.
     */
    public function koreksi(Request $request, $id)
    {
        $validated = $request->validate([
            'status'     => 'required|in:hadir,terlambat,izin,sakit,alpa',
            'jam_masuk'  => 'nullable|date_format:H:i',
            'jam_pulang' => 'nullable|date_format:H:i',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $presensi = PresensiGuru::findOrFail($id);

        $presensi->update([
            'status'     => $validated['status'],
            'jam_masuk'  => $validated['jam_masuk'] ? $validated['jam_masuk'] . ':00' : $presensi->jam_masuk,
            'jam_pulang' => $validated['jam_pulang'] ? $validated['jam_pulang'] . ':00' : $presensi->jam_pulang,
            'keterangan' => $validated['keterangan'] ? ($presensi->keterangan ? $presensi->keterangan . ' | Koreksi: ' . $validated['keterangan'] : 'Koreksi: ' . $validated['keterangan']) : $presensi->keterangan,
        ]);

        return redirect()->back()->with('success', 'Data presensi berhasil dikoreksi.');
    }

    /**
     * Ekspor Rekap Absensi Guru ke CSV/Excel.
     */
    public function export(Request $request)
    {
        $isSemuaLembaga = $request->exists('lembaga_id') && (
            $request->get('lembaga_id') === '' || 
            $request->get('lembaga_id') === 'all' || 
            is_null($request->get('lembaga_id'))
        );

        if ($isSemuaLembaga) {
            $lembagaId = null;
        } elseif ($request->filled('lembaga_id')) {
            $lembagaId = (int) $request->get('lembaga_id');
        } else {
            $user = auth()->user();
            $lembagaId = $user->currentLembaga()?->id ?: $user->accessibleLembagas()->first()?->id;
        }

        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);
        $tanggal = $request->get('tanggal');

        $this->syncRfidTapsToPresensiGuru($tanggal, $bulan, $tahun);

        $query = $this->buildPresensiQuery($lembagaId, $tanggal, $bulan, $tahun);
        $records = $query->orderBy('tanggal', 'asc')->orderBy('jam_masuk', 'asc')->get();

        $fileName = 'rekap_absensi_guru_' . $tahun . '_' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($records) {
            $handle = fopen('php://output', 'w');
            // Add UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header kolom
            fputcsv($handle, ['No', 'Tanggal', 'NIP', 'Nama Guru', 'Lembaga', 'Jam Masuk', 'Jam Pulang', 'Status', 'Jarak Masuk (m)', 'Keterangan']);

            $no = 1;
            foreach ($records as $row) {
                fputcsv($handle, [
                    $no++,
                    $row->tanggal ? $row->tanggal->format('d/m/Y') : '-',
                    $row->user->nip ?? '-',
                    $row->user->name ?? '-',
                    $row->lembaga->nama ?? '-',
                    $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '-',
                    $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '-',
                    $row->status_label,
                    $row->jarak_masuk_meter ?? '-',
                    $row->keterangan ?? '-',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Cetak Lembar Rekap Absensi Guru (Format A4 PDF/Print Friendly).
     */
    public function cetak(Request $request)
    {
        $isSemuaLembaga = $request->exists('lembaga_id') && (
            $request->get('lembaga_id') === '' || 
            $request->get('lembaga_id') === 'all' || 
            is_null($request->get('lembaga_id'))
        );

        if ($isSemuaLembaga) {
            $lembagaId = null;
        } elseif ($request->filled('lembaga_id')) {
            $lembagaId = (int) $request->get('lembaga_id');
        } else {
            $user = auth()->user();
            $lembagaId = $user->currentLembaga()?->id ?: $user->accessibleLembagas()->first()?->id;
        }

        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);
        $tanggal = $request->get('tanggal');

        $this->syncRfidTapsToPresensiGuru($tanggal, $bulan, $tahun);

        $lembaga = $lembagaId ? Lembaga::find($lembagaId) : (auth()->user()->currentLembaga() ?: Lembaga::first());

        $query = $this->buildPresensiQuery($lembagaId, $tanggal, $bulan, $tahun);
        $records = $query->orderBy('tanggal', 'asc')->orderBy('jam_masuk', 'asc')->get();

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ][$bulan] ?? '';

        return view('rekap-absensi.cetak', compact('records', 'lembaga', 'bulan', 'tahun', 'namaBulan'));
    }

    /**
     * Query builder presensi guru yang disesuaikan ketat berdasarkan lembaga tempat guru terdaftar:
     * - Walaupun akun berstatus yayasan, jika terdaftarnya sebagai guru SMP (primary_lembaga_id SMP),
     *   maka absensinya HANYA terlihat saat memilih lembaga SMP.
     * - Tidak akan bocor ke SMA atau lembaga lainnya.
     * - Opsi "Semua Lembaga" tetap menampilkan seluruh guru.
     */
    protected function buildPresensiQuery(?int $lembagaId, ?string $tanggal, int $bulan, int $tahun, ?string $status = null, ?string $search = null)
    {
        $query = PresensiGuru::with(['user', 'lembaga']);

        if ($lembagaId) {
            $query->where(function ($q) use ($lembagaId) {
                // Tampilkan presensi jika guru/staf memang terdaftar pada lembaga ini (primary_lembaga_id)
                $q->whereHas('user', function ($uq) use ($lembagaId) {
                    $uq->where('primary_lembaga_id', $lembagaId);
                })
                // ATAU jika record presensi dicatat di lembaga ini dan akun guru belum memiliki primary_lembaga_id
                ->orWhere(function ($sub) use ($lembagaId) {
                    $sub->where('lembaga_id', $lembagaId)
                        ->whereHas('user', function ($uq) {
                            $uq->whereNull('primary_lembaga_id');
                        });
                });
            });
        }

        if ($tanggal) {
            $query->whereDate('tanggal', $tanggal);
        } else {
            $query->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun);
        }

        if ($status && in_array($status, ['hadir', 'terlambat', 'izin', 'sakit', 'alpa'])) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Sinkronisasi data tap RFID guru/pegawai dari tabel presensis ke presensi_gurus.
     * Memastikan tap yang tercatat di dashboard RFID multi-lokasi otomatis masuk ke rekap absensi guru
     * dan dipetakan ke lembaga tempat guru tersebut terdaftar.
     */
    protected function syncRfidTapsToPresensiGuru(?string $tanggal = null, ?int $bulan = null, ?int $tahun = null): void
    {
        if (!Schema::hasTable('presensis') || !Schema::hasTable('presensi_gurus')) {
            return;
        }

        $tapsQuery = Presensi::where('tipe_entitas', 'guru_pegawai');

        if ($tanggal) {
            $tapsQuery->whereDate('waktu_tap', $tanggal);
        } elseif ($bulan && $tahun) {
            $tapsQuery->whereMonth('waktu_tap', $bulan)->whereYear('waktu_tap', $tahun);
        } else {
            $tapsQuery->whereDate('waktu_tap', Carbon::today());
        }

        $taps = $tapsQuery->orderBy('waktu_tap', 'asc')->get();

        if ($taps->isEmpty()) {
            return;
        }

        // Kelompokkan berdasarkan entitas_id (user_id) dan tanggal tap
        $grouped = $taps->groupBy(function ($tap) {
            $date = Carbon::parse($tap->waktu_tap)->toDateString();
            return "{$tap->entitas_id}_{$date}";
        });

        foreach ($grouped as $key => $tapList) {
            $firstTap = $tapList->first();
            $userId = (int) $firstTap->entitas_id;
            $user = User::find($userId);
            if (!$user) {
                continue;
            }

            $tapDate = Carbon::parse($firstTap->waktu_tap)->toDateString();
            $jamMasuk = Carbon::parse($firstTap->waktu_tap)->format('H:i:s');

            // Tentukan status masuk dari status_kehadiran log tap
            $statusMasuk = match ($firstTap->status_kehadiran) {
                'terlambat' => 'terlambat',
                default     => 'hadir',
            };

            // Lembaga terdaftar guru (prioritaskan primary_lembaga_id guru)
            $registeredLembagaId = $user->primary_lembaga_id;
            if (!$registeredLembagaId) {
                // Deteksi dari serial device jika ada indikasi khusus
                $devId = strtolower((string) $firstTap->device_id);
                if (str_contains($devId, 'smp')) {
                    $smp = Lembaga::where(fn($q) => $q->where('kode', 'smp')->orWhere('singkatan', 'SMP'))->first();
                    if ($smp) {
                        $registeredLembagaId = $smp->id;
                    }
                } elseif (str_contains($devId, 'sma')) {
                    $sma = Lembaga::where(fn($q) => $q->where('kode', 'sma')->orWhere('singkatan', 'SMA'))->first();
                    if ($sma) {
                        $registeredLembagaId = $sma->id;
                    }
                }
            }

            if (!$registeredLembagaId) {
                $registeredLembagaId = $user->lembagas()->first()?->id ?: 1;
            }

            // Cek apakah presensi guru untuk user ini di tanggal ini sudah ada
            $presensi = PresensiGuru::where('user_id', $userId)
                ->whereDate('tanggal', $tapDate)
                ->first();

            if (!$presensi) {
                // Jam pulang jika ada tap berikutnya yang berselisih waktu >= 3 menit
                $jamPulang = null;
                if ($tapList->count() > 1) {
                    $lastTap = $tapList->last();
                    $diffSec = Carbon::parse($lastTap->waktu_tap)->diffInSeconds(Carbon::parse($firstTap->waktu_tap));
                    if ($diffSec >= 180) { // Debounce 3 menit
                        $jamPulang = Carbon::parse($lastTap->waktu_tap)->format('H:i:s');
                    }
                }

                PresensiGuru::create([
                    'user_id'       => $userId,
                    'lembaga_id'    => $registeredLembagaId,
                    'tanggal'       => $tapDate,
                    'jam_masuk'     => $jamMasuk,
                    'jam_pulang'    => $jamPulang,
                    'status'        => $statusMasuk,
                    'metode_masuk'  => 'rfid_tap',
                    'metode_pulang' => $jamPulang ? 'rfid_tap' : null,
                ]);
            } else {
                $updated = false;

                // Pastikan lembaga_id presensi guru selalu sesuai dengan lembaga tempat guru terdaftar
                if ($presensi->lembaga_id != $registeredLembagaId) {
                    $presensi->lembaga_id = $registeredLembagaId;
                    $updated = true;
                }

                // Update jam_masuk jika belum terisi
                if (empty($presensi->jam_masuk)) {
                    $presensi->jam_masuk = $jamMasuk;
                    $presensi->status = $statusMasuk;
                    $presensi->metode_masuk = 'rfid_tap';
                    $updated = true;
                }

                // Cek pembaruan jam pulang dari tap terakhir
                if ($tapList->count() > 1) {
                    $lastTap = $tapList->last();
                    $baseTime = $presensi->jam_masuk ? "{$tapDate} {$presensi->jam_masuk}" : $firstTap->waktu_tap;
                    $diffSec = Carbon::parse($lastTap->waktu_tap)->diffInSeconds(Carbon::parse($baseTime));

                    if ($diffSec >= 180) {
                        $newJamPulang = Carbon::parse($lastTap->waktu_tap)->format('H:i:s');
                        if (empty($presensi->jam_pulang) || $newJamPulang > $presensi->jam_pulang) {
                            $presensi->jam_pulang = $newJamPulang;
                            $presensi->metode_pulang = 'rfid_tap';
                            $updated = true;
                        }
                    }
                }

                if ($updated) {
                    $presensi->save();
                }
            }
        }
    }
}
