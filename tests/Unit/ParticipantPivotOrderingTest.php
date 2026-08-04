<?php

namespace Tests\Unit;

use App\AgendaPimpinan;
use App\Rapat;
use App\VirtualMeeting;
use App\Voting;
use Tests\TestCase;

class ParticipantPivotOrderingTest extends TestCase
{
    public function testParticipantRelationsOrderByRealPivotColumns()
    {
        $this->assertStringContainsString(
            '"rapat_peserta"."urutan"',
            (new Rapat())->pesertas()->toSql()
        );
        $this->assertStringContainsString(
            '"voting_participants"."urutan"',
            (new Voting())->participants()->toSql()
        );
        $this->assertStringContainsString(
            '"virtual_meeting_user"."urutan"',
            (new VirtualMeeting())->participants()->toSql()
        );
        $this->assertStringContainsString(
            '"agenda_pimpinan_user"."urutan"',
            (new AgendaPimpinan())->recipients()->toSql()
        );
    }
}
