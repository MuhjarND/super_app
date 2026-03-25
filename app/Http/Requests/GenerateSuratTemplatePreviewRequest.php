<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateSuratTemplatePreviewRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->canAccessSuratTemplateMenu();
    }

    public function rules()
    {
        return [
            'fields' => 'array',
        ];
    }
}

