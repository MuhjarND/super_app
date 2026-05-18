<?php

namespace App\Http\Controllers;

use App\InventoryItemDetail;
use Barryvdh\DomPDF\Facade as PDF;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PersediaanQrController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        abort_unless(auth()->user()->canAccessInventoryModule(), 403);

        $details = InventoryItemDetail::with('item')->orderBy('sub_code')->paginate(18);

        return view('persediaan.qr.index', compact('details'));
    }

    public function print()
    {
        abort_unless(auth()->user()->canAccessInventoryModule(), 403);

        $details = InventoryItemDetail::with('item')->orderBy('sub_code')->get()->map(function ($detail) {
            $detail->qr_svg = base64_encode(QrCode::format('svg')->size(180)->errorCorrection('H')->generate($detail->sub_code));
            return $detail;
        });

        $verifier = app(\App\Services\PdfVerificationService::class);
        $verification = $verifier->begin('perawatan_alat_mesin', 'qr_sub_barang', null, 'Cetak QR Code Alat dan Mesin', [], ['jumlah_sub_barang' => $details->count()]);
        $pdfVerification = $verifier->viewData($verification);
        $pdf = PDF::loadView('persediaan.qr.print', compact('details', 'pdfVerification'))->setPaper('a4');

        return $verifier->response($pdf->output(), $verification, 'qrcode-alat-dan-mesin.pdf');
    }
}
