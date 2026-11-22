<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanGelombang extends Model
{
    use HasFactory;

    protected $fillable = [
        'penerimaan_santri_id',
        'nama_gelombang',
        'tanggal_mulai',
        'tanggal_selesai',
        'kuota',
        'kuota_per_jenjang',
        'urutan',
    ];

    protected $casts = [
        'tanggal_mulai'     => 'date',
        'tanggal_selesai'   => 'date',
        'kuota'             => 'integer',
        'kuota_per_jenjang' => 'array',
        'urutan'            => 'integer',
    ];

    public function penerimaan()
    {
        return $this->belongsTo(PenerimaanSantri::class, 'penerimaan_santri_id');
    }

    public function pendaftarans()
    {
        return $this->hasMany(Pendaftaran::class, 'penerimaan_gelombang_id');
    }

    /**
     * Kuota untuk lembaga/jenjang tertentu (null = tidak dibatasi).
     */
    public function kuotaForJenjang(string $jenjang): ?int
    {
        $map = is_array($this->kuota_per_jenjang) ? $this->kuota_per_jenjang : [];

        if (array_key_exists($jenjang, $map)) {
            $val = $map[$jenjang];
            return ($val !== null && $val !== '' && (int) $val > 0) ? (int) $val : null;
        }

        // Fallback untuk data gelombang lama yang hanya mengisi `kuota` tunggal
        if (empty($map) && $this->kuota !== null && $this->kuota > 0) {
            return (int) $this->kuota;
        }

        return null;
    }

    /**
     * Hitung jumlah pendaftar di gelombang ini per jenjang.
     */
    public function countsByJenjang(): array
    {
        return $this->pendaftarans()
            ->selectRaw('jenjang, COUNT(*) as total')
            ->groupBy('jenjang')
            ->pluck('total', 'jenjang')
            ->all();
    }

    /**
     * Cek apakah kuota jenjang tertentu di gelombang ini masih tersedia.
     */
    public function hasQuotaForJenjang(string $jenjang, ?array $countsByJenjang = null): bool
    {
        $limit = $this->kuotaForJenjang($jenjang);
        if ($limit === null) {
            return true;
        }

        $counts = $countsByJenjang ?? $this->countsByJenjang();
        $used = (int) ($counts[$jenjang] ?? 0);

        return $used < $limit;
    }

    /**
     * Daftar jenjang yang kuotanya masih tersedia pada gelombang ini.
     */
    public function availableJenjangs(array $allowedJenjangs): array
    {
        $counts = $this->countsByJenjang();
        $available = [];

        foreach ($allowedJenjangs as $j) {
            if ($this->hasQuotaForJenjang($j, $counts)) {
                $available[] = $j;
            }
        }

        return $available;
    }
}
