<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalActionRequest extends FormRequest
{
    public function authorize() { return auth()->check(); }
    public function rules() { return ['note' => 'nullable|string|max:2000']; }
}
