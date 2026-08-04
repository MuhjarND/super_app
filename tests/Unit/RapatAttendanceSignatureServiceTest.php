<?php

namespace Tests\Unit;

use App\Rapat;
use App\RapatAttendance;
use App\Services\RapatAttendanceSignatureService;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RapatAttendanceSignatureServiceTest extends TestCase
{
    public function testItUsesTheSelectedOfficialsAttendanceSignature()
    {
        Storage::fake('public');
        Storage::disk('public')->put('rapat/attendance/official.png', str_repeat('signature', 80));

        $official = new User([
            'name' => 'Pejabat Absensi',
            'nip' => '198001012006041001',
            'jabatan_keterangan' => 'Ketua Panitia',
        ]);
        $official->id = 41;
        $official->setRelation('jabatan', null);

        $attendance = new RapatAttendance([
            'user_id' => 41,
            'signature_path' => 'rapat/attendance/official.png',
            'attended_at' => Carbon::parse('2026-08-04 09:15:00', 'Asia/Jayapura'),
        ]);

        $rapat = new Rapat([
            'attendance_signer_id' => 41,
            'token_qr' => 'attendance-signature-test-token',
        ]);
        $rapat->setRelation('attendanceSigner', $official);
        $rapat->setRelation('approver1', null);
        $rapat->setRelation('approver2', null);
        $rapat->setRelation('internalAttendances', collect([$attendance]));

        $result = app(RapatAttendanceSignatureService::class)->resolve($rapat);

        $this->assertTrue($result['configured']);
        $this->assertTrue($result['available']);
        $this->assertSame('Pejabat Absensi', $result['name']);
        $this->assertSame('Ketua Panitia,', $result['line1']);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $result['image']);
        $this->assertStringContainsString('signature=attendance', $result['verification_url']);
        $this->assertSame('2026-08-04 09:15:00', $result['signed_at']->format('Y-m-d H:i:s'));

        $rapat->setRelation('creator', null);
        $rapat->setRelation('kategoriSuratKode', null);
        $rapat->setRelation('suratKeluar', null);
        $verification = app(RapatAttendanceSignatureService::class)->buildVerificationData($rapat);
        $this->assertTrue($verification['valid']);
        $this->assertSame('Laporan Absensi Kegiatan', $verification['document_type']);
        $this->assertSame('Pejabat Absensi', $verification['signatory_name']);
    }

    public function testExistingMeetingFallsBackToInvitationSignatory()
    {
        $official = new User([
            'name' => 'Penanda Tangan Lama',
            'jabatan_keterangan' => 'Sekretaris',
        ]);
        $official->id = 9;
        $official->setRelation('jabatan', null);

        $rapat = new Rapat();
        $rapat->setRelation('attendanceSigner', null);
        $rapat->setRelation('approver1', $official);
        $rapat->setRelation('approver2', null);
        $rapat->setRelation('internalAttendances', collect());

        $result = app(RapatAttendanceSignatureService::class)->resolve($rapat);

        $this->assertFalse($result['configured']);
        $this->assertFalse($result['available']);
        $this->assertSame('Penanda Tangan Lama', $result['name']);
    }
}
