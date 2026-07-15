<?php

use App\Library\Book;
use App\Library\BookCopy;
use App\Library\Category;
use App\Library\Fine;
use App\Library\Loan;
use App\Library\LoanItem;
use App\Library\Member;
use App\Library\ReturnModel;
use App\Library\Shelf;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LibraryDummySeeder extends Seeder
{
    public function run()
    {
        $operator = User::orderBy('id')->first();

        if (!$operator) {
            $this->command->error('Data dummy perpustakaan tidak dibuat karena belum ada user.');
            return;
        }

        DB::transaction(function () use ($operator) {
            $categories = $this->seedCategories();
            $shelves = $this->seedShelves();
            $copies = $this->seedBooks($categories, $shelves);
            $members = $this->seedMembers();

            $this->seedLoans($operator, $copies, $members);
        });

        $this->command->info('Data dummy modul perpustakaan berhasil dibuat.');
    }

    protected function seedCategories()
    {
        $definitions = [
            'Hukum' => 'Buku hukum, peraturan, dan referensi peradilan.',
            'Administrasi' => 'Buku tata kelola dan administrasi perkantoran.',
            'Teknologi Informasi' => 'Referensi teknologi informasi dan transformasi digital.',
            'Pelayanan Publik' => 'Referensi peningkatan kualitas pelayanan publik.',
        ];

        $categories = collect();
        foreach ($definitions as $name => $description) {
            $categories->put($name, Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $description]
            ));
        }

        return $categories;
    }

    protected function seedShelves()
    {
        $definitions = [
            'RAK-HKM' => ['name' => 'Rak Hukum', 'location' => 'Ruang Perpustakaan'],
            'RAK-ADM' => ['name' => 'Rak Administrasi', 'location' => 'Ruang Perpustakaan'],
            'RAK-TI' => ['name' => 'Rak Teknologi Informasi', 'location' => 'Ruang Perpustakaan'],
            'RAK-PP' => ['name' => 'Rak Pelayanan Publik', 'location' => 'Ruang Perpustakaan'],
        ];

        $shelves = collect();
        foreach ($definitions as $code => $attributes) {
            $shelves->put($code, Shelf::firstOrCreate(['code' => $code], $attributes));
        }

        return $shelves;
    }

    protected function seedBooks($categories, $shelves)
    {
        $definitions = [
            ['9786230001001', 'Pengantar Hukum Indonesia', 'Tim Hukum Indonesia', 'Pustaka Yustisia', 2022, 'Hukum', 'RAK-HKM', 3],
            ['9786230001002', 'Hukum Acara Peradilan Agama', 'Ahmad Mujahidin', 'Sinar Grafika', 2021, 'Hukum', 'RAK-HKM', 3],
            ['9786230001003', 'Kompilasi Hukum Islam', 'Mahkamah Agung RI', 'Direktorat Badilag', 2020, 'Hukum', 'RAK-HKM', 2],
            ['9786230001004', 'Administrasi Perkantoran Modern', 'Sedarmayanti', 'Mandar Maju', 2023, 'Administrasi', 'RAK-ADM', 3],
            ['9786230001005', 'Manajemen Arsip Dinamis', 'Sambas Ali Muhidin', 'Pustaka Setia', 2022, 'Administrasi', 'RAK-ADM', 2],
            ['9786230001006', 'Tata Naskah Dinas Elektronik', 'Tim Administrasi Negara', 'Media Aparatur', 2024, 'Administrasi', 'RAK-ADM', 2],
            ['9786230001007', 'Transformasi Digital Pemerintahan', 'Richardus Eko Indrajit', 'Andi', 2023, 'Teknologi Informasi', 'RAK-TI', 3],
            ['9786230001008', 'Keamanan Informasi Dasar', 'Budi Rahardjo', 'Informatika', 2022, 'Teknologi Informasi', 'RAK-TI', 2],
            ['9786230001009', 'Pelayanan Publik Prima', 'Hardiyansyah', 'Gava Media', 2021, 'Pelayanan Publik', 'RAK-PP', 3],
            ['9786230001010', 'Zona Integritas dan Reformasi Birokrasi', 'Tim Reformasi Birokrasi', 'Media Aparatur', 2024, 'Pelayanan Publik', 'RAK-PP', 2],
        ];

        $copies = collect();
        foreach ($definitions as $bookIndex => $definition) {
            list($isbn, $title, $author, $publisher, $year, $category, $shelf, $quantity) = $definition;

            $book = Book::updateOrCreate(
                ['isbn' => $isbn],
                [
                    'title' => $title,
                    'author' => $author,
                    'publisher' => $publisher,
                    'year' => $year,
                    'category_id' => $categories->get($category)->id,
                    'shelf_id' => $shelves->get($shelf)->id,
                    'description' => 'Data contoh koleksi perpustakaan SIMANTAP.',
                    'stock' => $quantity,
                ]
            );

            for ($copyIndex = 1; $copyIndex <= $quantity; $copyIndex++) {
                $code = sprintf('DUMMY-BK-%03d-%02d', $bookIndex + 1, $copyIndex);
                $copies->put($code, BookCopy::updateOrCreate(
                    ['copy_code' => $code],
                    [
                        'book_id' => $book->id,
                        'notes' => 'Eksemplar data contoh.',
                    ]
                ));
            }
        }

        return $copies;
    }

    protected function seedMembers()
    {
        $definitions = [
            ['DUMMY-AG-001', 'Andi Pratama', 'L', 'Staf Kepaniteraan', '081200000001'],
            ['DUMMY-AG-002', 'Maria Wenda', 'P', 'Staf Kesekretariatan', '081200000002'],
            ['DUMMY-AG-003', 'Yusuf Krey', 'L', 'PPNPN', '081200000003'],
            ['DUMMY-AG-004', 'Debora Mandacan', 'P', 'Staf Umum dan Keuangan', '081200000004'],
            ['DUMMY-AG-005', 'Rizky Saputra', 'L', 'Staf Kepegawaian', '081200000005'],
            ['DUMMY-AG-006', 'Marlina Rumbruren', 'P', 'Staf Perencanaan', '081200000006'],
        ];

        $members = collect();
        foreach ($definitions as $index => $definition) {
            list($number, $name, $gender, $position, $phone) = $definition;
            $members->put($number, Member::updateOrCreate(
                ['member_number' => $number],
                [
                    'name' => $name,
                    'gender' => $gender,
                    'class_position' => $position,
                    'address' => 'Manokwari, Papua Barat',
                    'phone' => $phone,
                    'email' => 'anggota' . ($index + 1) . '@example.test',
                    'status' => 'aktif',
                    'valid_until' => Carbon::today()->addYear(),
                ]
            ));
        }

        return $members;
    }

    protected function seedLoans(User $operator, $copies, $members)
    {
        $definitions = [
            [
                'number' => 'DUMMY-PM-001',
                'member' => 'DUMMY-AG-001',
                'copy' => 'DUMMY-BK-001-01',
                'loan_date' => Carbon::today()->subDays(2),
                'due_date' => Carbon::today()->addDays(5),
                'status' => 'dipinjam',
            ],
            [
                'number' => 'DUMMY-PM-002',
                'member' => 'DUMMY-AG-002',
                'copy' => 'DUMMY-BK-004-01',
                'loan_date' => Carbon::today()->subDays(14),
                'due_date' => Carbon::today()->subDays(7),
                'status' => 'terlambat',
            ],
            [
                'number' => 'DUMMY-PM-003',
                'member' => 'DUMMY-AG-003',
                'copy' => 'DUMMY-BK-007-01',
                'loan_date' => Carbon::today()->subDays(20),
                'due_date' => Carbon::today()->subDays(13),
                'status' => 'dikembalikan',
                'return_date' => Carbon::today()->subDays(14),
            ],
            [
                'number' => 'DUMMY-PM-004',
                'member' => 'DUMMY-AG-004',
                'copy' => 'DUMMY-BK-009-01',
                'loan_date' => Carbon::today()->subDays(25),
                'due_date' => Carbon::today()->subDays(18),
                'status' => 'dikembalikan',
                'return_date' => Carbon::today()->subDays(14),
                'fine_status' => 'belum_dibayar',
            ],
            [
                'number' => 'DUMMY-PM-005',
                'member' => 'DUMMY-AG-005',
                'copy' => 'DUMMY-BK-010-01',
                'loan_date' => Carbon::today()->subDays(35),
                'due_date' => Carbon::today()->subDays(28),
                'status' => 'dikembalikan',
                'return_date' => Carbon::today()->subDays(25),
                'fine_status' => 'lunas',
            ],
        ];

        foreach ($definitions as $definition) {
            $loan = Loan::updateOrCreate(
                ['loan_number' => $definition['number']],
                [
                    'member_id' => $members->get($definition['member'])->id,
                    'user_id' => $operator->id,
                    'loan_date' => $definition['loan_date'],
                    'due_date' => $definition['due_date'],
                    'status' => $definition['status'],
                    'note' => 'Transaksi contoh modul perpustakaan.',
                ]
            );

            $copy = $copies->get($definition['copy']);
            $isReturned = $definition['status'] === 'dikembalikan';
            $item = LoanItem::updateOrCreate(
                ['loan_id' => $loan->id, 'book_copy_id' => $copy->id],
                [
                    'returned_at' => $isReturned ? $definition['return_date'] : null,
                    'condition' => $isReturned ? 'Baik' : null,
                ]
            );

            $copy->update(['status' => $isReturned ? 'tersedia' : 'dipinjam']);

            if ($isReturned) {
                ReturnModel::updateOrCreate(
                    ['loan_id' => $loan->id],
                    [
                        'user_id' => $operator->id,
                        'return_date' => $definition['return_date'],
                        'note' => 'Pengembalian data contoh.',
                    ]
                );
            }

            if (isset($definition['fine_status'])) {
                $daysLate = Carbon::parse($definition['due_date'])
                    ->diffInDays(Carbon::parse($definition['return_date']));

                Fine::updateOrCreate(
                    ['loan_item_id' => $item->id],
                    [
                        'member_id' => $loan->member_id,
                        'days_late' => $daysLate,
                        'amount_per_day' => 1000,
                        'total_amount' => $daysLate * 1000,
                        'status' => $definition['fine_status'],
                        'paid_at' => $definition['fine_status'] === 'lunas' ? $definition['return_date'] : null,
                        'paid_by' => $definition['fine_status'] === 'lunas' ? $operator->id : null,
                    ]
                );
            }
        }
    }
}
