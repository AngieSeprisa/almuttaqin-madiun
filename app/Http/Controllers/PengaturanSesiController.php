<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\MasterKelasPengajian;
use App\Models\MasterSesi;
use App\Models\SesiDeviceAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PengaturanSesiController extends Controller
{
    /**
     * Halaman Utama Pengaturan Sesi & Perangkat Presensi RFID.
     */
    public function index()
    {
        $sesis = MasterSesi::with(['allocations.device', 'device'])
            ->orderBy('jam_mulai')
            ->get()
            ->map(function ($s) {
                if (!isset($s->nama_lokasi)) {
                    $s->nama_lokasi = $s->device->nama_lokasi ?? null;
                    $s->kategori_area = $s->device->kategori_area ?? null;
                    $s->device_aktif = $s->device->status_aktif ?? false;
                }
                return $s;
            });

        $devices = DB::table('master_devices')
            ->where('status_aktif', 1)
            ->orderByDesc('last_seen_at')
            ->orderBy('nama_lokasi')
            ->get();

        $allDevices = DB::table('master_devices')
            ->leftJoin('sesi_device_allocations', 'master_devices.device_id', '=', 'sesi_device_allocations.device_id')
            ->leftJoin('master_sesis', 'master_devices.device_id', '=', 'master_sesis.device_id_target')
            ->select(
                'master_devices.*',
                DB::raw('COUNT(DISTINCT COALESCE(sesi_device_allocations.master_sesi_id, master_sesis.id)) as total_sesi')
            )
            ->groupBy(
                'master_devices.id',
                'master_devices.device_id',
                'master_devices.nama_lokasi',
                'master_devices.kategori_area',
                'master_devices.ip_address',
                'master_devices.status_aktif',
                'master_devices.last_seen_at',
                'master_devices.created_at',
                'master_devices.updated_at'
            )
            ->orderByDesc('master_devices.last_seen_at')
            ->orderBy('master_devices.device_id')
            ->get();

        $kamars = Kamar::daftarNamaKamarAktif();

        return view('pengaturan-sesi', compact('sesis', 'devices', 'allDevices', 'kamars'));
    }

    /**
     * Halaman Penuh Tambah Sesi Presensi Baru.
     */
    public function create()
    {
        $devices = DB::table('master_devices')
            ->where('status_aktif', 1)
            ->orderByDesc('last_seen_at')
            ->orderBy('nama_lokasi')
            ->get();
        $kamars = Kamar::daftarNamaKamarAktif();
        $kelasPengajians = MasterKelasPengajian::orderBy('nama_kelas')->get();
        $allocations = collect();

        return view('pengaturan-sesi.form', compact('devices', 'kamars', 'kelasPengajians', 'allocations'));
    }

    /**
     * Halaman Penuh Edit Pengaturan Sesi Presensi.
     */
    public function edit($id)
    {
        $sesi = MasterSesi::with(['allocations.device'])->find($id);
        if (!$sesi) {
            return redirect()->route('pengaturan.sesi')->with('error', 'Data sesi tidak ditemukan.');
        }

        $devices = DB::table('master_devices')
            ->where('status_aktif', 1)
            ->orderByDesc('last_seen_at')
            ->orderBy('nama_lokasi')
            ->get();
        $kamars = Kamar::daftarNamaKamarAktif();
        $kelasPengajians = MasterKelasPengajian::orderBy('nama_kelas')->get();
        $allocations = $sesi->allocations;

        return view('pengaturan-sesi.form', compact('sesi', 'devices', 'kamars', 'kelasPengajians', 'allocations'));
    }

    /**
     * Endpoint JSON Live Auto-Detect Serial ESP32 terakhir yang menyala / tap.
     */
    public function lastDetectedDevice()
    {
        $data = null;

        foreach (['/tmp/rfid_last_device.json', '/tmp/rfid_last_tap.json'] as $file) {
            if (file_exists($file)) {
                $decoded = json_decode((string) file_get_contents($file), true);
                if (is_array($decoded) && !empty($decoded['device_id'])) {
                    $data = $decoded;
                    break;
                }
            }
        }

        if (!$data) {
            // Fallback ke perangkat terbaru yang terdeteksi di tabel master_devices
            $latestDb = DB::table('master_devices')
                ->whereNotNull('last_seen_at')
                ->orderByDesc('last_seen_at')
                ->first();

            if ($latestDb) {
                return response()->json([
                    'device_id' => $latestDb->device_id,
                    'ip' => $latestDb->ip_address,
                    'timestamp' => $latestDb->last_seen_at,
                    'already_registered' => true,
                    'nama_lokasi' => $latestDb->nama_lokasi,
                ]);
            }

            return response()->json(['device_id' => null]);
        }

        $existing = DB::table('master_devices')
            ->where('device_id', $data['device_id'])
            ->first();

        return response()->json([
            'device_id' => $data['device_id'],
            'ip' => $data['ip'] ?? ($existing->ip_address ?? null),
            'timestamp' => $data['timestamp'] ?? null,
            'already_registered' => (bool) $existing,
            'nama_lokasi' => $existing->nama_lokasi ?? null,
        ]);
    }

    /**
     * Tambah Sesi Presensi Baru.
     */
    public function store(Request $request)
    {
        if ($request->filled('kode_sesi')) {
            $request->merge([
                'kode_sesi' => strtoupper(preg_replace('/\s+/', '_', trim((string) $request->kode_sesi))),
            ]);
        }

        $validated = $request->validate([
            'kode_sesi' => ['required', 'string', 'max:30', 'unique:master_sesis,kode_sesi'],
            'nama_sesi' => ['required', 'string', 'max:100'],
            'jam_mulai' => ['required'],
            'jam_selesai' => ['required'],
            'toleransi_menit' => ['nullable', 'integer', 'min:0', 'max:240'],
            'device_id_target' => ['nullable', 'string', 'max:50', 'exists:master_devices,device_id'],
            'kategori' => ['nullable', Rule::in(['sholat', 'pengajian', 'sekolah', 'makan', 'pegawai'])],
            'target_kategori' => ['nullable', Rule::in(['semua_santri', 'kamar_khusus', 'kelas_pengajian', 'sekolah', 'pegawai'])],
            'target_filter' => ['nullable'],
        ], [
            'kode_sesi.required' => 'Kode sesi wajib diisi.',
            'kode_sesi.unique' => 'Kode sesi tersebut sudah digunakan, silakan pilih kode lain.',
            'nama_sesi.required' => 'Nama sesi wajib diisi.',
            'jam_mulai.required' => 'Jam mulai sesi wajib diisi.',
            'jam_selesai.required' => 'Jam selesai sesi wajib diisi.',
        ]);

        $targetKategori = $validated['target_kategori'] ?? 'semua_santri';
        $rawTargetFilter = $request->input('target_filter');

        $legacyGender = 'ALL';
        if (in_array($targetKategori, ['kamar_khusus', 'sekolah'], true)) {
            if (is_array($rawTargetFilter)) {
                $filtered = array_filter(array_map('trim', $rawTargetFilter));
                $targetFilter = !empty($filtered) ? implode(', ', $filtered) : null;
            } else {
                $targetFilter = trim((string) $rawTargetFilter) ?: null;
            }
        } elseif ($targetKategori === 'kelas_pengajian') {
            $rawKelas = $request->input('target_filter_kelas', $rawTargetFilter);
            if (is_array($rawKelas)) {
                $filteredKelas = array_filter(array_map('trim', $rawKelas));
                $kelasStr = !empty($filteredKelas) ? implode(', ', $filteredKelas) : '';
            } else {
                $kelasStr = trim((string) $rawKelas);
            }

            $genderInput = strtolower(trim((string) $request->input('target_filter_gender', 'semua')));
            if ($genderInput === 'putra') {
                $legacyGender = 'L';
                $targetFilter = $kelasStr ? "{$kelasStr} [Gender: Putra]" : '[Gender: Putra]';
            } elseif ($genderInput === 'putri') {
                $legacyGender = 'P';
                $targetFilter = $kelasStr ? "{$kelasStr} [Gender: Putri]" : '[Gender: Putri]';
            } else {
                $targetFilter = $kelasStr ?: null;
            }
        } else {
            $targetFilter = null;
        }

        $fallbackLegacy = [
            'device_id' => !empty($validated['device_id_target']) ? $validated['device_id_target'] : null,
            'target_kategori' => match($targetKategori) {
                'kamar_khusus' => 'kamar',
                'sekolah' => 'kelas_sekolah',
                'kelas_pengajian' => 'kelas_pengajian',
                'pegawai' => 'pegawai',
                default => 'semua',
            },
            'target_filter' => $targetFilter,
            'target_gender' => $legacyGender,
        ];

        $allocations = $this->parseAllocationsFromRequest($request, $fallbackLegacy);

        $firstAlloc = $allocations[0] ?? null;
        $legacyDev = count($allocations) === 1 ? ($firstAlloc['device_id'] ?? null) : null;
        $legacyKat = $firstAlloc ? match($firstAlloc['target_kategori']) {
            'kamar' => 'kamar_khusus',
            'kelas_sekolah' => 'sekolah',
            'kelas_pengajian' => 'kelas_pengajian',
            'pegawai' => 'pegawai',
            default => 'semua_santri'
        } : $targetKategori;
        $legacyFil = $firstAlloc ? $firstAlloc['target_filter'] : $targetFilter;

        $sesiId = DB::table('master_sesis')->insertGetId([
            'device_id_target' => $legacyDev,
            'kode_sesi' => $validated['kode_sesi'],
            'nama_sesi' => trim($validated['nama_sesi']),
            'jam_mulai' => $this->normalizeTime($validated['jam_mulai']),
            'jam_selesai' => $this->normalizeTime($validated['jam_selesai']),
            'toleransi_menit' => isset($validated['toleransi_menit']) ? (int) $validated['toleransi_menit'] : 15,
            'kategori' => $validated['kategori'] ?? 'sholat',
            'target_kategori' => $legacyKat,
            'target_filter' => $legacyFil,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($allocations as $alloc) {
            DB::table('sesi_device_allocations')->insert([
                'master_sesi_id' => $sesiId,
                'device_id' => $alloc['device_id'],
                'target_kategori' => $alloc['target_kategori'],
                'target_filter' => $alloc['target_filter'],
                'target_gender' => $alloc['target_gender'] ?? 'ALL',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('pengaturan.sesi')->with('success', 'Sesi presensi baru berhasil ditambahkan.');
    }

    /**
     * Perbarui Pengaturan Sesi Presensi.
     */
    public function update(Request $request, $id)
    {
        $existing = DB::table('master_sesis')->where('id', $id)->first();
        if (!$existing) {
            return redirect()->route('pengaturan.sesi')->with('error', 'Data sesi tidak ditemukan.');
        }

        if ($request->filled('kode_sesi')) {
            $request->merge([
                'kode_sesi' => strtoupper(preg_replace('/\s+/', '_', trim((string) $request->kode_sesi))),
            ]);
        }

        $validated = $request->validate([
            'kode_sesi' => ['nullable', 'string', 'max:30', Rule::unique('master_sesis', 'kode_sesi')->ignore($id)],
            'nama_sesi' => ['required', 'string', 'max:100'],
            'jam_mulai' => ['required'],
            'jam_selesai' => ['required'],
            'toleransi_menit' => ['nullable', 'integer', 'min:0', 'max:240'],
            'device_id_target' => ['nullable', 'string', 'max:50', 'exists:master_devices,device_id'],
            'kategori' => ['required', Rule::in(['sholat', 'pengajian', 'sekolah', 'makan', 'pegawai'])],
            'target_kategori' => ['nullable', Rule::in(['semua_santri', 'kamar_khusus', 'kelas_pengajian', 'sekolah', 'pegawai'])],
            'target_filter' => ['nullable'],
        ]);

        $targetKategori = $validated['target_kategori'] ?? 'semua_santri';
        $rawTargetFilter = $request->input('target_filter');

        $legacyGender = 'ALL';
        if (in_array($targetKategori, ['kamar_khusus', 'sekolah'], true)) {
            if (is_array($rawTargetFilter)) {
                $filtered = array_filter(array_map('trim', $rawTargetFilter));
                $targetFilter = !empty($filtered) ? implode(', ', $filtered) : null;
            } else {
                $targetFilter = trim((string) $rawTargetFilter) ?: null;
            }
        } elseif ($targetKategori === 'kelas_pengajian') {
            $rawKelas = $request->input('target_filter_kelas', $rawTargetFilter);
            if (is_array($rawKelas)) {
                $filteredKelas = array_filter(array_map('trim', $rawKelas));
                $kelasStr = !empty($filteredKelas) ? implode(', ', $filteredKelas) : '';
            } else {
                $kelasStr = trim((string) $rawKelas);
            }

            $genderInput = strtolower(trim((string) $request->input('target_filter_gender', 'semua')));
            if ($genderInput === 'putra') {
                $legacyGender = 'L';
                $targetFilter = $kelasStr ? "{$kelasStr} [Gender: Putra]" : '[Gender: Putra]';
            } elseif ($genderInput === 'putri') {
                $legacyGender = 'P';
                $targetFilter = $kelasStr ? "{$kelasStr} [Gender: Putri]" : '[Gender: Putri]';
            } else {
                $targetFilter = $kelasStr ?: null;
            }
        } else {
            $targetFilter = null;
        }

        $fallbackLegacy = [
            'device_id' => !empty($validated['device_id_target']) ? $validated['device_id_target'] : null,
            'target_kategori' => match($targetKategori) {
                'kamar_khusus' => 'kamar',
                'sekolah' => 'kelas_sekolah',
                'kelas_pengajian' => 'kelas_pengajian',
                'pegawai' => 'pegawai',
                default => 'semua',
            },
            'target_filter' => $targetFilter,
            'target_gender' => $legacyGender,
        ];

        $allocations = $this->parseAllocationsFromRequest($request, $fallbackLegacy);

        $firstAlloc = $allocations[0] ?? null;
        $legacyDev = count($allocations) === 1 ? ($firstAlloc['device_id'] ?? null) : null;
        $legacyKat = $firstAlloc ? match($firstAlloc['target_kategori']) {
            'kamar' => 'kamar_khusus',
            'kelas_sekolah' => 'sekolah',
            'kelas_pengajian' => 'kelas_pengajian',
            'pegawai' => 'pegawai',
            default => 'semua_santri'
        } : $targetKategori;
        $legacyFil = $firstAlloc ? $firstAlloc['target_filter'] : $targetFilter;

        $updatePayload = [
            'device_id_target' => $legacyDev,
            'nama_sesi' => trim($validated['nama_sesi']),
            'jam_mulai' => $this->normalizeTime($validated['jam_mulai']),
            'jam_selesai' => $this->normalizeTime($validated['jam_selesai']),
            'toleransi_menit' => isset($validated['toleransi_menit']) ? (int) $validated['toleransi_menit'] : 15,
            'kategori' => $validated['kategori'],
            'target_kategori' => $legacyKat,
            'target_filter' => $legacyFil,
            'updated_at' => now(),
        ];

        if (!empty($validated['kode_sesi'])) {
            $updatePayload['kode_sesi'] = $validated['kode_sesi'];
        }

        DB::table('master_sesis')->where('id', $id)->update($updatePayload);

        // Sinkronisasi alokasi perangkat
        DB::table('sesi_device_allocations')->where('master_sesi_id', $id)->delete();
        foreach ($allocations as $alloc) {
            DB::table('sesi_device_allocations')->insert([
                'master_sesi_id' => $id,
                'device_id' => $alloc['device_id'],
                'target_kategori' => $alloc['target_kategori'],
                'target_filter' => $alloc['target_filter'],
                'target_gender' => $alloc['target_gender'] ?? 'ALL',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('pengaturan.sesi')->with('success', 'Pengaturan sesi berhasil diperbarui.');
    }

    /**
     * Hapus Sesi Presensi.
     */
    public function destroy($id)
    {
        DB::table('master_sesis')->where('id', $id)->delete();

        return redirect()->route('pengaturan.sesi')->with('success', 'Sesi berhasil dihapus.');
    }

    /**
     * Hapus Massal Sesi Presensi Terpilih.
     */
    public function destroyBulk(Request $request)
    {
        $raw = $request->input('sesi_ids', '');
        $items = is_array($raw) ? $raw : explode(',', (string) $raw);
        $ids = collect($items)
            ->map(fn ($id) => (int) trim((string) $id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return redirect()->route('pengaturan.sesi')->with('error', 'Tidak ada sesi yang dipilih untuk dihapus.');
        }

        $deleted = DB::table('master_sesis')->whereIn('id', $ids)->delete();

        return redirect()->route('pengaturan.sesi')->with('success', "{$deleted} sesi terpilih berhasil dihapus.");
    }

    /**
     * Tambah / Simpan Perangkat ESP32 (Serial Unik) ke Master Devices.
     */
    public function storeDevice(Request $request)
    {
        if ($request->filled('device_id')) {
            $request->merge([
                'device_id' => preg_replace('/\s+/', '_', trim((string) $request->device_id)),
            ]);
        }

        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:50'],
            'nama_lokasi' => ['required', 'string', 'max:100'],
            'kategori_area' => ['required', Rule::in(['pondok', 'sekolah', 'dapur', 'umum'])],
        ], [
            'device_id.required' => 'Serial / ID Perangkat ESP32 wajib diisi.',
            'nama_lokasi.required' => 'Nama lokasi pemasangan wajib diisi.',
        ]);

        // Gunakan updateOrInsert agar jika serial sudah auto-register oleh daemon, tinggal memperbarui lokasinya
        $existing = DB::table('master_devices')->where('device_id', $validated['device_id'])->first();

        if ($existing) {
            DB::table('master_devices')->where('id', $existing->id)->update([
                'nama_lokasi' => trim($validated['nama_lokasi']),
                'kategori_area' => $validated['kategori_area'],
                'status_aktif' => 1,
                'updated_at' => now(),
            ]);
            $msg = 'Lokasi perangkat ' . $validated['device_id'] . ' berhasil diperbarui.';
        } else {
            DB::table('master_devices')->insert([
                'device_id' => $validated['device_id'],
                'nama_lokasi' => trim($validated['nama_lokasi']),
                'kategori_area' => $validated['kategori_area'],
                'status_aktif' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $msg = 'Perangkat ESP32 (' . $validated['device_id'] . ') berhasil didaftarkan.';
        }

        return redirect()->route('pengaturan.sesi', ['tab' => 'devices'])
            ->with('success', $msg);
    }

    /**
     * Edit Nama Lokasi & Kategori Area Perangkat ESP32.
     */
    public function updateDevice(Request $request, $id)
    {
        $device = DB::table('master_devices')->where('id', $id)->first();
        if (!$device) {
            return redirect()->route('pengaturan.sesi', ['tab' => 'devices'])
                ->with('error', 'Perangkat tidak ditemukan.');
        }

        $validated = $request->validate([
            'nama_lokasi' => ['required', 'string', 'max:100'],
            'kategori_area' => ['required', Rule::in(['pondok', 'sekolah', 'dapur', 'umum'])],
        ]);

        DB::table('master_devices')->where('id', $id)->update([
            'nama_lokasi' => trim($validated['nama_lokasi']),
            'kategori_area' => $validated['kategori_area'],
            'updated_at' => now(),
        ]);

        return redirect()->route('pengaturan.sesi', ['tab' => 'devices'])
            ->with('success', 'Nama lokasi perangkat ' . $device->device_id . ' berhasil disimpan.');
    }

    /**
     * Aktifkan / Nonaktifkan Status Perangkat ESP32.
     */
    public function toggleDevice($id)
    {
        $device = DB::table('master_devices')->where('id', $id)->first();
        if ($device) {
            DB::table('master_devices')->where('id', $id)->update([
                'status_aktif' => $device->status_aktif ? 0 : 1,
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('pengaturan.sesi', ['tab' => 'devices'])
            ->with('success', 'Status perangkat ESP32 berhasil diperbarui.');
    }

    /**
     * Hapus Perangkat ESP32 (Sesi yang memakainya otomatis dikembalikan ke Global/NULL).
     */
    public function destroyDevice($id)
    {
        $device = DB::table('master_devices')->where('id', $id)->first();
        if ($device) {
            DB::table('master_sesis')
                ->where('device_id_target', $device->device_id)
                ->update(['device_id_target' => null]);

            DB::table('master_devices')->where('id', $id)->delete();
        }

        return redirect()->route('pengaturan.sesi', ['tab' => 'devices'])
            ->with('success', 'Perangkat berhasil dihapus dan sesi terkait dikembalikan ke Global.');
    }

    /**
     * Pastikan format waktu konsisten HH:MM:SS.
     */
    private function normalizeTime(string $time): string
    {
        $clean = trim($time);
        if (preg_match('/^\d{2}:\d{2}$/', $clean)) {
            return $clean . ':00';
        }
        return $clean;
    }

    /**
     * Parsing dan normalisasi data alokasi perangkat dari request.
     */
    private function parseAllocationsFromRequest(Request $request, array $fallbackLegacy = []): array
    {
        $allocationsInput = $request->input('allocations');
        if (empty($allocationsInput) && $request->filled('allocations_json')) {
            $decoded = json_decode((string) $request->input('allocations_json'), true);
            if (is_array($decoded)) {
                $allocationsInput = $decoded;
            }
        }

        $processed = [];
        if (is_array($allocationsInput) && !empty($allocationsInput)) {
            foreach ($allocationsInput as $alloc) {
                if (!is_array($alloc)) continue;

                $devId = !empty($alloc['device_id']) && $alloc['device_id'] !== 'ALL' ? trim((string)$alloc['device_id']) : null;
                $kat = !empty($alloc['target_kategori']) ? trim((string)$alloc['target_kategori']) : 'semua';
                if ($kat === 'semua_santri') $kat = 'semua';
                if ($kat === 'kamar_khusus') $kat = 'kamar';
                if ($kat === 'sekolah') $kat = 'kelas_sekolah';

                $gender = !empty($alloc['target_gender']) ? strtoupper(trim((string)$alloc['target_gender'])) : 'ALL';
                if (!in_array($gender, ['ALL', 'L', 'P'], true)) {
                    $gender = 'ALL';
                }

                $rawFilter = $alloc['target_filter'] ?? null;
                if (is_array($rawFilter)) {
                    $filterStr = implode(', ', array_filter(array_map('trim', $rawFilter)));
                } else {
                    $filterStr = trim((string)$rawFilter);
                }

                $processed[] = [
                    'device_id' => $devId,
                    'target_kategori' => $kat,
                    'target_filter' => $filterStr ?: null,
                    'target_gender' => $gender,
                ];
            }
        }

        if (empty($processed) && !empty($fallbackLegacy)) {
            $processed[] = $fallbackLegacy;
        }

        return $processed;
    }
}
