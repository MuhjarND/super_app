<?php

namespace Tests\Unit;

use App\Support\DocumentFilename;
use PHPUnit\Framework\TestCase;

class DocumentFilenameTest extends TestCase
{
    public function test_letter_filename_contains_leading_number_then_subject_and_is_windows_safe(): void
    {
        $filename = DocumentFilename::fromLetter(
            '001/KPTA.W31-A/UND.OT1.6/VIII/2026',
            'Undangan Rapat: Monitoring/Evaluasi?'
        );

        $this->assertSame('001 - Undangan Rapat- Monitoring-Evaluasi.pdf', $filename);
        $this->assertSame(0, preg_match('/[<>:"\/\\|?*]/', $filename));
    }
}
