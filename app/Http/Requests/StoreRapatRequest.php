<?php

namespace App\Http\Requests;

use App\KlasifikasiKode;
use Illuminate\Foundation\Http\FormRequest;

class StoreRapatRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->canManageRapat();
    }

    public function rules()
    {
        return [
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'kategori_surat_kode_id' => ['required', 'exists:klasifikasi_kodes,id'],
            'nomenklatur_jabatan' => ['required', 'in:ketua,wakil_ketua,sekretaris,panitera'],
            'tanggal' => ['required', 'date'],
            'waktu_mulai' => ['required', 'date_format:H:i'],
            'tempat' => ['required', 'string', 'max:255'],
            'peserta_ids' => ['required', 'array', 'min:1'],
            'peserta_ids.*' => ['exists:users,id'],
            'approver_1_id' => ['nullable', 'exists:users,id'],
            'approver_2_id' => ['nullable', 'different:approver_1_id', 'exists:users,id'],
            'approval1_jabatan_manual' => ['nullable', 'string', 'max:255'],
            'include_detail_tambahan' => ['nullable', 'boolean'],
            'detail_tambahan' => ['nullable', 'required_if:include_detail_tambahan,1', 'string'],
            'tujuan_surat' => ['nullable', 'required_if:gunakan_lampiran_tambahan,1', 'string'],
            'include_pakaian' => ['nullable', 'boolean'],
            'jenis_pakaian' => ['nullable', 'required_if:include_pakaian,1', 'string', 'max:255'],
            'is_virtual' => ['nullable', 'boolean'],
            'meeting_id' => ['nullable', 'required_if:is_virtual,1', 'string', 'max:255'],
            'meeting_passcode' => ['nullable', 'required_if:is_virtual,1', 'string', 'max:255'],
            'gunakan_lampiran_tambahan' => ['nullable', 'boolean'],
            'lampiran_tambahan' => ['nullable', 'required_if:gunakan_lampiran_tambahan,1', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'status' => ['nullable', 'in:draft,terjadwal,pending_approval,disetujui,ditolak,dibatalkan,selesai'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurring_pattern' => ['nullable', 'required_if:is_recurring,1', 'in:harian,mingguan,bulanan'],
            'recurring_until' => ['nullable', 'required_if:is_recurring,1', 'date', 'after_or_equal:tanggal'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $kategori = KlasifikasiKode::find($this->input('kategori_surat_kode_id'));

            if ($kategori && $kategori->children()->exists()) {
                $validator->errors()->add('kategori_surat_kode_id', 'Kategori surat yang dipilih harus merupakan kode turunan terakhir.');
            }

            if ($kategori) {
                $root = $kategori;
                while ($root && $root->parent) {
                    $root = $root->parent;
                }

                if ($root && !preg_match('/[A-Za-z]/', (string) $root->kode)) {
                    $validator->errors()->add('kategori_surat_kode_id', 'Kategori surat rapat harus berasal dari kode klasifikasi huruf.');
                }
            }

            if (!$this->boolean('include_detail_tambahan')) {
                $this->merge(['detail_tambahan' => null]);
            }

            if (!$this->boolean('include_pakaian')) {
                $this->merge(['jenis_pakaian' => null]);
            }

            if ($this->boolean('gunakan_lampiran_tambahan') && trim((string) $this->input('tujuan_surat')) === '') {
                $validator->errors()->add('tujuan_surat', 'Tujuan surat wajib diisi jika lampiran tambahan digunakan.');
            }
        });
    }
}
