<?php

namespace Tests\Unit;

use App\Support\SuratTemplateCatalog;
use App\SuratKeluarApproval;
use PHPUnit\Framework\TestCase;

class SuratTugasWorkflowTest extends TestCase
{
    public function testSuratTugasCatalogContainsNewOrderedFields()
    {
        $template = SuratTemplateCatalog::find('surat-tugas');
        $fieldNames = collect($template['field_schema'])->pluck('name')->all();

        $this->assertSame([
            'dalam_rangka',
            'untuk_tugas',
            'tambahan_dasar_hukum',
            'petugas_ids',
            'tanggal_mulai',
            'tanggal_selesai',
            'penanda_tangan_id',
            'paraf_user_id',
            'jabatan_plh',
            'lokasi',
        ], $fieldNames);
    }

    public function testApprovalCannotProceedBeforeRequiredParafIsApproved()
    {
        $approval = new SuratKeluarApproval([
            'paraf_user_id' => 10,
            'paraf_status' => 'pending',
        ]);

        $this->assertFalse($approval->isParafReady());

        $approval->paraf_status = 'approved';
        $this->assertTrue($approval->isParafReady());
    }

    public function testLegacyApprovalWithoutParafRemainsReady()
    {
        $approval = new SuratKeluarApproval([
            'paraf_user_id' => null,
            'paraf_status' => 'not_required',
        ]);

        $this->assertTrue($approval->isParafReady());
    }
}
