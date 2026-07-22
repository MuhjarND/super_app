<?php

namespace Tests\Unit;

use App\Support\PersonNameFormatter;
use PHPUnit\Framework\TestCase;

class PersonNameFormatterTest extends TestCase
{
    /**
     * @dataProvider titledNames
     */
    public function testItRemovesTitlesFromDisplayName($name, $expected)
    {
        $this->assertSame($expected, PersonNameFormatter::withoutTitles($name));
    }

    public function titledNames()
    {
        return [
            ['Syamsul Bahri, S.H.I.', 'Syamsul Bahri'],
            ['Dr. Acep Saifuddin, S.H., M.Ag.', 'Acep Saifuddin'],
            ['Prof. Dr. H. Muhammad Amin, S.H., M.H.', 'Muhammad Amin'],
            ['Akram', 'Akram'],
        ];
    }
}
