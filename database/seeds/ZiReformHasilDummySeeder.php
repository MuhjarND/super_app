<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\User;
use App\ZiActivity;
use App\ZiActivityRealization;
use App\ZiArea;
use App\ZiEvidence;
use App\ZiGuidelineIndicator;
use App\ZiGuidelinePoint;
use App\ZiGuidelineSubPoint;
use App\ZiIndicator;
use App\ZiPeriod;

class ZiReformHasilDummySeeder extends Seeder
{
    public function run()
    {
        $systemUser = User::where('email', 'superadmin@pta-papuabarat.go.id')->first() ?: User::orderBy('id')->first();
        $period = ZiPeriod::where('is_active', true)->first();

        if (!$period) {
            $period = ZiPeriod::firstOrCreate(
                ['name' => 'Periode Evaluasi ZI 2026'],
                [
                    'year' => 2026,
                    'target_evaluation_date' => Carbon::parse('2026-12-31'),
                    'description' => 'Periode dummy untuk pengembangan modul Progress ZI.',
                    'is_active' => true,
                    'status' => 'active',
                    'created_by' => optional($systemUser)->id,
                    'updated_by' => optional($systemUser)->id,
                ]
            );
        }

        $picUsers = User::whereIn('email', [
            'sekretaris@pta-papuabarat.go.id',
            'panitera@pta-papuabarat.go.id',
            'kabagkepeg@pta-papuabarat.go.id',
            'kabagumum@pta-papuabarat.go.id',
            'kasubagkepeg@pta-papuabarat.go.id',
            'kasubagrenpro@pta-papuabarat.go.id',
            'kasubaglapkeu@pta-papuabarat.go.id',
            'kasubagturt@pta-papuabarat.go.id',
        ])->get()->keyBy('email');

        $definitions = [
            [
                'code' => 'AREA 07',
                'name' => 'Reform Tata Kelola Internal',
                'group_type' => ZiArea::GROUP_REFORM,
                'pic_email' => 'sekretaris@pta-papuabarat.go.id',
                'theme' => 'penguatan tata kelola internal',
            ],
            [
                'code' => 'AREA 08',
                'name' => 'Reform Penguatan Disiplin Kinerja',
                'group_type' => ZiArea::GROUP_REFORM,
                'pic_email' => 'panitera@pta-papuabarat.go.id',
                'theme' => 'penguatan disiplin kinerja',
            ],
            [
                'code' => 'AREA 09',
                'name' => 'Reform Digitalisasi Administrasi',
                'group_type' => ZiArea::GROUP_REFORM,
                'pic_email' => 'kabagkepeg@pta-papuabarat.go.id',
                'theme' => 'digitalisasi administrasi',
            ],
            [
                'code' => 'AREA 10',
                'name' => 'Reform Integrasi Pengawasan',
                'group_type' => ZiArea::GROUP_REFORM,
                'pic_email' => 'kabagumum@pta-papuabarat.go.id',
                'theme' => 'integrasi pengawasan',
            ],
            [
                'code' => 'AREA 11',
                'name' => 'Reform Sinergi Tim Lintas Fungsi',
                'group_type' => ZiArea::GROUP_REFORM,
                'pic_email' => 'kasubagkepeg@pta-papuabarat.go.id',
                'theme' => 'sinergi tim lintas fungsi',
            ],
            [
                'code' => 'AREA 12',
                'name' => 'Reform Inovasi Proses Kerja',
                'group_type' => ZiArea::GROUP_REFORM,
                'pic_email' => 'kasubagrenpro@pta-papuabarat.go.id',
                'theme' => 'inovasi proses kerja',
            ],
            [
                'code' => 'AREA 13',
                'name' => 'Hasil Capaian Reformasi Birokrasi',
                'group_type' => ZiArea::GROUP_HASIL,
                'pic_email' => 'kasubaglapkeu@pta-papuabarat.go.id',
                'theme' => 'capaian reformasi birokrasi',
            ],
            [
                'code' => 'AREA 14',
                'name' => 'Hasil Dampak Layanan Publik',
                'group_type' => ZiArea::GROUP_HASIL,
                'pic_email' => 'kasubagturt@pta-papuabarat.go.id',
                'theme' => 'dampak layanan publik',
            ],
        ];

        foreach ($definitions as $index => $definition) {
            $picUser = $picUsers->get($definition['pic_email']) ?: $systemUser;

            $area = ZiArea::updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'description' => 'Data dummy untuk pengembangan bagian ' . $definition['group_type'] . ' pada Progress ZI.',
                    'group_type' => $definition['group_type'],
                    'pic_user_id' => optional($picUser)->id,
                    'is_active' => true,
                ]
            );

            if ($picUser) {
                $area->pics()->syncWithoutDetaching([$picUser->id]);
            }

            $point = ZiGuidelinePoint::updateOrCreate(
                [
                    'zi_area_id' => $area->id,
                    'code' => 'I',
                ],
                [
                    'title' => 'Penguatan ' . ucfirst($definition['theme']),
                    'description' => 'Poin pedoman dummy untuk ' . strtolower($definition['name']) . '.',
                    'sort_order' => 1,
                ]
            );

            $subPointDefinitions = [
                [
                    'code' => 'a',
                    'title' => 'Perencanaan dan pelaksanaan ' . $definition['theme'],
                    'description' => 'Sub poin dummy untuk memastikan ada data monitoring pada area ini.',
                    'activity_name' => 'Pelaksanaan ' . ucfirst($definition['theme']),
                    'activity_status' => $index % 3 === 0 ? 'selesai' : ($index % 3 === 1 ? 'sedang_berjalan' : 'dijadwalkan'),
                    'indicator_status' => $index % 3 === 0 ? 'diverifikasi' : ($index % 3 === 1 ? 'sebagian_terpenuhi' : 'belum_diisi'),
                    'evidence_status' => $index % 3 === 0 ? 'valid' : ($index % 3 === 1 ? 'terupload' : null),
                ],
                [
                    'code' => 'b',
                    'title' => 'Evaluasi berkala ' . $definition['theme'],
                    'description' => 'Sub poin dummy lanjutan untuk pengisian data dashboard.',
                    'activity_name' => 'Evaluasi berkala ' . ucfirst($definition['theme']),
                    'activity_status' => $index % 2 === 0 ? 'perlu_perbaikan' : 'selesai',
                    'indicator_status' => $index % 2 === 0 ? 'ditolak' : 'terpenuhi',
                    'evidence_status' => $index % 2 === 0 ? 'revisi' : 'valid',
                ],
            ];

            foreach ($subPointDefinitions as $subIndex => $subDefinition) {
                $subPoint = ZiGuidelineSubPoint::updateOrCreate(
                    [
                        'zi_guideline_point_id' => $point->id,
                        'code' => $subDefinition['code'],
                    ],
                    [
                        'title' => $subDefinition['title'],
                        'description' => $subDefinition['description'],
                        'sort_order' => $subIndex + 1,
                    ]
                );

                $guidelineIndicator = ZiGuidelineIndicator::updateOrCreate(
                    [
                        'zi_guideline_sub_point_id' => $subPoint->id,
                        'code' => '1',
                    ],
                    [
                        'indicator_text' => 'Indikator dummy untuk ' . strtolower($subDefinition['title']) . '.',
                        'evidence_example' => 'Notulen rapat, foto kegiatan, dan rekap tindak lanjut.',
                        'implementation_note' => 'Digunakan sebagai data contoh untuk pengembangan tampilan.',
                        'is_periodic' => $subDefinition['code'] === 'b',
                        'sort_order' => 1,
                    ]
                );

                $activity = ZiActivity::updateOrCreate(
                    [
                        'zi_period_id' => $period->id,
                        'zi_area_id' => $area->id,
                        'zi_guideline_sub_point_id' => $subPoint->id,
                        'name' => $subDefinition['activity_name'],
                    ],
                    [
                        'description' => 'Kegiatan dummy untuk ' . strtolower($subDefinition['activity_name']) . '.',
                        'target_start_date' => Carbon::parse('2026-01-01')->addDays(($index * 10) + ($subIndex * 5)),
                        'target_end_date' => Carbon::parse('2026-02-01')->addDays(($index * 10) + ($subIndex * 5)),
                        'pic_user_id' => optional($picUser)->id,
                        'status' => $subDefinition['activity_status'],
                        'source_type' => 'manual',
                        'created_by' => optional($systemUser)->id,
                        'updated_by' => optional($systemUser)->id,
                    ]
                );

                $indicator = ZiIndicator::updateOrCreate(
                    [
                        'zi_activity_id' => $activity->id,
                        'zi_guideline_indicator_id' => $guidelineIndicator->id,
                    ],
                    [
                        'name' => 'Indikator ' . $definition['code'] . '-' . strtoupper($subDefinition['code']),
                        'description' => 'Indikator dummy untuk dashboard dan monitoring.',
                        'weight' => 100,
                        'target_fulfillment_text' => 'Target dummy terpenuhi.',
                        'is_evidence_required' => true,
                        'minimum_evidence_count' => 1,
                        'status' => $subDefinition['indicator_status'],
                        'created_by' => optional($systemUser)->id,
                        'updated_by' => optional($systemUser)->id,
                    ]
                );

                if ($subDefinition['evidence_status']) {
                    $realization = ZiActivityRealization::updateOrCreate(
                        [
                            'zi_activity_id' => $activity->id,
                            'realization_date' => Carbon::parse('2026-02-10')->addDays(($index * 7) + $subIndex),
                        ],
                        [
                            'implementation_summary' => 'Pelaksanaan dummy untuk ' . strtolower($subDefinition['title']) . '.',
                            'result_summary' => 'Hasil dummy tercatat untuk kebutuhan pengujian dashboard.',
                            'obstacles' => $subDefinition['evidence_status'] === 'revisi' ? 'Masih perlu penyempurnaan eviden.' : null,
                            'follow_up' => 'Lanjutkan penguatan dan pemantauan berkala.',
                            'source_type' => 'manual',
                            'created_by' => optional($systemUser)->id,
                            'updated_by' => optional($systemUser)->id,
                        ]
                    );

                    $evidence = ZiEvidence::updateOrCreate(
                        [
                            'zi_activity_realization_id' => $realization->id,
                            'title' => 'Eviden ' . $definition['code'] . ' ' . strtoupper($subDefinition['code']),
                        ],
                        [
                            'description' => 'Eviden dummy untuk kebutuhan tampilan dan rekap.',
                            'evidence_type' => 'dokumen',
                            'source_type' => 'manual',
                            'source_reference_type' => 'manual',
                            'status' => $subDefinition['evidence_status'],
                            'is_auto_linked' => false,
                            'uploaded_by' => optional($systemUser)->id,
                        ]
                    );

                    $indicator->evidences()->syncWithoutDetaching([$evidence->id]);
                }
            }
        }
    }
}
