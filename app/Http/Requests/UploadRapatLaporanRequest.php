<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadRapatLaporanRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->canAccessMeetingMinutes();
    }

    public function rules()
    {
        return [
            'laporan_file' => ['required', 'file', 'max:15360', 'mimes:pdf,doc,docx,xls,xlsx'],
            'deskripsi_upload' => ['nullable', 'string'],
        ];
    }
}
