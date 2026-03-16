<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url');
        $this->apiKey = config('services.whatsapp.api_key');
    }

    /**
     * Send WhatsApp notification
     */
    public function send($phoneNumber, $message)
    {
        if (empty($this->apiUrl) || empty($this->apiKey)) {
            Log::info('[WA Notification] API not configured. Message to ' . $phoneNumber . ': ' . $message);
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->post($this->apiUrl, [
                'target' => $phoneNumber,
                'message' => $message,
            ]);

            Log::info('[WA Notification] Sent to ' . $phoneNumber . ': ' . ($response->successful() ? 'Success' : 'Failed'));
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('[WA Notification] Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify about new incoming mail
     */
    public function notifySuratMasuk($suratMasuk, $targetUser)
    {
        $url = url('/surat-masuk/' . $suratMasuk->id);
        $message = "*Surat Masuk Baru*\n\n"
            . "No. Surat: {$suratMasuk->nomor_surat}\n"
            . "Pengirim: {$suratMasuk->pengirim}\n"
            . "Perihal: {$suratMasuk->perihal}\n"
            . "Tanggal: {$suratMasuk->tanggal_surat}\n"
            . "Sifat: {$suratMasuk->sifat}\n\n"
            . "Tindak Lanjut: {$url}";

        return $this->send($targetUser->no_hp, $message);
    }

    /**
     * Notify about disposition
     */
    public function notifyDisposisi($disposisi, $targetUser)
    {
        $suratMasuk = $disposisi->suratMasuk;
        $dari = $disposisi->dariUser;
        $url = url('/surat-masuk/' . $suratMasuk->id);
        $tipe = $disposisi->tipe == 'naikan' ? 'Surat Dinaikkan' : 'Disposisi';

        $message = "*{$tipe}*\n\n"
            . "Dari: {$dari->name}\n"
            . "No. Surat: {$suratMasuk->nomor_surat}\n"
            . "Pengirim: {$suratMasuk->pengirim}\n"
            . "Perihal: {$suratMasuk->perihal}\n"
            . ($disposisi->petunjuk ? "Petunjuk: {$disposisi->petunjuk}\n" : "")
            . ($disposisi->catatan ? "Catatan: {$disposisi->catatan}\n" : "")
            . "\nTindak Lanjut: {$url}";

        return $this->send($targetUser->no_hp, $message);
    }
}
