<?php

namespace Tests\Unit;

use App\ZiArea;
use PHPUnit\Framework\TestCase;

class ZiAreaDisplayCodeTest extends TestCase
{
    /** @dataProvider areaCodes */
    public function test_guide_area_labels_hide_the_internal_prefix_without_changing_the_stored_code($code, $label)
    {
        $area = new ZiArea(['code' => $code]);
        $this->assertSame($label, $area->display_code);
        $this->assertSame($code, $area->code);
    }

    public function areaCodes()
    {
        return [
            ['PANDUAN-P-1', 'AREA 1'], ['PANDUAN-R-6', 'AREA 6'],
            ['PANDUAN-H-i', 'AREA 1'], ['PANDUAN-H-ii', 'AREA 2'],
            ['PANDUAN-R-01', 'AREA 1'], ['AREA 3', 'AREA 3'], ['KHUSUS', 'KHUSUS'],
        ];
    }
}
