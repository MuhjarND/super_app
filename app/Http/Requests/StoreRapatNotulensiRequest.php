<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRapatNotulensiRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->canManageMeetingMinutes();
    }

    public function rules()
    {
        $isCreate = $this->route('rapat') !== null && $this->route('notulensi') === null;

        return [
            'judul' => ['nullable', 'string', 'max:255'],
            'uraian_kegiatan' => ['nullable', 'string'],
            'agenda_rapat' => ['required', 'string'],
            'susunan_agenda' => ['nullable', 'string'],
            'hasil_rapat' => ['required', 'string'],
            'rekomendasi_items' => ['nullable', 'array'],
            'rekomendasi_items.*.aksi' => ['nullable', 'string'],
            'rekomendasi_items.*.user_ids' => ['nullable', 'array'],
            'rekomendasi_items.*.user_ids.*' => ['integer', Rule::exists('users', 'id')->where('status_aktif_pegawai', true)],
            'dokumentasi_files' => $isCreate ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'dokumentasi_files.*' => ['file', 'image', 'max:5120'],
            'remove_dokumentasi_files' => ['nullable', 'array'],
            'remove_dokumentasi_files.*' => ['string'],
            'signature_data' => ['nullable', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'dokumentasi_files.required' => 'Dokumentasi kegiatan wajib diupload saat membuat notulensi.',
            'dokumentasi_files.min' => 'Dokumentasi kegiatan minimal 1 file.',
            'dokumentasi_files.*.image' => 'Dokumentasi kegiatan harus berupa file gambar.',
            'dokumentasi_files.*.max' => 'Ukuran dokumentasi maksimal 5MB per file.',
        ];
    }
}
