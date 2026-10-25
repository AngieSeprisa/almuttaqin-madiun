<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\MataPelajaran;
use App\Models\Schedule;
use App\Models\TimeSlot;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BerandaMobileViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
    }

    /**
     * Test beranda renders with greeting and ticker, while lembaga badge and semester are removed.
     */
    public function test_beranda_renders_mobile_ready_components(): void
    {
        $user = User::where('email', 'admin.smp@almuttaqin.id')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)
            ->withSession(['active_room' => 'smp'])
            ->get('/beranda');

        $response->assertStatus(200);

        // Verify Hero Greeting remains
        $response->assertSee('Halo, Admin', false);
        $response->assertSee('beranda-hero-card', false);
        $response->assertSee('beranda-ticker-wrapper', false);

        // Verify tulisan lembaga, semester, and role badge are removed
        $response->assertDontSee('beranda-hero-badge', false);
        $response->assertDontSee('Semester Gasal TA 2026/2027', false);
        $response->assertDontSee('beranda-hero-role', false);

        // Verify Stats Grid
        $response->assertSee('stats-grid', false);
        $response->assertSee('Siswa SMP', false);
        $response->assertSee('Aktif di Asrama', false);
        $response->assertSee('Izin Pulang', false);
        $response->assertSee('Izin Sakit', false);

        // Verify redundant quick actions and distribution cards are removed
        $response->assertDontSee('beranda-quick-actions', false);
        $response->assertDontSee('Distribusi Santri Antar Lembaga', false);
    }

    /**
     * Test fallback announcement text when no schedules/agendas exist.
     */
    public function test_beranda_shows_empty_announcement_when_no_schedule(): void
    {
        $user = User::where('email', 'yayasan@almuttaqin.id')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)
            ->withSession(['active_room' => 'yayasan'])
            ->get('/beranda');

        $response->assertStatus(200);
        $response->assertSee('Halo, Pimpinan', false);
        // Default on Sunday or days without seeded schedule for today:
        $response->assertSee('tidak ada jadwal mengajar / tidak ada agenda hari ini', false);
    }

    /**
     * Test ticker renders schedules and agendas when present today.
     */
    public function test_beranda_renders_rotating_ticker_when_schedule_exists(): void
    {
        $user = User::where('email', 'admin.smp@almuttaqin.id')->first();
        $this->assertNotNull($user);

        $hariMap = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
        $hariIni = $hariMap[(int)date('w')];

        // Create time slot for today
        $slot1 = TimeSlot::create([
            'jenjang'       => 'SMP',
            'hari'          => $hariIni,
            'jam_ke'        => 1,
            'waktu_mulai'   => '07:30',
            'waktu_selesai' => '08:15',
            'is_break'      => false,
            'tipe_kegiatan' => 'KBM',
            'label'         => 'Jam 1',
            'urutan'        => 1,
            'is_active'     => true,
        ]);

        $slotBreak = TimeSlot::create([
            'jenjang'       => 'SMP',
            'hari'          => $hariIni,
            'jam_ke'        => 0,
            'waktu_mulai'   => '09:30',
            'waktu_selesai' => '10:00',
            'is_break'      => true,
            'tipe_kegiatan' => 'Istirahat',
            'label'         => 'Kudapan & Istirahat Pagi',
            'urutan'        => 2,
            'is_active'     => true,
        ]);

        $smpLembaga = \App\Models\Lembaga::where('kode', 'smp')->first();

        $classroom = Classroom::firstOrCreate(
            ['nama_kelas' => '7A'],
            ['lembaga_id' => $smpLembaga->id, 'jenjang' => 'SMP', 'tingkat' => 7, 'urutan' => 1, 'is_active' => true]
        );

        $mapel = MataPelajaran::firstOrCreate(
            ['mata_pelajaran' => 'Matematika'],
            ['lembaga_id' => $smpLembaga->id, 'kode' => 'MTK']
        );

        Schedule::create([
            'classroom_id' => $classroom->id,
            'time_slot_id' => $slot1->id,
            'subject_id'   => $mapel->id,
            'teacher_id'   => $user->id,
            'tahun_ajaran' => '2026/2027',
            'semester'     => 'Gasal',
            'is_locked'    => false,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_room' => 'smp'])
            ->get('/beranda');

        $response->assertStatus(200);
        $response->assertSee('beranda-ticker-carousel', false);
        $response->assertSee('beranda-ticker-item', false);
        $response->assertSee('Jadwal Mengajar', false);
        $response->assertSee('Matematika', false);
        $response->assertSee('Kudapan &amp; Istirahat Pagi', false);
    }
}
