<?php

namespace App\Services;

use App\SuratKeluar;
use App\SuratMasuk;
use Carbon\CarbonInterface;

class DocumentShareLinkService
{
    const TYPE_INCOMING = 1;
    const TYPE_OUTGOING = 2;
    const SIGNATURE_BYTES = 8;

    public function incomingUrl(SuratMasuk $suratMasuk)
    {
        return route('document-share.open', [
            'token' => $this->token(self::TYPE_INCOMING, $suratMasuk->id),
        ]);
    }

    public function outgoingUrl(SuratKeluar $suratKeluar)
    {
        return route('document-share.open', [
            'token' => $this->token(self::TYPE_OUTGOING, $suratKeluar->id),
        ]);
    }

    public function token($type, $documentId, CarbonInterface $expiresAt = null)
    {
        $expiresAt = $expiresAt ?: now('Asia/Jayapura')->addDays(7);
        $payload = pack('CNN', (int) $type, (int) $documentId, (int) $expiresAt->timestamp);
        $signature = substr(hash_hmac('sha256', $payload, $this->signingKey(), true), 0, self::SIGNATURE_BYTES);

        return rtrim(strtr(base64_encode($payload . $signature), '+/', '-_'), '=');
    }

    public function resolve($token)
    {
        $encoded = strtr((string) $token, '-_', '+/');
        $encoded .= str_repeat('=', (4 - (strlen($encoded) % 4)) % 4);
        $binary = base64_decode($encoded, true);

        if ($binary === false || strlen($binary) !== (9 + self::SIGNATURE_BYTES)) {
            return null;
        }

        $payload = substr($binary, 0, 9);
        $signature = substr($binary, 9);
        $expected = substr(hash_hmac('sha256', $payload, $this->signingKey(), true), 0, self::SIGNATURE_BYTES);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $data = unpack('Ctype/Ndocument_id/Nexpires_at', $payload);
        if (!in_array((int) $data['type'], [self::TYPE_INCOMING, self::TYPE_OUTGOING], true)) {
            return null;
        }

        return [
            'type' => (int) $data['type'],
            'document_id' => (int) $data['document_id'],
            'expires_at' => (int) $data['expires_at'],
            'expired' => (int) $data['expires_at'] < now('Asia/Jayapura')->timestamp,
        ];
    }

    protected function signingKey()
    {
        $key = (string) config('app.key');
        if (strpos($key, 'base64:') === 0) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $key;
    }
}
