<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimeSlotController extends Controller
{
    /**
     * Daftar hari sekolah
     */
    public const DAFTAR_HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    /**
     * Tampilkan daftar master jam pelajaran / time slot
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && !$authUser->canDo('master_jam', 'view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka Master Jam Pelajaran.');
        }

        $activeHari = $request->get('hari', 'Senin');
        $selectedJenjang = $request->get('jenjang', 'all');

        $query = TimeSlot::query();

        if ($activeHari && $activeHari !== 'all') {
            $query->where('hari', ucfirst(strtolower($activeHari)));
        }

        if ($selectedJenjang && $selectedJenjang !== 'all') {
            $query->where(function ($q) use ($selectedJenjang) {
                $q->where('jenjang', $selectedJenjang)
                  ->orWhere('jenjang', 'Semua');
            });
        }

        $timeSlots = $query->orderBy('hari')
                           ->orderBy('urutan')
                           ->orderBy('waktu_mulai')
                           ->get();

        // Hitung slot per hari untuk badge di tab
        $slotCountPerHari = [];
        foreach (self::DAFTAR_HARI as $h) {
            $slotCountPerHari[$h] = TimeSlot::where('hari', $h)->count();
        }

        // Statistik umum
        $stats = [
            'total'     => TimeSlot::count(),
            'kbm'       => TimeSlot::where('is_break', false)->count(),
            'non_kbm'   => TimeSlot::where('is_break', true)->count(),
            'aktif'     => TimeSlot::where('is_active', true)->count(),
        ];

        $daftarHari = self::DAFTAR_HARI;

        return view('master.jam-pelajaran.index', compact('timeSlots', 'activeHari', 'selectedJenjang', 'slotCountPerHari', 'stats', 'daftarHari'));
    }

    /**
     * Simpan slot jam pelajaran baru (bisa untuk satu atau beberapa hari sekaligus)
     */
    public function store(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('master_jam', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah jam pelajaran.');
        }

        $validated = $request->validate([
            'hari_list'     => 'required|array|min:1',
            'hari_list.*'   => 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'jenjang'       => 'required|in:Semua,SMP,SMA',
            'jam_ke'        => 'required|integer|min:0|max:20',
            'waktu_mulai'   => 'required|date_format:H:i',
            'waktu_selesai' => 'required|date_format:H:i|after:waktu_mulai',
            'tipe_kegiatan' => 'required|string|max:50',
            'label'         => 'nullable|string|max:100',
            'urutan'        => 'nullable|integer',
            'is_break'      => 'nullable|boolean',
            'is_active'     => 'nullable|boolean',
        ], [
            'hari_list.required' => 'Pilih minimal satu hari kegiatan.',
            'waktu_selesai.after' => 'Waktu selesai harus lebih besar daripada waktu mulai.',
        ]);

        $isBreak = $request->has('is_break') || in_array($validated['tipe_kegiatan'], ['Istirahat', 'Upacara', 'Sholat Dhuhur']);
        $isActive = $request->has('is_active');
        $label = $validated['label'] ?: ($validated['tipe_kegiatan'] === 'KBM' ? "Jam {$validated['jam_ke']}" : $validated['tipe_kegiatan']);

        $createdCount = 0;

        foreach ($validated['hari_list'] as $hari) {
            $urutan = $validated['urutan'] ?? ((TimeSlot::where('hari', $hari)->max('urutan') ?? 0) + 1);

            TimeSlot::create([
                'jenjang'       => $validated['jenjang'],
                'hari'          => $hari,
                'jam_ke'        => $validated['jam_ke'],
                'waktu_mulai'   => $validated['waktu_mulai'],
                'waktu_selesai' => $validated['waktu_selesai'],
                'is_break'      => $isBreak,
                'tipe_kegiatan' => $validated['tipe_kegiatan'],
                'label'         => $label,
                'urutan'        => $urutan,
                'is_active'     => $isActive,
            ]);
            $createdCount++;
        }

        $targetHari = count($validated['hari_list']) === 1 ? $validated['hari_list'][0] : 'all';

        return redirect()->route('master.jam-pelajaran.index', ['hari' => $targetHari])
                         ->with('success', "Berhasil menambahkan {$createdCount} slot jam pelajaran ({$label}).");
    }

    /**
     * Perbarui slot jam pelajaran
     */
    public function update(Request $request, TimeSlot $jamPelajaran)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('master_jam', 'edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah jam pelajaran.');
        }

        $validated = $request->validate([
            'hari'          => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'jenjang'       => 'required|in:Semua,SMP,SMA',
            'jam_ke'        => 'required|integer|min:0|max:20',
            'waktu_mulai'   => 'required|date_format:H:i',
            'waktu_selesai' => 'required|date_format:H:i|after:waktu_mulai',
            'tipe_kegiatan' => 'required|string|max:50',
            'label'         => 'nullable|string|max:100',
            'urutan'        => 'nullable|integer',
            'is_break'      => 'nullable|boolean',
            'is_active'     => 'nullable|boolean',
        ], [
            'waktu_selesai.after' => 'Waktu selesai harus lebih besar daripada waktu mulai.',
        ]);

        $isBreak = $request->has('is_break') || in_array($validated['tipe_kegiatan'], ['Istirahat', 'Upacara', 'Sholat Dhuhur']);
        $isActive = $request->has('is_active');
        $label = $validated['label'] ?: ($validated['tipe_kegiatan'] === 'KBM' ? "Jam {$validated['jam_ke']}" : $validated['tipe_kegiatan']);

        $jamPelajaran->update([
            'hari'          => $validated['hari'],
            'jenjang'       => $validated['jenjang'],
            'jam_ke'        => $validated['jam_ke'],
            'waktu_mulai'   => $validated['waktu_mulai'],
            'waktu_selesai' => $validated['waktu_selesai'],
            'is_break'      => $isBreak,
            'tipe_kegiatan' => $validated['tipe_kegiatan'],
            'label'         => $label,
            'urutan'        => $validated['urutan'] ?? $jamPelajaran->urutan,
            'is_active'     => $isActive,
        ]);

        return redirect()->route('master.jam-pelajaran.index', ['hari' => $jamPelajaran->hari])
                         ->with('success', "Slot jam pelajaran '{$label}' berhasil diperbarui.");
    }

    /**
     * Toggle status aktif slot jam pelajaran
     */
    public function toggleStatus(TimeSlot $jamPelajaran)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('master_jam', 'edit')) {
            abort(403, 'Anda tidak memiliki hak akses mengubah status jam pelajaran.');
        }

        $jamPelajaran->update(['is_active' => !$jamPelajaran->is_active]);

        $statusText = $jamPelajaran->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Slot '{$jamPelajaran->label}' berhasil {$statusText}.");
    }

    /**
     * Hapus slot jam pelajaran dengan proteksi dependensi jadwal
     */
    public function destroy(TimeSlot $jamPelajaran)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('master_jam', 'delete')) {
            abort(403, 'Anda tidak memiliki hak akses menghapus jam pelajaran.');
        }

        $scheduleCount = Schedule::where('time_slot_id', $jamPelajaran->id)->count();
        if ($scheduleCount > 0) {
            return redirect()->back()->with('error', "Slot jam '{$jamPelajaran->label}' tidak dapat dihapus karena masih digunakan dalam {$scheduleCount} jadwal mengajar guru.");
        }

        $hari = $jamPelajaran->hari;
        $label = $jamPelajaran->label;
        $jamPelajaran->delete();

        return redirect()->route('master.jam-pelajaran.index', ['hari' => $hari])
                         ->with('success', "Slot '{$label}' ({$hari}) berhasil dihapus.");
    }

    /**
     * Muat slot standar otomatis (Senin-Jumat lengkap KBM 1-8, Upacara, Istirahat, Isoma)
     */
    public function muatStandar(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        if (!$authUser->isYayasan() && !$authUser->canDo('master_jam', 'create')) {
            abort(403, 'Anda tidak memiliki hak akses.');
        }

        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $created = 0;

        foreach ($days as $day) {
            if ($day === 'Senin') {
                $slots = [
                    ['jam_ke' => 0, 'mulai' => '07:00', 'selesai' => '07:45', 'break' => true,  'tipe' => 'Upacara',       'label' => 'Upacara Bendera'],
                    ['jam_ke' => 1, 'mulai' => '07:45', 'selesai' => '08:30', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 1'],
                    ['jam_ke' => 2, 'mulai' => '08:30', 'selesai' => '09:15', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 2'],
                    ['jam_ke' => 3, 'mulai' => '09:15', 'selesai' => '10:00', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 3'],
                    ['jam_ke' => 0, 'mulai' => '10:00', 'selesai' => '10:20', 'break' => true,  'tipe' => 'Istirahat',     'label' => 'Istirahat 1'],
                    ['jam_ke' => 4, 'mulai' => '10:20', 'selesai' => '11:05', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 4'],
                    ['jam_ke' => 5, 'mulai' => '11:05', 'selesai' => '11:50', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 5'],
                    ['jam_ke' => 0, 'mulai' => '11:50', 'selesai' => '12:45', 'break' => true,  'tipe' => 'Sholat Dhuhur', 'label' => 'Sholat Dhuhur & Isoma'],
                    ['jam_ke' => 6, 'mulai' => '12:45', 'selesai' => '13:30', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 6'],
                    ['jam_ke' => 7, 'mulai' => '13:30', 'selesai' => '14:15', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 7'],
                    ['jam_ke' => 8, 'mulai' => '14:15', 'selesai' => '15:00', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 8'],
                ];
            } elseif ($day === 'Jumat') {
                $slots = [
                    ['jam_ke' => 1, 'mulai' => '07:00', 'selesai' => '07:45', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 1'],
                    ['jam_ke' => 2, 'mulai' => '07:45', 'selesai' => '08:30', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 2'],
                    ['jam_ke' => 3, 'mulai' => '08:30', 'selesai' => '09:15', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 3'],
                    ['jam_ke' => 0, 'mulai' => '09:15', 'selesai' => '09:45', 'break' => true,  'tipe' => 'Istirahat',     'label' => 'Istirahat & Kudapan'],
                    ['jam_ke' => 4, 'mulai' => '09:45', 'selesai' => '10:30', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 4'],
                    ['jam_ke' => 5, 'mulai' => '10:30', 'selesai' => '11:15', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 5'],
                    ['jam_ke' => 0, 'mulai' => '11:15', 'selesai' => '13:00', 'break' => true,  'tipe' => 'Sholat Dhuhur', 'label' => 'Sholat Jumat & Istirahat'],
                    ['jam_ke' => 6, 'mulai' => '13:00', 'selesai' => '13:45', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 6'],
                    ['jam_ke' => 7, 'mulai' => '13:45', 'selesai' => '14:30', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 7'],
                    ['jam_ke' => 8, 'mulai' => '14:30', 'selesai' => '15:15', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 8'],
                ];
            } else {
                $slots = [
                    ['jam_ke' => 1, 'mulai' => '07:00', 'selesai' => '07:45', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 1'],
                    ['jam_ke' => 2, 'mulai' => '07:45', 'selesai' => '08:30', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 2'],
                    ['jam_ke' => 3, 'mulai' => '08:30', 'selesai' => '09:15', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 3'],
                    ['jam_ke' => 4, 'mulai' => '09:15', 'selesai' => '10:00', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 4'],
                    ['jam_ke' => 0, 'mulai' => '10:00', 'selesai' => '10:20', 'break' => true,  'tipe' => 'Istirahat',     'label' => 'Istirahat'],
                    ['jam_ke' => 5, 'mulai' => '10:20', 'selesai' => '11:05', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 5'],
                    ['jam_ke' => 6, 'mulai' => '11:05', 'selesai' => '11:50', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 6'],
                    ['jam_ke' => 0, 'mulai' => '11:50', 'selesai' => '12:45', 'break' => true,  'tipe' => 'Sholat Dhuhur', 'label' => 'Sholat Dhuhur & Isoma'],
                    ['jam_ke' => 7, 'mulai' => '12:45', 'selesai' => '13:30', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 7'],
                    ['jam_ke' => 8, 'mulai' => '13:30', 'selesai' => '14:15', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 8'],
                ];
            }

            foreach ($slots as $idx => $s) {
                $item = TimeSlot::firstOrCreate(
                    [
                        'jenjang'     => 'Semua',
                        'hari'        => $day,
                        'jam_ke'      => $s['jam_ke'],
                        'waktu_mulai' => $s['mulai'],
                    ],
                    [
                        'waktu_selesai' => $s['selesai'],
                        'is_break'      => $s['break'],
                        'tipe_kegiatan' => $s['tipe'],
                        'label'         => $s['label'],
                        'urutan'        => $idx + 1,
                        'is_active'     => true,
                    ]
                );
                if ($item->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        return redirect()->route('master.jam-pelajaran.index', ['hari' => 'Senin'])
                         ->with('success', "Berhasil memeriksa dan menambahkan {$created} slot jam standar.");
    }
}
