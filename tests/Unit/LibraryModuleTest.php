<?php

namespace Tests\Unit;

use App\Library\Book;
use App\Library\BookCopy;
use App\Library\Category;
use App\Library\Fine;
use App\Library\Loan;
use App\Library\LoanItem;
use App\Library\Member;
use App\Library\ReturnModel;
use App\Library\Setting;
use App\Library\Shelf;
use App\Role;
use App\User;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Tests\TestCase;

class LibraryModuleTest extends TestCase
{
    public function test_library_models_use_isolated_tables()
    {
        $expected = [
            Book::class => 'library_books',
            BookCopy::class => 'library_book_copies',
            Category::class => 'library_categories',
            Fine::class => 'library_fines',
            Loan::class => 'library_loans',
            LoanItem::class => 'library_loan_items',
            Member::class => 'library_members',
            ReturnModel::class => 'library_returns',
            Setting::class => 'library_settings',
            Shelf::class => 'library_shelves',
        ];

        foreach ($expected as $model => $table) {
            $this->assertSame($table, (new $model())->getTable());
        }
    }

    public function test_operator_can_manage_and_employee_can_only_monitor()
    {
        $operator = $this->userWithRole('operator_perpustakaan');
        $employee = $this->userWithRole('pegawai');

        $this->assertTrue($operator->canAccessLibraryModule());
        $this->assertTrue($operator->canManageLibraryModule());
        $this->assertTrue($employee->canAccessLibraryModule());
        $this->assertFalse($employee->canManageLibraryModule());
    }

    public function test_barcode_generator_is_available()
    {
        $generator = new BarcodeGeneratorSVG();
        $svg = $generator->getBarcode('BK-2026-000001', BarcodeGeneratorSVG::TYPE_CODE_128);

        $this->assertStringContainsString('<svg', $svg);
    }

    protected function userWithRole($name)
    {
        $role = new Role();
        $role->forceFill(['name' => $name, 'display_name' => $name]);

        $user = new User();
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('jabatan', null);
        $user->setRelation('activeJabatanDelegations', collect());

        return $user;
    }
}
