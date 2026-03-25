<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSuratTemplateRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->canManageSuratTemplates();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|alpha_dash',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,inactive',
            'field_schema' => 'required|string',
            'template_body' => 'required|string',
            'sample_file' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:5120',
        ];
    }
}

