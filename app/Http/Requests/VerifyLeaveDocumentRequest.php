<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyLeaveDocumentRequest extends FormRequest
{
    public function authorize() { return auth()->check(); }
    public function rules()
    {
        return [
            'document_id' => 'required|integer',
            'is_verified' => 'required|boolean',
            'verification_note' => 'nullable|string|max:1000',
        ];
    }
}
