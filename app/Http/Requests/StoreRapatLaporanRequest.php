<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRapatLaporanRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->canManageMeetingMinutes();
    }

    public function rules()
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'bab_1_latar_belakang' => ['required', 'string'],
            'bab_1_dasar' => ['required', 'string'],
            'bab_1_tujuan' => ['required', 'string'],
            'bab_2_hasil_monitoring' => ['required', 'string'],
            'bab_3_tindak_lanjut' => ['required', 'string'],
        ];
    }

    public function attributes()
    {
        return [
            'bab_1_latar_belakang' => 'Bab 1 Latar Belakang',
            'bab_1_dasar' => 'Bab 1 Dasar',
            'bab_1_tujuan' => 'Bab 1 Tujuan',
            'bab_2_hasil_monitoring' => 'Bab 2 Hasil Monitoring dan Evaluasi',
            'bab_3_tindak_lanjut' => 'Bab 3 Tindak Lanjut dan Rekomendasi',
        ];
    }
}
