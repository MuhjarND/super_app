<?php

namespace App\Http\Requests;

use App\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveRequest extends FormRequest
{
    public function authorize() { return auth()->check(); }
    public function rules()
    {
        $isSatker = auth()->check() && auth()->user()->isSatker();
        $leaveRequest = $this->route('leaveRequest');
        $uniqueLetterNumber = Rule::unique('leave_requests', 'letter_number');
        if ($leaveRequest) {
            $uniqueLetterNumber->ignore(is_object($leaveRequest) ? $leaveRequest->id : $leaveRequest);
        }

        return [
            'letter_number' => [
                $isSatker ? 'required' : 'nullable',
                'string',
                'max:150',
                $uniqueLetterNumber,
            ],
            'leave_type_id' => [
                'required',
                'integer',
                Rule::exists('leave_types', 'id')->where(function ($query) {
                    $query->where('status', 'active')
                        ->where('code', '!=', LeaveType::CODE_BERSAMA);
                }),
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'purpose' => 'required|string|max:255',
            'leave_address' => 'required|string|max:255',
            'is_abroad' => 'nullable|boolean',
            'abroad_country' => 'nullable|required_if:is_abroad,1|string|max:100',
            '_leave_form_mode' => 'nullable|string|in:create,edit',
            '_leave_request_id' => 'nullable|integer',
            'revision_note' => 'nullable|string|max:1000',
            'documents.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ];
    }

    public function messages()
    {
        return [
            'letter_number.required' => 'Nomor surat satuan kerja wajib diisi.',
            'letter_number.unique' => 'Nomor surat satuan kerja tersebut sudah digunakan pada pengajuan cuti lain.',
        ];
    }
}
