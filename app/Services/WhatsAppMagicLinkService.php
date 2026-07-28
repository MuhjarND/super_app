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

        return $this->absoluteApplicationUrl(
            route('whatsapp.magic-login.consume', ['token' => $plainToken], false)
        );
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

            if (!$this->isApplicationUrl($url) || $this->isMagicLoginUrl($url)) {
                return $matches[0];
            }

            return $this->create($user, $url) . $suffix;
        }, (string) $message);
    }

    public function normalizeDestination($destinationUrl)
    {
        $destinationUrl = trim((string) $destinationUrl);

        if (!$this->isApplicationUrl($destinationUrl)) {
            return $this->absoluteApplicationUrl(route('dashboard', [], false));
        }

        return $this->absoluteApplicationUrl($destinationUrl);
    }

    protected function isApplicationUrl($url)
    {
        $candidate = parse_url($url);

        if (empty($candidate['scheme']) || empty($candidate['host'])) {
            return false;
        }

        $candidatePort = $candidate['port'] ?? $this->defaultPort($candidate['scheme'] ?? null);

        foreach ($this->recognizedApplicationUrls() as $applicationUrl) {
            $application = parse_url($applicationUrl);
            $applicationPort = $application['port'] ?? $this->defaultPort($application['scheme'] ?? null);

            if (strtolower($candidate['scheme']) === strtolower($application['scheme'] ?? '')
                && strtolower($candidate['host']) === strtolower($application['host'] ?? '')
                && $candidatePort === $applicationPort) {
                return true;
            }
        }

        return false;
    }

    protected function isMagicLoginUrl($url)
    {
        return strpos(parse_url($url, PHP_URL_PATH) ?: '', '/masuk/whatsapp/') === 0;
    }

    protected function recognizedApplicationUrls()
    {
        return array_values(array_unique(array_filter([
            config('services.whatsapp.application_url'),
            config('app.url'),
        ])));
    }

    protected function absoluteApplicationUrl($url)
    {
        $baseUrl = rtrim((string) config('services.whatsapp.application_url', config('app.url')), '/');
        $parts = parse_url((string) $url);

        if (empty($parts['host'])) {
            return $baseUrl . '/' . ltrim((string) $url, '/');
        }

        $result = $baseUrl . '/' . ltrim($parts['path'] ?? '/', '/');
        if (!empty($parts['query'])) {
            $result .= '?' . $parts['query'];
        }
        if (!empty($parts['fragment'])) {
            $result .= '#' . $parts['fragment'];
        }

        return $result;
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
