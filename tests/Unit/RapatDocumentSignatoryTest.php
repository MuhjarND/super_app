<?php

namespace Tests\Unit;

use App\Jabatan;
use App\Services\DocumentQrCodeService;
use App\Services\PdfVerificationService;
use App\Services\RapatDocumentService;
use App\User;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RapatDocumentSignatoryTest extends TestCase
{
    public function test_manual_title_takes_precedence_over_actual_approver_position()
    {
        $jabatan = new Jabatan();
        $jabatan->forceFill([
            'kode' => 'KASUBAG-TURT',
            'nama' => 'Kepala Sub Bagian Tata Usaha dan Rumah Tangga',
        ]);

        $approver = new User();
        $approver->setRelation('jabatan', $jabatan);

        $service = new RapatDocumentService(
            $this->createMock(DocumentQrCodeService::class),
            $this->createMock(PdfVerificationService::class)
        );
        $method = new ReflectionMethod($service, 'resolveSignatoryTitle');
        $method->setAccessible(true);

        $title = $method->invoke($service, $approver, 'Pejabat Penanda Tangan');

        $this->assertSame('Pejabat Penanda Tangan,', $title['line1']);
        $this->assertSame('Pengadilan Tinggi Agama Papua Barat', $title['line2']);
    }

    public function test_actual_approver_position_is_used_when_manual_title_is_empty()
    {
        $jabatan = new Jabatan();
        $jabatan->forceFill([
            'kode' => 'KASUBAG-TURT',
            'nama' => 'Kepala Sub Bagian Tata Usaha dan Rumah Tangga',
        ]);

        $approver = new User();
        $approver->setRelation('jabatan', $jabatan);

        $service = new RapatDocumentService(
            $this->createMock(DocumentQrCodeService::class),
            $this->createMock(PdfVerificationService::class)
        );
        $method = new ReflectionMethod($service, 'resolveSignatoryTitle');
        $method->setAccessible(true);

        $title = $method->invoke($service, $approver, '');

        $this->assertSame('Kepala Sub Bagian Tata Usaha dan Rumah Tangga,', $title['line1']);
        $this->assertSame('Pengadilan Tinggi Agama Papua Barat', $title['line2']);
    }

    public function test_only_kpta_is_excluded_from_tembusan(): void
    {
        $service = new RapatDocumentService(
            $this->createMock(DocumentQrCodeService::class),
            $this->createMock(PdfVerificationService::class)
        );
        $method = new ReflectionMethod($service, 'isKetua');
        $method->setAccessible(true);

        $ketua = $this->approverWithPosition('KPTA', 'Ketua Pengadilan Tinggi Agama Papua Barat');
        $wakil = $this->approverWithPosition('WKPTA', 'Wakil Ketua Pengadilan Tinggi Agama Papua Barat');

        $this->assertTrue($method->invoke($service, $ketua));
        $this->assertFalse($method->invoke($service, $wakil));
    }

    public function test_manual_wakil_title_still_gets_tembusan(): void
    {
        $service = new RapatDocumentService(
            $this->createMock(DocumentQrCodeService::class),
            $this->createMock(PdfVerificationService::class)
        );
        $method = new ReflectionMethod($service, 'isKetuaTitle');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($service, 'Ketua Pengadilan Tinggi Agama Papua Barat'));
        $this->assertFalse($method->invoke($service, 'Wakil Ketua Pengadilan Tinggi Agama Papua Barat'));
        $this->assertFalse($method->invoke($service, 'Sekretaris'));
    }

    protected function approverWithPosition($code, $name)
    {
        $position = new Jabatan();
        $position->forceFill(['kode' => $code, 'nama' => $name]);

        $approver = new User();
        $approver->setRelation('jabatan', $position);

        return $approver;
    }
}
