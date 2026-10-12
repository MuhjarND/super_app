<?php

namespace Tests\Feature;

use App\Services\SignaturePadService;
use App\Services\SupplyRequestDocumentService;
use App\SupplyPickup;
use App\SupplyRequest;
use App\SupplyRequestItem;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplyRequestDocumentSignatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_saved_handover_signature_is_embedded_once_for_multiple_items()
    {
        $path = $this->storeSignature('first');
        $request = $this->requestWithSignatures(1, [$path, $path]);
        $html = $this->pdfHtml($request);
        $signature = app(SignaturePadService::class)->toDataUri($path);

        $this->assertSame(1, substr_count($html, $signature));
        $this->assertStringContainsString('Tanda tangan penerima barang', $html);
        $this->assertStringContainsString('Pemohon 1', $html);
    }

    public function test_batch_print_keeps_each_employees_signature_on_its_own_form()
    {
        $firstPath = $this->storeSignature('first', 0);
        $secondPath = $this->storeSignature('second', 30);
        $requests = collect([
            $this->requestWithSignatures(1, [$firstPath]),
            $this->requestWithSignatures(2, [$secondPath]),
        ]);
        $html = $this->pdfHtml($requests);
        $first = app(SignaturePadService::class)->toDataUri($firstPath);
        $second = app(SignaturePadService::class)->toDataUri($secondPath);
        $sections = explode('<div class="sheet">', $html);

        $this->assertCount(3, $sections);
        $this->assertStringContainsString($first, $sections[1]);
        $this->assertStringNotContainsString($second, $sections[1]);
        $this->assertStringContainsString($second, $sections[2]);
        $this->assertStringNotContainsString($first, $sections[2]);
    }

    public function test_unsigned_request_does_not_use_profile_signature()
    {
        $request = $this->requestWithSignatures(1, []);
        $request->requester->profile_signature_path = $this->storeSignature('profile');

        $this->assertStringNotContainsString('alt="Tanda tangan penerima barang"', $this->pdfHtml($request));
    }

    public function test_missing_legacy_signature_does_not_break_printing()
    {
        $request = $this->requestWithSignatures(1, ['persediaan/pickups/missing.png']);

        $this->assertStringNotContainsString('alt="Tanda tangan penerima barang"', $this->pdfHtml($request));
    }

    public function test_printing_loads_saved_pickup_signatures_when_relation_was_not_loaded()
    {
        Schema::create('supply_pickups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supply_request_id');
            $table->string('receiver_signature_path');
        });
        $path = $this->storeSignature('historical');
        \DB::table('supply_pickups')->insert([
            'supply_request_id' => 1,
            'receiver_signature_path' => $path,
        ]);
        $request = $this->requestWithSignatures(1, []);
        $request->submitted_at = '2026-05-22 10:00:00';
        $request->status = SupplyRequest::STATUS_FULFILLED;
        $request->unsetRelation('pickups');
        $request->exists = true;

        $this->assertStringContainsString(
            app(SignaturePadService::class)->toDataUri($path),
            $this->pdfHtml($request)
        );
        $this->assertTrue($request->relationLoaded('pickups'));
    }

    public function test_batch_print_loads_historical_signatures_without_a_submission_date_limit()
    {
        Schema::create('supply_pickups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supply_request_id');
            $table->string('receiver_signature_path');
        });
        $firstPath = $this->storeSignature('old-first');
        $secondPath = $this->storeSignature('old-second', 30);
        \DB::table('supply_pickups')->insert([
            ['supply_request_id' => 1, 'receiver_signature_path' => $firstPath],
            ['supply_request_id' => 1, 'receiver_signature_path' => $firstPath],
            ['supply_request_id' => 2, 'receiver_signature_path' => $secondPath],
        ]);
        $firstRequest = $this->requestWithSignatures(1, []);
        $secondRequest = $this->requestWithSignatures(2, []);
        $firstRequest->submitted_at = '2026-05-22 10:00:00';
        $secondRequest->submitted_at = '2026-06-10 10:00:00';
        foreach ([$firstRequest, $secondRequest] as $request) {
            $request->status = SupplyRequest::STATUS_FULFILLED;
            $request->exists = true;
            $request->unsetRelation('pickups');
        }

        $html = $this->pdfHtml(collect([$firstRequest, $secondRequest]));
        $sections = explode('<div class="sheet">', $html);
        $firstSignature = app(SignaturePadService::class)->toDataUri($firstPath);
        $secondSignature = app(SignaturePadService::class)->toDataUri($secondPath);

        $this->assertCount(3, $sections);
        $this->assertSame(1, substr_count($sections[1], $firstSignature));
        $this->assertStringNotContainsString($secondSignature, $sections[1]);
        $this->assertStringContainsString('22 Mei 2026', $sections[1]);
        $this->assertSame(1, substr_count($sections[2], $secondSignature));
        $this->assertStringNotContainsString($firstSignature, $sections[2]);
        $this->assertStringContainsString('10 Juni 2026', $sections[2]);
    }

    public function test_pdf_with_signature_renders_successfully()
    {
        $request = $this->requestWithSignatures(1, [$this->storeSignature('render')]);
        $pdf = app(SupplyRequestDocumentService::class)->makePdf($request);

        $this->assertStringStartsWith('%PDF-', $pdf->output());
        $this->assertSame(1, $pdf->getDomPDF()->getCanvas()->get_page_count());
    }

    public function test_batch_pdf_renders_one_form_per_page()
    {
        $requests = collect([
            $this->requestWithSignatures(1, [$this->storeSignature('render-first')]),
            $this->requestWithSignatures(2, [$this->storeSignature('render-second', 30)]),
        ]);
        $pdf = app(SupplyRequestDocumentService::class)->makePdf($requests);

        $pdf->output();
        $this->assertSame(2, $pdf->getDomPDF()->getCanvas()->get_page_count());
    }

    protected function pdfHtml($requests)
    {
        return app(SupplyRequestDocumentService::class)->makePdf($requests)
            ->getDomPDF()->getDom()->saveHTML();
    }

    protected function requestWithSignatures($id, array $paths)
    {
        $request = new SupplyRequest([
            'request_number' => 'PS-20261012-' . $id,
            'purpose' => 'Kebutuhan administrasi kantor',
            'submitted_at' => '2026-10-12 10:00:00',
        ]);
        $request->id = $id;
        $requester = new User();
        $requester->name = 'Pemohon ' . $id;
        $requester->nip = '198001012010011001';
        $request->setRelation('requester', $requester);
        $request->setRelation('items', collect([new SupplyRequestItem([
            'item_name_snapshot' => 'Kertas A4',
            'unit_snapshot' => 'Rim',
            'quantity_requested' => 2,
        ])]));
        $request->setRelation('pickups', collect($paths)->map(function ($path, $index) {
            $pickup = new SupplyPickup(['receiver_signature_path' => $path]);
            $pickup->id = $index + 1;
            return $pickup;
        }));

        return $request;
    }

    protected function storeSignature($name, $offset = 0)
    {
        $image = imagecreatetruecolor(360, 120);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        $ink = imagecolorallocate($image, 20, 35, 65);
        imagesetthickness($image, 3);
        imageline($image, 25, 85, 85, 20 + $offset, $ink);
        imageline($image, 85, 20 + $offset, 65, 90, $ink);
        imageline($image, 65, 90, 280, 50 + $offset, $ink);
        imageline($image, 40, 95, 315, 95, $ink);
        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);
        $path = 'persediaan/pickups/' . $name . '.png';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
