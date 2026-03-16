<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadRapatNotulensiRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->canAccessMeetingMinutes();
    }

    public function rules()
    {
        return [
            'notulensi_file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx'],
            'catatan_upload' => ['nullable', 'string'],
        ];
    }
}
