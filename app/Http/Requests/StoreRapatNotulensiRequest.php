<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRapatNotulensiRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->canAccessMeetingMinutes();
    }

    public function rules()
    {
        return [
            'mode' => ['required', 'in:template_a,template_b'],
            'notulis_id' => ['nullable', 'exists:users,id'],
            'judul' => ['nullable', 'string', 'max:255'],
            'uraian_kegiatan' => ['required', 'string'],
            'agenda_rapat' => ['required', 'string'],
            'susunan_agenda' => ['nullable', 'string'],
            'hasil_rapat' => ['required', 'string'],
            'rekomendasi' => ['nullable', 'string'],
            'dokumentasi_rapat' => ['nullable', 'string'],
            'catatan' => ['nullable', 'string'],
        ];
    }
}
