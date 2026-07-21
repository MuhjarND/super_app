<?php

namespace Tests\Unit;

use App\SuratKeluar;
use Tests\TestCase;

class SuratKeluarRecipientNotificationTest extends TestCase
{
    public function test_draft_with_no_file_is_not_ready_for_recipient_notification()
    {
        $surat = new SuratKeluar(['status' => 'draft', 'file_path' => null]);
        $this->setEmptyGeneratedDocumentRelations($surat);

        $this->assertFalse($surat->isReadyForRecipientNotification());
    }

    public function test_complete_letter_with_uploaded_file_is_ready_for_recipient_notification()
    {
        $surat = new SuratKeluar(['status' => 'lengkap', 'file_path' => 'surat-keluar/document.pdf']);

        $this->assertTrue($surat->isReadyForRecipientNotification());
    }

    public function test_file_does_not_trigger_notification_while_status_is_still_draft()
    {
        $surat = new SuratKeluar(['status' => 'draft', 'file_path' => 'surat-keluar/document.pdf']);

        $this->assertFalse($surat->isReadyForRecipientNotification());
    }

    protected function setEmptyGeneratedDocumentRelations(SuratKeluar $surat)
    {
        $surat->setRelation('templateApproval', null);
        $surat->setRelation('leaveRequest', null);
        $surat->setRelation('pdfVerifications', collect());
    }
}
