<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SuratKeluar extends Model
{
    protected $fillable = [
        'nomor_surat',
        'nomor_urut',
        'tahun_surat',
        'klasifikasi_kode_id',
        'kategori_surat_id',
        'kode_fungsi_id',
        'kode_kegiatan_id',
        'kode_transaksi_id',
        'nomenklatur_jabatan',
        'opsi_penerima',
        'penerima_external',
        'perihal',
        'tanggal_surat',
        'has_lampiran',
        'file_path',
        'status',
        'created_by',
        'rapat_id'
    ];

    protected $dates = ['tanggal_surat'];

    public function scopeVisibleTo($query, $user)
    {
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->canManageSuratKeluar()) {
            return $query;
        }

        return $query->whereHas('penerimaInternal', function ($penerimaQuery) use ($user) {
            $penerimaQuery->where('users.id', $user->id);
        });
    }

    public function klasifikasiKode()
    {
        return $this->belongsTo(KlasifikasiKode::class, 'klasifikasi_kode_id');
    }

    public function kategoriSurat()
    {
        return $this->belongsTo(KategoriSurat::class, 'kategori_surat_id');
    }

    public function kodeFungsi()
    {
        return $this->belongsTo(KlasifikasiKode::class, 'kode_fungsi_id');
    }

    public function kodeKegiatan()
    {
        return $this->belongsTo(KlasifikasiKode::class, 'kode_kegiatan_id');
    }

    public function kodeTransaksi()
    {
        return $this->belongsTo(KlasifikasiKode::class, 'kode_transaksi_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rapat()
    {
        return $this->belongsTo(Rapat::class, 'rapat_id');
    }

    public function penerimaInternal()
    {
        return $this->belongsToMany(User::class, 'surat_keluar_penerima');
    }

    public function getNomenklaturKodeAttribute()
    {
        $map = [
            'ketua' => 'KPTA',
            'wakil_ketua' => 'WKPTA',
            'sekretaris' => 'SEK',
            'panitera' => 'PAN',
        ];
        return $map[$this->nomenklatur_jabatan] ?? '';
    }

    public function getStatusBadgeAttribute()
    {
        if ($this->status == 'draft') {
            return '<span class="badge badge-warning">Draft</span>';
        }
        return '<span class="badge badge-success">Lengkap</span>';
    }

    public function getPenerimaInfoAttribute()
    {
        if ($this->opsi_penerima == 'internal') {
            $names = $this->penerimaInternal->pluck('name')->implode(', ');
            return 'Internal - ' . $names;
        }
        return 'External - ' . $this->penerima_external;
    }

    public function getDeskripsiKodeAttribute()
    {
        if ($this->kodeTransaksi) {
            return $this->kodeTransaksi->nama;
        }
        if ($this->kodeKegiatan) {
            return $this->kodeKegiatan->nama;
        }
        if ($this->kodeFungsi) {
            return $this->kodeFungsi->nama;
        }
        if ($this->klasifikasiKode) {
            return $this->klasifikasiKode->nama;
        }

        return '-';
    }

    public function getNomorSuratFormattedAttribute()
    {
        $nomor = trim((string) $this->nomor_surat);

        // Backward compatibility for existing pattern:
        // {nomor}/{KPTA|SEK|PAN}/W31-A/{kode}/{bulan}/{tahun}
        if (preg_match('#^([0-9]+)/(KPTA|WKPTA|SEK|PAN)/W31-A/(.+)$#', $nomor, $match)) {
            $prefixMap = [
                'KPTA' => 'KPTA.W31-A',
                'WKPTA' => 'WKPTA.W31-A',
                'SEK' => 'SEK.W31-A',
                'PAN' => 'PAN.W31-A',
            ];

            $prefix = $prefixMap[$match[2]] ?? ($match[2] . '.W31-A');
            return $match[1] . '/' . $prefix . '/' . $match[3];
        }

        return str_replace(
            ['/SEK.PTA.W31-A/', '/PAN.PTA.W31-A/'],
            ['/SEK.W31-A/', '/PAN.W31-A/'],
            $nomor
        );
    }

    /**
     * Generate nomor surat otomatis
     */
    public static function generateNomorSurat($nomenklatur, $klasifikasiKode, $kodeFungsi, $kodeKegiatan, $kodeTransaksi, $tahun, $month = null, $nomorUrut = null)
    {
        $prefixMap = [
            'ketua' => 'KPTA.W31-A',
            'wakil_ketua' => 'WKPTA.W31-A',
            'sekretaris' => 'SEK.W31-A',
            'panitera' => 'PAN.W31-A',
        ];

        $nomorPrefix = $prefixMap[$nomenklatur] ?? 'W31-A';

        if ($nomorUrut === null) {
            $lastNumber = self::max('nomor_urut');
            $nextNumber = ($lastNumber ?? 0) + 1;
        } else {
            $nextNumber = (int) $nomorUrut;
        }

        // Build code path:
        // - kode klasifikasi: huruf (contoh KU)
        // - turunan: angka (contoh 1.1.1)
        $kodeKlasifikasiNomor = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $klasifikasiKode));
        if ($kodeKlasifikasiNomor === '') {
            $kodeKlasifikasiNomor = strtoupper((string) $klasifikasiKode);
        }

        $kodePath = $kodeKlasifikasiNomor;

        $kodeFungsiNomor = self::normalizeTurunanKode($kodeFungsi);
        $kodeKegiatanNomor = self::normalizeTurunanKode($kodeKegiatan);
        $kodeTransaksiNomor = self::normalizeTurunanKode($kodeTransaksi);

        if ($kodeFungsiNomor !== null)
            $kodePath .= $kodeFungsiNomor;
        if ($kodeKegiatanNomor !== null)
            $kodePath .= '.' . $kodeKegiatanNomor;
        if ($kodeTransaksiNomor !== null)
            $kodePath .= '.' . $kodeTransaksiNomor;

        // Roman month
        $bulan = self::getRomanMonth($month ?: now()->month);

        // Format:
        // {nomor}/{prefix}/{kode_path}/{bulan}/{tahun}
        // Contoh: 326/SEK.W31-A/KU1.1.1/III/2026
        $nomor = "{$nextNumber}/{$nomorPrefix}/{$kodePath}/{$bulan}/{$tahun}";

        return ['nomor' => $nomor, 'urut' => $nextNumber];
    }

    public static function getRomanMonth($month)
    {
        $romanMonths = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII'
        ];
        return $romanMonths[$month] ?? '';
    }

    protected static function normalizeTurunanKode($kode)
    {
        if ($kode === null || $kode === '') {
            return null;
        }

        if (preg_match('/(\d+)(?!.*\d)/', (string) $kode, $match)) {
            return $match[1];
        }

        return null;
    }
}
