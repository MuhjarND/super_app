<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVotingRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->canManageVoting();
    }

    protected function prepareForValidation()
    {
        $items = $this->input('items', []);
        if (!is_array($items)) {
            return;
        }
        foreach ($items as &$item) {
            if (!is_array($item)) {
                continue;
            }
            $item['uses_candidates'] = $item['uses_candidates'] ?? '1';
            if (isset($item['options']) && is_array($item['options'])) {
                $item['options'] = array_map(function ($label) {
                    return is_string($label) ? trim($label) : $label;
                }, $item['options']);
            }
        }
        unset($item);
        $this->merge(['items' => $items]);
    }

    public function rules()
    {
        $rules = [
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,aktif,selesai'],
            'select_all_participants' => ['nullable', 'boolean'],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => [Rule::exists('users', 'id')->where('status_aktif_pegawai', true)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.judul' => ['required', 'string', 'max:255'],
            'items.*.deskripsi' => ['nullable', 'string'],
            'items.*.uses_candidates' => ['required', 'boolean'],
        ];

        foreach ((array) $this->input('items', []) as $index => $item) {
            if (!is_array($item)) continue;
            if (filter_var($item['uses_candidates'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
                $rules['items.' . $index . '.candidate_ids'] = ['required', 'array', 'min:2'];
                $rules['items.' . $index . '.candidate_ids.*'] = ['required', 'integer', 'distinct', Rule::exists('users', 'id')->where('status_aktif_pegawai', true)];
                $rules['items.' . $index . '.candidate_images'] = ['nullable', 'array'];
                $rules['items.' . $index . '.candidate_images.*'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'];
                $rules['items.' . $index . '.existing_candidate_images'] = ['nullable', 'array'];
                $rules['items.' . $index . '.existing_candidate_images.*'] = ['nullable', 'string', 'max:255'];
            } else {
                $rules['items.' . $index . '.options'] = ['required', 'array', 'min:2', 'max:20'];
                $rules['items.' . $index . '.options.*'] = ['required', 'string', 'max:255'];
                $rules['items.' . $index . '.option_images'] = ['nullable', 'array'];
                $rules['items.' . $index . '.option_images.*'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'];
                $rules['items.' . $index . '.existing_option_image_ids'] = ['nullable', 'array'];
                $voting = $this->route('voting');
                $votingId = $voting instanceof \App\Voting ? $voting->id : 0;
                $rules['items.' . $index . '.existing_option_image_ids.*'] = [
                    'nullable', 'integer', Rule::exists('voting_candidates', 'id')->where(function ($query) use ($votingId) {
                        $query->whereIn('voting_item_id', function ($items) use ($votingId) {
                            $items->select('id')->from('voting_items')->where('voting_id', $votingId);
                        });
                    }),
                ];
            }
        }
        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $selectAll = (bool) $this->input('select_all_participants');
            $participants = (array) $this->input('participant_ids', []);

            if (!$selectAll && count($participants) === 0) {
                $validator->errors()->add('participant_ids', 'Pilih peserta voting atau aktifkan opsi pilih semua peserta.');
            }

            foreach ((array) $this->input('items', []) as $index => $item) {
                if (!is_array($item) || filter_var($item['uses_candidates'] ?? true, FILTER_VALIDATE_BOOLEAN)) continue;
                $labels = collect(is_array($item['options'] ?? null) ? $item['options'] : [])
                    ->filter(function ($label) { return is_string($label); })
                    ->map(function ($label) { return mb_strtolower(trim($label)); });
                if ($labels->unique()->count() !== $labels->count()) {
                    $validator->errors()->add('items.' . $index . '.options', 'Pilihan jawaban dalam satu item tidak boleh sama.');
                }
            }
        });
    }

    public function messages()
    {
        return [
            'items.*.candidate_ids.required' => 'Pilih minimal dua kandidat untuk item ini.',
            'items.*.candidate_ids.min' => 'Pilih minimal dua kandidat untuk item ini.',
            'items.*.options.required' => 'Isi minimal dua pilihan jawaban untuk voting tanpa kandidat.',
            'items.*.options.min' => 'Isi minimal dua pilihan jawaban untuk voting tanpa kandidat.',
            'items.*.options.*.required' => 'Pilihan jawaban tidak boleh kosong.',
            'items.*.option_images.*.image' => 'Unggah gambar yang valid untuk pilihan jawaban.',
            'items.*.option_images.*.mimes' => 'Gambar pilihan harus berformat JPG, PNG, atau WebP.',
            'items.*.option_images.*.max' => 'Ukuran gambar pilihan maksimal 3 MB.',
            'items.*.candidate_images.*.max' => 'Ukuran gambar kandidat maksimal 3 MB.',
        ];
    }
}
