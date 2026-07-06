<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class TwoFactorAuthenticationService
{
    protected $issuer = 'PAPEDA';

    public function generateSecret($length = 32)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $secret;
    }

    public function encryptSecret($secret)
    {
        return Crypt::encryptString($secret);
    }

    public function decryptSecret($encryptedSecret)
    {
        if (!$encryptedSecret) {
            return null;
        }

        return Crypt::decryptString($encryptedSecret);
    }

    public function verifyCode($secret, $code, $window = 1)
    {
        $normalizedCode = preg_replace('/\D+/', '', (string) $code);
        if (strlen($normalizedCode) !== 6) {
            return false;
        }

        $currentSlice = (int) floor(time() / 30);
        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals($this->generateCodeForSlice($secret, $currentSlice + $offset), $normalizedCode)) {
                return true;
            }
        }

        return false;
    }

    public function provisioningUri(User $user, $secret)
    {
        $label = rawurlencode($this->issuer . ':' . ($user->email ?: $user->username ?: $user->name));
        $issuer = rawurlencode($this->issuer);

        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&digits=6&period=30";
    }

    public function generateRecoveryCodes($count = 8)
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(4) . '-' . Str::random(4));
        }

        return $codes;
    }

    public function encryptRecoveryCodes(array $codes)
    {
        return Crypt::encryptString(json_encode(array_values($codes)));
    }

    public function decryptRecoveryCodes($encryptedCodes)
    {
        if (!$encryptedCodes) {
            return [];
        }

        $decoded = json_decode(Crypt::decryptString($encryptedCodes), true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    public function useRecoveryCode(User $user, $recoveryCode)
    {
        $codes = $this->decryptRecoveryCodes($user->two_factor_recovery_codes);
        $normalizedRecoveryCode = $this->normalizeRecoveryCode($recoveryCode);

        foreach ($codes as $index => $code) {
            if ($this->normalizeRecoveryCode($code) !== $normalizedRecoveryCode) {
                continue;
            }

            unset($codes[$index]);

            $user->forceFill([
                'two_factor_recovery_codes' => empty($codes)
                    ? null
                    : $this->encryptRecoveryCodes(array_values($codes)),
            ])->save();

            return true;
        }

        return false;
    }

    public function remainingRecoveryCodeCount(User $user)
    {
        return count($this->decryptRecoveryCodes($user->two_factor_recovery_codes));
    }

    public function normalizeRecoveryCode($code)
    {
        return strtoupper(trim((string) $code));
    }

    protected function generateCodeForSlice($secret, $timeSlice)
    {
        $secretKey = $this->base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncatedHash = (
            ((ord($hash[$offset + 0]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        $code = $truncatedHash % 1000000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    protected function base32Decode($secret)
    {
        if ($secret === '') {
            return '';
        }

        $map = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $secret = strtoupper($secret);
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';

        foreach (str_split($secret) as $char) {
            if (!isset($map[$char])) {
                continue;
            }

            $buffer = ($buffer << 5) | $map[$char];
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer & (0xFF << $bitsLeft)) >> $bitsLeft);
            }
        }

        return $result;
    }
}
