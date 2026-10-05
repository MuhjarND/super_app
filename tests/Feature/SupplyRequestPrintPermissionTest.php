<?php

namespace Tests\Feature;

use App\Http\Controllers\SupplyRequestController;
use App\Jabatan;
use App\Role;
use App\Services\SignaturePadService;
use App\Services\SupplyRequestDocumentService;
use App\Services\WhatsAppNotificationService;
use App\SupplyRequest;
use App\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SupplyRequestPrintPermissionTest extends TestCase
{
    public function test_other_users_cannot_access_any_form_print_action()
    {
        foreach (['pegawai', 'super_admin', 'admin'] as $role) {
            $this->actingAs($this->user($role));
            $controller = $this->controller();
            $documents = $this->createMock(SupplyRequestDocumentService::class);
            $documents->expects($this->never())->method('makePdf');

            foreach (['report', 'reportPdf', 'print'] as $action) {
                try {
                    if ($action === 'report') {
                        $controller->report(Request::create('/'));
                    } elseif ($action === 'reportPdf') {
                        $controller->reportPdf(Request::create('/'), $documents);
                    } else {
                        $controller->print($documents, new SupplyRequest());
                    }
                    $this->fail('Print action must reject role ' . $role);
                } catch (HttpException $exception) {
                    $this->assertSame(403, $exception->getStatusCode());
                }
            }
        }
    }

    public function test_operator_and_turt_can_print_other_employees_requests()
    {
        foreach ([$this->user('operator_persediaan'), $this->user('kasubag', 'KASUBAG_TURT')] as $user) {
            $this->actingAs($user);
            $request = $this->getMockBuilder(SupplyRequest::class)->onlyMethods(['load'])->getMock();
            $request->user_id = 99;
            $request->expects($this->once())->method('load')->with(['requester', 'items'])->willReturnSelf();

            $pdf = new class {
                public function download($filename) { return response('PDF', 200); }
            };
            $documents = $this->createMock(SupplyRequestDocumentService::class);
            $documents->expects($this->once())->method('makePdf')->with($request)->willReturn($pdf);
            $documents->method('filename')->willReturn('formulir.pdf');

            $this->assertSame(200, $this->controller()->print($documents, $request)->getStatusCode());
        }
    }

    protected function controller()
    {
        return new SupplyRequestController(
            $this->createMock(WhatsAppNotificationService::class),
            $this->createMock(SignaturePadService::class)
        );
    }

    protected function user($role, $position = null)
    {
        $user = new User();
        $user->id = 1;
        $roleModel = new Role();
        $roleModel->name = $role;
        $user->setRelation('roles', collect([$roleModel]));
        $jabatan = null;
        if ($position) {
            $jabatan = new Jabatan();
            $jabatan->kode = $position;
        }
        $user->setRelation('jabatan', $jabatan);
        $user->setRelation('activeJabatanDelegations', collect());

        return $user;
    }
}
