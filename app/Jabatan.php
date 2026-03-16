<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Jabatan extends Model
{
    protected $fillable = ['nama', 'kode', 'parent_id', 'level', 'unit_id'];

    public function parent()
    {
        return $this->belongsTo(Jabatan::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Jabatan::class, 'parent_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the list of jabatan IDs this position can disposisi to
     */
    public function getTargetDisposisi()
    {
        $targets = [];

        switch ($this->kode) {
            case 'ADMIN_SURAT':
                $targets = [];
                break;
            case 'KASUBAG_TURT':
                $targets = Jabatan::whereIn('kode', ['SEK', 'PAN'])->pluck('id')->toArray();
                break;
            case 'SEK':
                $targets = Jabatan::whereIn('kode', ['KABAG_KEPEG', 'KABAG_UMUM', 'KPTA', 'WKPTA'])->pluck('id')->toArray();
                break;
            case 'PAN':
                $targets = Jabatan::whereIn('kode', ['PANMUD_BANDING', 'PANMUD_HUKUM', 'KPTA', 'WKPTA'])->pluck('id')->toArray();
                break;
            case 'KPTA':
            case 'WKPTA':
                $targets = Jabatan::whereIn('kode', ['SEK', 'PAN'])->pluck('id')->toArray();
                break;
            case 'KABAG_KEPEG':
                $targets = Jabatan::whereIn('kode', ['KASUBAG_KEPEG', 'KASUBAG_RENPRO'])->pluck('id')->toArray();
                break;
            case 'KABAG_UMUM':
                $targets = Jabatan::whereIn('kode', ['KASUBAG_LAPKEU', 'KASUBAG_TURT'])->pluck('id')->toArray();
                break;
        }

        return $targets;
    }
}
