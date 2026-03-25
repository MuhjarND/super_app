<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSuratTemplateProposalRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->canSubmitSuratTemplateProposal();
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|alpha_dash',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string',
            'requested_fields' => 'required|string',
            'suggested_template_body' => 'nullable|string',
            'example_file' => 'required|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
        ];
    }
}

