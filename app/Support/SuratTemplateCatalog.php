<?php

namespace App\Support;

class SuratTemplateCatalog
{
    public static function all()
    {
        return [
            [
                'id' => 'default-surat-tugas',
                'name' => 'Surat Tugas',
                'slug' => 'surat-tugas',
                'category' => 'Penugasan',
                'description' => 'Template untuk penugasan resmi pegawai pada kegiatan atau perjalanan dinas tertentu.',
                'status' => 'active',
                'source_type' => 'system',
                'surat_keluar_defaults' => [
                    'kategori_kode' => 'KP7.1',
                    'nomenklatur_jabatan' => 'ketua',
                    'opsi_penerima' => 'internal',
                ],
                'field_schema' => [
                    ['name' => 'dalam_rangka', 'label' => 'Dalam Rangka', 'type' => 'textarea', 'required' => true],
                    ['name' => 'tambahan_dasar_hukum', 'label' => 'Tambahan Dasar Hukum (Opsional, 1 baris = 1 poin)', 'type' => 'textarea', 'required' => false],
                    ['name' => 'petugas_ids', 'label' => 'Daftar Petugas', 'type' => 'user_multi', 'required' => true],
                    ['name' => 'tanggal_mulai', 'label' => 'Tanggal Mulai', 'type' => 'date', 'required' => true],
                    ['name' => 'tanggal_selesai', 'label' => 'Tanggal Selesai', 'type' => 'date', 'required' => true],
                    ['name' => 'penanda_tangan_id', 'label' => 'Penanda Tangan', 'type' => 'user_select', 'required' => true],
                    ['name' => 'jabatan_plh', 'label' => 'Jabatan Plh/Plt (Opsional)', 'type' => 'text', 'required' => false],
                    ['name' => 'lokasi', 'label' => 'Lokasi', 'type' => 'text', 'required' => true],
                ],
                'template_body' => '<p><strong>SURAT TUGAS</strong></p><p>Nomor: {{nomor_surat}}</p><p>Template surat tugas resmi Pengadilan Tinggi Agama Papua Barat.</p>',
            ],
            [
                'id' => 'default-surat-keterangan',
                'name' => 'Surat Keterangan',
                'slug' => 'surat-keterangan',
                'category' => 'Keterangan',
                'description' => 'Template surat keterangan umum untuk kebutuhan kepegawaian dan administrasi internal.',
                'status' => 'active',
                'source_type' => 'system',
                'surat_keluar_defaults' => [
                    'kategori_kode' => 'KP',
                    'nomenklatur_jabatan' => 'sekretaris',
                    'opsi_penerima' => 'external',
                ],
                'field_schema' => [
                    ['name' => 'nomor_surat', 'label' => 'Nomor Surat', 'type' => 'text', 'required' => true],
                    ['name' => 'tanggal_surat', 'label' => 'Tanggal Surat', 'type' => 'date', 'required' => true],
                    ['name' => 'nama_pegawai', 'label' => 'Nama Pegawai', 'type' => 'text', 'required' => true],
                    ['name' => 'nip_pegawai', 'label' => 'NIP Pegawai', 'type' => 'text', 'required' => false],
                    ['name' => 'jabatan_pegawai', 'label' => 'Jabatan Pegawai', 'type' => 'text', 'required' => true],
                    ['name' => 'isi_keterangan', 'label' => 'Isi Keterangan', 'type' => 'textarea', 'required' => true],
                    ['name' => 'keperluan', 'label' => 'Keperluan', 'type' => 'textarea', 'required' => true],
                ],
                'template_body' => '<p><strong>SURAT KETERANGAN</strong></p><p>Nomor: {{nomor_surat}}</p><p>Yang bertanda tangan di bawah ini menerangkan bahwa:</p><p>Nama: <strong>{{nama_pegawai}}</strong><br>NIP: {{nip_pegawai}}<br>Jabatan: {{jabatan_pegawai}}</p><p>{{isi_keterangan}}</p><p>Surat ini dipergunakan untuk: {{keperluan}}</p><p>Manokwari, {{tanggal_surat}}</p>',
            ],
            [
                'id' => 'default-surat-perintah',
                'name' => 'Surat Perintah',
                'slug' => 'surat-perintah',
                'category' => 'Instruksi',
                'description' => 'Template surat perintah internal dengan fokus pada instruksi pelaksanaan pekerjaan atau kegiatan.',
                'status' => 'active',
                'source_type' => 'system',
                'surat_keluar_defaults' => [
                    'kategori_kode' => 'OT',
                    'nomenklatur_jabatan' => 'sekretaris',
                    'opsi_penerima' => 'internal',
                ],
                'field_schema' => [
                    ['name' => 'nomor_surat', 'label' => 'Nomor Surat', 'type' => 'text', 'required' => true],
                    ['name' => 'tanggal_surat', 'label' => 'Tanggal Surat', 'type' => 'date', 'required' => true],
                    ['name' => 'nama_pelaksana', 'label' => 'Nama Pelaksana', 'type' => 'text', 'required' => true],
                    ['name' => 'jabatan_pelaksana', 'label' => 'Jabatan Pelaksana', 'type' => 'text', 'required' => true],
                    ['name' => 'uraian_perintah', 'label' => 'Uraian Perintah', 'type' => 'textarea', 'required' => true],
                    ['name' => 'target_waktu', 'label' => 'Target Waktu', 'type' => 'text', 'required' => true],
                ],
                'template_body' => '<p><strong>SURAT PERINTAH</strong></p><p>Nomor: {{nomor_surat}}</p><p>Kepada: <strong>{{nama_pelaksana}}</strong><br>Jabatan: {{jabatan_pelaksana}}</p><p>Dengan ini diperintahkan untuk melaksanakan: {{uraian_perintah}}</p><p>Target waktu pelaksanaan: {{target_waktu}}</p><p>Demikian untuk dilaksanakan dengan penuh tanggung jawab.</p><p>Manokwari, {{tanggal_surat}}</p>',
            ],
        ];
    }

    public static function find($slug)
    {
        foreach (self::all() as $template) {
            if (($template['slug'] ?? null) === $slug) {
                return $template;
            }
        }

        return null;
    }

    public static function resolveSuratKeluarDefaults($template)
    {
        if (is_array($template) && !empty($template['surat_keluar_defaults'])) {
            return $template['surat_keluar_defaults'];
        }

        $slug = is_object($template) ? (string) data_get($template, 'slug') : (string) data_get($template, 'slug');
        $name = mb_strtolower((string) data_get($template, 'name'));
        $category = mb_strtolower((string) data_get($template, 'category'));

        if ($slug === 'surat-tugas' || str_contains($name, 'tugas')) {
            return [
                'kategori_kode' => 'KP7.1',
                'nomenklatur_jabatan' => 'ketua',
                'opsi_penerima' => 'internal',
            ];
        }

        if ($slug === 'surat-keterangan' || str_contains($name, 'keterangan') || str_contains($category, 'keterangan')) {
            return [
                'kategori_kode' => 'KP',
                'nomenklatur_jabatan' => 'sekretaris',
                'opsi_penerima' => 'external',
            ];
        }

        if ($slug === 'surat-perintah' || str_contains($name, 'perintah') || str_contains($category, 'instruksi')) {
            return [
                'kategori_kode' => 'OT',
                'nomenklatur_jabatan' => 'sekretaris',
                'opsi_penerima' => 'internal',
            ];
        }

        return [
            'kategori_kode' => null,
            'nomenklatur_jabatan' => 'sekretaris',
            'opsi_penerima' => 'internal',
        ];
    }
}
