<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateLibraryModuleTables extends Migration
{
    public function up()
    {
        Schema::create('library_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('library_shelves', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('library_books', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('author');
            $table->string('publisher')->nullable();
            $table->year('year')->nullable();
            $table->string('isbn', 20)->nullable()->unique();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('shelf_id')->nullable();
            $table->text('description')->nullable();
            $table->string('cover')->nullable();
            $table->integer('stock')->default(0);
            $table->timestamps();
            $table->foreign('category_id')->references('id')->on('library_categories')->onDelete('restrict');
            $table->foreign('shelf_id')->references('id')->on('library_shelves')->onDelete('set null');
            $table->index(['title', 'author']);
        });

        Schema::create('library_book_copies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('copy_code')->unique();
            $table->unsignedBigInteger('book_id');
            $table->enum('status', ['tersedia', 'dipinjam', 'rusak', 'hilang'])->default('tersedia');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('book_id')->references('id')->on('library_books')->onDelete('cascade');
            $table->index(['book_id', 'status']);
        });

        Schema::create('library_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('member_number')->unique();
            $table->string('name');
            $table->enum('gender', ['L', 'P']);
            $table->string('class_position')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('photo')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->date('valid_until')->nullable();
            $table->timestamps();
            $table->index(['name', 'status']);
        });

        Schema::create('library_loans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('loan_number')->unique();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('user_id');
            $table->date('loan_date');
            $table->date('due_date');
            $table->enum('status', ['dipinjam', 'dikembalikan', 'terlambat'])->default('dipinjam');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->foreign('member_id')->references('id')->on('library_members')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->index(['status', 'due_date']);
        });

        Schema::create('library_loan_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('book_copy_id');
            $table->timestamp('returned_at')->nullable();
            $table->string('condition')->nullable();
            $table->timestamps();
            $table->foreign('loan_id')->references('id')->on('library_loans')->onDelete('cascade');
            $table->foreign('book_copy_id')->references('id')->on('library_book_copies')->onDelete('restrict');
            $table->index(['book_copy_id', 'returned_at']);
        });

        Schema::create('library_returns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('user_id');
            $table->date('return_date');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->foreign('loan_id')->references('id')->on('library_loans')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::create('library_fines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('loan_item_id');
            $table->unsignedBigInteger('member_id');
            $table->integer('days_late')->default(0);
            $table->decimal('amount_per_day', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['belum_dibayar', 'lunas'])->default('belum_dibayar');
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->timestamps();
            $table->foreign('loan_item_id')->references('id')->on('library_loan_items')->onDelete('restrict');
            $table->foreign('member_id')->references('id')->on('library_members')->onDelete('restrict');
            $table->foreign('paid_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'member_id']);
        });

        Schema::create('library_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('label')->nullable();
            $table->string('type')->default('text');
            $table->timestamps();
        });

        $now = now();
        foreach ($this->defaultCategories() as $category) {
            DB::table('library_categories')->insert([
                'name' => $category[0],
                'slug' => Str::slug($category[0]),
                'description' => $category[1],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($this->defaultShelves() as $shelf) {
            DB::table('library_shelves')->insert(array_merge($shelf, ['created_at' => $now, 'updated_at' => $now]));
        }

        foreach ($this->defaultSettings() as $setting) {
            DB::table('library_settings')->insert(array_merge($setting, ['created_at' => $now, 'updated_at' => $now]));
        }

        DB::table('roles')->updateOrInsert(
            ['name' => 'operator_perpustakaan'],
            ['display_name' => 'Operator Perpustakaan', 'created_at' => $now, 'updated_at' => $now]
        );
    }

    public function down()
    {
        $roleId = DB::table('roles')->where('name', 'operator_perpustakaan')->value('id');
        if ($roleId) {
            DB::table('role_user')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }

        Schema::dropIfExists('library_fines');
        Schema::dropIfExists('library_returns');
        Schema::dropIfExists('library_loan_items');
        Schema::dropIfExists('library_loans');
        Schema::dropIfExists('library_members');
        Schema::dropIfExists('library_book_copies');
        Schema::dropIfExists('library_books');
        Schema::dropIfExists('library_shelves');
        Schema::dropIfExists('library_categories');
        Schema::dropIfExists('library_settings');
    }

    protected function defaultCategories()
    {
        return [
            ['Fiksi', 'Novel, cerpen, dan karya sastra fiksi'],
            ['Non-Fiksi', 'Buku berdasarkan fakta dan kenyataan'],
            ['Sains & Teknologi', 'Ilmu pengetahuan alam dan teknologi'],
            ['Sejarah', 'Sejarah dan peristiwa masa lalu'],
            ['Biografi', 'Perjalanan hidup tokoh'],
            ['Pendidikan', 'Buku pendidikan dan referensi'],
            ['Agama & Spiritualitas', 'Buku keagamaan dan spiritualitas'],
            ['Ekonomi & Bisnis', 'Ekonomi, bisnis, dan keuangan'],
            ['Kesehatan', 'Kesehatan dan kedokteran'],
            ['Seni & Budaya', 'Seni dan budaya'],
        ];
    }

    protected function defaultShelves()
    {
        return [
            ['code' => 'RAK-A', 'name' => 'Rak A - Fiksi & Sastra', 'location' => 'Ruang Perpustakaan'],
            ['code' => 'RAK-B', 'name' => 'Rak B - Ilmu Pengetahuan', 'location' => 'Ruang Perpustakaan'],
            ['code' => 'RAK-C', 'name' => 'Rak C - Sejarah & Biografi', 'location' => 'Ruang Perpustakaan'],
            ['code' => 'RAK-D', 'name' => 'Rak D - Pendidikan & Referensi', 'location' => 'Ruang Perpustakaan'],
            ['code' => 'RAK-E', 'name' => 'Rak E - Agama & Sosial', 'location' => 'Ruang Perpustakaan'],
        ];
    }

    protected function defaultSettings()
    {
        return [
            ['key' => 'library_name', 'value' => 'Perpustakaan PTA Papua Barat', 'label' => 'Nama Perpustakaan'],
            ['key' => 'library_address', 'value' => '', 'label' => 'Alamat'],
            ['key' => 'library_phone', 'value' => '', 'label' => 'No. Telepon'],
            ['key' => 'fine_per_day', 'value' => '1000', 'label' => 'Denda per Hari (Rp)'],
            ['key' => 'loan_days', 'value' => '7', 'label' => 'Durasi Pinjam (hari)'],
            ['key' => 'max_books_per_loan', 'value' => '3', 'label' => 'Maks. Buku per Peminjaman'],
        ];
    }
}
