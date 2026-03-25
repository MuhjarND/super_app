<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class ProcessSuratTemplateProposalRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->canManageSuratTemplates();
    }

    public function rules()
    {
        return [
            'action' => 'required|in:approve,reject',
            'review_notes' => 'nullable|string',
            'template_name' => 'required_if:action,approve|string|max:255',
            'template_slug' => 'required_if:action,approve|string|max:255|alpha_dash',
            'template_category' => 'required_if:action,approve|string|max:100',
            'template_status' => 'required_if:action,approve|in:draft,active,inactive',
            'field_schema' => 'required_if:action,approve|string',
            'template_body' => 'required_if:action,approve|string',
        ];
    }
}

