<?php

namespace App\Services;

use App\User;
use App\WhatsAppMagicLoginToken;
use Illuminate\Support\Str;

class WhatsAppMagicLinkService
{
    public function create(User $user, $destinationUrl)
    {
        $destinationUrl = $this->normalizeDestination($destinationUrl);
        $plainToken = Str::random(64);

        WhatsAppMagicLoginToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'destination_url' => $destinationUrl,
            'expires_at' => now()->addDays($this->lifetimeDays()),
        ]);

        return route('whatsapp.magic-login.consume', ['token' => $plainToken]);
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
}
