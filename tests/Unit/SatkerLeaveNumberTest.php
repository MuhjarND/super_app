<?php

namespace Tests\Unit;

use App\LeaveRequest;
use App\Role;
use App\Services\DocumentQrCodeService;
use App\Services\LeaveDocumentService;
use App\Services\PdfVerificationService;
use App\User;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class SatkerLeaveNumberTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_satker_manual_number_is_kept_without_papeda_generation(): void
    {
        $leaveRequest = $this->satkerLeaveRequest('123/KPA/W25-A/KP5.3/07/2026');

        $this->assertSame(
            '123/KPA/W25-A/KP5.3/07/2026',
            $this->service()->ensureLetterNumber($leaveRequest)
        );
    }

    public function test_satker_request_without_manual_number_is_rejected(): void
    {
        try {
            $this->service()->ensureLetterNumber($this->satkerLeaveRequest());
            $this->fail('Pengajuan cuti satker tanpa nomor surat seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Nomor surat satuan kerja wajib diisi dan tidak dibuat oleh penomoran PAPEDA.',
                data_get($exception->errors(), 'letter_number.0')
            );
        }
    }

    public function test_satker_request_is_not_synchronized_to_outgoing_letters(): void
    {
        $leaveRequest = $this->satkerLeaveRequest('123/KPA/W25-A/KP5.3/07/2026');
        $leaveRequest->setRelation('leaveType', null);
        $leaveRequest->setRelation('approvals', collect());
        $leaveRequest->setRelation('creator', null);
        $leaveRequest->setRelation('documents', collect());
        $leaveRequest->setRelation('suratKeluar', null);

        $this->assertNull($this->service()->syncSuratKeluar($leaveRequest, true));
    }

    protected function satkerLeaveRequest($letterNumber = null)
    {
        $role = new Role();
        $role->forceFill(['name' => 'satker', 'display_name' => 'Satuan Kerja']);

        $user = new User();
        $user->forceFill(['id' => 10, 'name' => 'Pegawai Satker']);
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('unit', null);
        $user->setRelation('jabatan', null);

        $leaveRequest = new LeaveRequest();
        $leaveRequest->forceFill([
            'id' => 20,
            'user_id' => 10,
            'letter_number' => $letterNumber,
        ]);
        $leaveRequest->setRelation('user', $user);

        return $leaveRequest;
    }

    protected function service()
    {
        return new LeaveDocumentService(
            Mockery::mock(DocumentQrCodeService::class),
            Mockery::mock(PdfVerificationService::class)
        );
    }
}
