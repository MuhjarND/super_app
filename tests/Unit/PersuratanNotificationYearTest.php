<?php

namespace Tests\Unit;

use App\SuratKeluar;
use App\SuratMasuk;
use PHPUnit\Framework\TestCase;

class PersuratanNotificationYearTest extends TestCase
{
    /**
     * @dataProvider letterModelProvider
     */
    public function test_letter_year_scope_uses_the_letter_date($modelClass)
    {
        $query = new CapturingLetterYearQuery();
        $model = new $modelClass();

        $result = $model->scopeForLetterYear($query, 2026);

        $this->assertSame($query, $result);
        $this->assertSame(['tanggal_surat', 2026], $query->whereYearArguments);
    }

    public function letterModelProvider()
    {
        return [
            'surat masuk' => [SuratMasuk::class],
            'surat keluar' => [SuratKeluar::class],
        ];
    }
}

class CapturingLetterYearQuery
{
    public $whereYearArguments;

    public function whereYear($column, $year)
    {
        $this->whereYearArguments = [$column, $year];

        return $this;
    }
}
