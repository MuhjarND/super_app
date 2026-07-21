<?php

namespace Tests\Unit;

use App\Jabatan;
use App\Services\PdfVerificationService;
use App\Services\RapatDocumentService;
use App\Services\SignaturePadService;
use App\User;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RapatDocumentSignatoryTest extends TestCase
{
    public function test_actual_approver_position_takes_precedence_over_manual_title()
    {
        $jabatan = new Jabatan();
        $jabatan->forceFill([
            'kode' => 'KASUBAG-TURT',
            'nama' => 'Kepala Sub Bagian Tata Usaha dan Rumah Tangga',
        ]);

        $approver = new User();
        $approver->setRelation('jabatan', $jabatan);

        $service = new RapatDocumentService(
            $this->createMock(SignaturePadService::class),
            $this->createMock(PdfVerificationService::class)
        );
        $method = new ReflectionMethod($service, 'resolveSignatoryTitle');
        $method->setAccessible(true);

        $title = $method->invoke($service, $approver, 'Pejabat Penanda Tangan');

        $this->assertSame('Kepala Sub Bagian Tata Usaha dan Rumah Tangga,', $title['line1']);
        $this->assertSame('Pengadilan Tinggi Agama Papua Barat', $title['line2']);
    }
}
