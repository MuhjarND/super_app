<?php

namespace Tests\Unit;

use App\Http\Controllers\DisposisiController;
use App\Jabatan;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class DisposisiTargetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('jabatans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode')->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('level')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('jabatans');

        parent::tearDown();
    }

    public function test_ketua_can_dispose_to_wakil_ketua(): void
    {
        $jabatans = $this->createStructuralJabatans();
        $user = $this->userWithJabatan($jabatans['KPTA']);

        $targetIds = $this->targetIdsFor($user);

        $this->assertContains($jabatans['WKPTA']->id, $targetIds);
        $this->assertContains($jabatans['SEK']->id, $targetIds);
        $this->assertContains($jabatans['PAN']->id, $targetIds);
        $this->assertNotContains($jabatans['KPTA']->id, $targetIds);
    }

    public function test_wakil_ketua_cannot_dispose_back_to_ketua(): void
    {
        $jabatans = $this->createStructuralJabatans();
        $user = $this->userWithJabatan($jabatans['WKPTA']);

        $targetIds = $this->targetIdsFor($user);

        $this->assertNotContains($jabatans['KPTA']->id, $targetIds);
        $this->assertNotContains($jabatans['WKPTA']->id, $targetIds);
        $this->assertContains($jabatans['SEK']->id, $targetIds);
        $this->assertContains($jabatans['PAN']->id, $targetIds);
    }

    protected function createStructuralJabatans(): array
    {
        return collect([
            ['nama' => 'Ketua PTA', 'kode' => 'KPTA', 'level' => 1],
            ['nama' => 'Wakil Ketua PTA', 'kode' => 'WKPTA', 'level' => 1],
            ['nama' => 'Sekretaris', 'kode' => 'SEK', 'level' => 2],
            ['nama' => 'Panitera', 'kode' => 'PAN', 'level' => 2],
        ])->mapWithKeys(function ($attributes) {
            $jabatan = Jabatan::create($attributes);

            return [$jabatan->kode => $jabatan];
        })->all();
    }

    protected function userWithJabatan(Jabatan $jabatan): User
    {
        $user = new User();
        $user->forceFill([
            'id' => $jabatan->id + 100,
            'jabatan_id' => $jabatan->id,
        ]);
        $user->setRelation('jabatan', $jabatan);
        $user->setRelation('activeJabatanDelegations', collect());
        $user->setRelation('roles', collect());

        return $user;
    }

    protected function targetIdsFor(User $user): array
    {
        $controller = (new ReflectionClass(DisposisiController::class))
            ->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(DisposisiController::class, 'targetJabatanIdsFor');
        $method->setAccessible(true);

        return array_map('intval', $method->invoke($controller, $user, 'disposisi'));
    }
}
