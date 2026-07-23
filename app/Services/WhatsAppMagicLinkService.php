<?php

namespace App\Services;

use App\User;
use App\WhatsAppMagicLoginToken;

class WhatsAppMagicLinkService
{
    public function create(User $user, $destinationUrl)
    {
        $destinationUrl = $this->normalizeDestination($destinationUrl);
        $expiresAt = now()->addDays($this->lifetimeDays());
        $plainToken = $this->makeSignedToken($user, $destinationUrl, $expiresAt->timestamp);

        WhatsAppMagicLoginToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'destination_url' => $destinationUrl,
            'expires_at' => $expiresAt,
        ]);

        return route('whatsapp.magic-login.consume', ['token' => $plainToken]);
    }

    public function resolveSignedToken($token)
    {
        $parts = explode('.', strtolower(trim((string) $token)));

        if (count($parts) !== 6 || $parts[0] !== 'v1') {
            return null;
        }

        list($version, $userId, $expiresAt, $destinationHex, $nonce, $signature) = $parts;
        if (!ctype_digit($userId)
            || !ctype_digit($expiresAt)
            || $destinationHex === ''
            || !ctype_xdigit($destinationHex)
            || strlen($destinationHex) % 2 !== 0
            || !preg_match('/^[a-f0-9]{32}$/', $nonce)
            || !preg_match('/^[a-f0-9]{64}$/', $signature)) {
            return null;
        }

        $payload = implode('.', [$version, $userId, $expiresAt, $destinationHex, $nonce]);
        if (!hash_equals($this->sign($payload), $signature) || (int) $expiresAt < now()->timestamp) {
            return null;
        }

        $destinationUrl = hex2bin($destinationHex);
        if ($destinationUrl === false) {
            return null;
        }

        return [
            'user_id' => (int) $userId,
            'destination_url' => $this->normalizeDestination($destinationUrl),
            'expires_at' => (int) $expiresAt,
        ];
    }

    public function replaceApplicationUrls(User $user, $message)
    {
        return preg_replace_callback('/https?:\/\/[^\s]+/i', function ($matches) use ($user) {
            $url = rtrim($matches[0], '.,;:)');
            $suffix = substr($matches[0], strlen($url));

            if (!$this->isApplicationUrl($url) || $this->isMagicLoginUrl($url) || $this->isSignedDocumentUrl($url)) {
                return $matches[0];
            }

            return $this->create($user, $url) . $suffix;
        }, (string) $message);
    }

    public function normalizeDestination($destinationUrl)
    {
        $destinationUrl = trim((string) $destinationUrl);

        if (!$this->isApplicationUrl($destinationUrl)) {
            return route('dashboard');
        }

        return $destinationUrl;
    }

    protected function isApplicationUrl($url)
    {
        $application = parse_url(config('app.url'));
        $candidate = parse_url($url);

        if (empty($candidate['scheme']) || empty($candidate['host'])) {
            return false;
        }

        $applicationPort = $application['port'] ?? $this->defaultPort($application['scheme'] ?? null);
        $candidatePort = $candidate['port'] ?? $this->defaultPort($candidate['scheme'] ?? null);

        return strtolower($candidate['scheme']) === strtolower($application['scheme'] ?? '')
            && strtolower($candidate['host']) === strtolower($application['host'] ?? '')
            && $candidatePort === $applicationPort;
    }

    protected function isMagicLoginUrl($url)
    {
        return strpos(parse_url($url, PHP_URL_PATH) ?: '', '/masuk/whatsapp/') === 0;
    }

    protected function isSignedDocumentUrl($url)
    {
        if (preg_match('#^/surat-keluar/\d+/file$#', parse_url($url, PHP_URL_PATH) ?: '') !== 1) {
            return false;
        }

        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

        return !empty($query['signature']) && !empty($query['expires']);
    }

    protected function defaultPort($scheme)
    {
        return strtolower((string) $scheme) === 'https' ? 443 : 80;
    }

    protected function lifetimeDays()
    {
        return max(1, (int) config('services.whatsapp.magic_link_ttl_days', 14));
    }

    protected function makeSignedToken(User $user, $destinationUrl, $expiresAt)
    {
        $payload = implode('.', [
            'v1',
            (int) $user->getKey(),
            (int) $expiresAt,
            bin2hex($destinationUrl),
            bin2hex(random_bytes(16)),
        ]);

        return $payload . '.' . $this->sign($payload);
    }

    protected function sign($payload)
    {
        return hash_hmac('sha256', (string) $payload, (string) config('app.key'));
    }
}
