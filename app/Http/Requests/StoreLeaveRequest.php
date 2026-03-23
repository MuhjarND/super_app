<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
    public function authorize() { return auth()->check() && auth()->user()->canAccessLeaveModule(); }
    public function rules()
    {
        return [
            'leave_type_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'purpose' => 'required|string|max:255',
            'leave_address' => 'required|string|max:255',
            '_leave_form_mode' => 'nullable|string|in:create,edit',
            '_leave_request_id' => 'nullable|integer',
            'documents.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ];
    }
}
