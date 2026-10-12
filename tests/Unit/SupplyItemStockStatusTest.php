<?php

namespace Tests\Unit;

use App\SupplyItem;
use PHPUnit\Framework\TestCase;

class SupplyItemStockStatusTest extends TestCase
{
    /** @dataProvider stockStatusCases */
    public function test_stock_status_includes_managed_unit_stock($stock, $hasUnitStock, $unitStock, $minimumStock, $isEmpty, $label)
    {
        $item = new SupplyItem([
            'stock' => $stock,
            'has_unit_stock' => $hasUnitStock,
            'unit_stock' => $unitStock,
            'minimum_stock' => $minimumStock,
        ]);

        $this->assertSame($isEmpty, $item->is_empty_stock);
        $this->assertSame($label, $item->stock_status_label);
    }

    public function stockStatusCases()
    {
        return [
            'main empty but units remain' => [0, true, 8, 2, false, 'Tersedia'],
            'last unit remains' => [0, true, 1, 2, false, 'Tersedia'],
            'both stocks empty' => [0, true, 0, 2, true, 'Habis'],
            'disabled unit tracking ignores old unit count' => [0, false, 8, 2, true, 'Habis'],
            'main stock remains without units' => [8, true, 0, 2, false, null],
            'normal stock without unit tracking' => [8, false, 0, 2, false, null],
            'main stock nearly empty' => [2, true, 8, 2, false, 'Hampir habis'],
        ];
    }
}
