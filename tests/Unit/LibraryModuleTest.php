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

    public function test_operator_can_manage_and_other_users_can_only_borrow()
    {
        $operator = $this->userWithRole('operator_perpustakaan');
        $employee = $this->userWithRole('pegawai');

        $this->assertTrue($operator->canAccessLibraryModule());
        $this->assertTrue($operator->canManageLibraryModule());
        $this->assertTrue($employee->canAccessLibraryModule());
        $this->assertFalse($employee->canManageLibraryModule());
    }

    public function test_non_operator_can_only_use_catalog_and_loan_routes()
    {
        $routes = app('router')->getRoutes();

        $this->assertNotContains('library.manage', $routes->getByName('library.books.index')->gatherMiddleware());
        $this->assertNotContains('library.manage', $routes->getByName('library.books.show')->gatherMiddleware());
        $this->assertNotContains('library.manage', $routes->getByName('library.loans.index')->gatherMiddleware());
        $this->assertNotContains('library.manage', $routes->getByName('library.loans.store')->gatherMiddleware());

        $this->assertContains('library.manage', $routes->getByName('library.members.index')->gatherMiddleware());
        $this->assertContains('library.manage', $routes->getByName('library.returns.index')->gatherMiddleware());
        $this->assertContains('library.manage', $routes->getByName('library.reports.index')->gatherMiddleware());
    }

    public function test_regular_user_can_only_view_their_own_loan()
    {
        $owner = $this->userWithRole('pegawai');
        $owner->id = 10;
        $other = $this->userWithRole('pegawai');
        $other->id = 11;
        $operator = $this->userWithRole('operator_perpustakaan');
        $operator->id = 12;

        $member = new Member();
        $member->user_id = $owner->id;

        $loan = new Loan();
        $loan->setRelation('member', $member);

        $this->assertTrue($loan->isVisibleTo($owner));
        $this->assertFalse($loan->isVisibleTo($other));
        $this->assertTrue($loan->isVisibleTo($operator));
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
