<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVotingRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->isMeetingAdmin();
    }

    public function rules()
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,aktif,selesai'],
            'select_all_participants' => ['nullable', 'boolean'],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.judul' => ['required', 'string', 'max:255'],
            'items.*.deskripsi' => ['nullable', 'string'],
            'items.*.candidate_ids' => ['required', 'array', 'min:2'],
            'items.*.candidate_ids.*' => ['distinct', 'exists:users,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $selectAll = (bool) $this->input('select_all_participants');
            $participants = (array) $this->input('participant_ids', []);

            if (!$selectAll && count($participants) === 0) {
                $validator->errors()->add('participant_ids', 'Pilih peserta voting atau aktifkan opsi pilih semua peserta.');
            }
        });
    }
}
