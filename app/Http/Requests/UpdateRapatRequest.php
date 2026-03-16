<?php

namespace App\Http\Requests;

class UpdateRapatRequest extends StoreRapatRequest
{
    public function rules()
    {
        $rules = parent::rules();
        $rules['hapus_lampiran_tambahan'] = ['nullable', 'boolean'];
        $rules['lampiran_tambahan'] = ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'];

        return $rules;
    }

    public function withValidator($validator)
    {
        parent::withValidator($validator);

        $validator->after(function ($validator) {
            $rapat = $this->route('rapat');
            $gunakanLampiran = $this->boolean('gunakan_lampiran_tambahan');
            $hapusLampiran = $this->boolean('hapus_lampiran_tambahan');
            $punyaLampiranSaatIni = $rapat && $rapat->lampiran_tambahan_path && !$hapusLampiran;

            if ($gunakanLampiran && !$punyaLampiranSaatIni && !$this->hasFile('lampiran_tambahan')) {
                $validator->errors()->add('lampiran_tambahan', 'Lampiran tambahan wajib diunggah jika opsi lampiran tambahan diaktifkan.');
            }

            if ($gunakanLampiran && trim((string) $this->input('tujuan_surat')) === '') {
                $validator->errors()->add('tujuan_surat', 'Tujuan surat wajib diisi jika lampiran tambahan digunakan.');
            }
        });
    }
}
