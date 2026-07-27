<?php

namespace App\Services;

use App\User;
use App\WebPushSubscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushNotificationService
{
    public function isConfigured()
    {
        return !empty(config('services.webpush.public_key'))
            && !empty(config('services.webpush.private_key'))
            && Schema::hasTable('web_push_subscriptions');
    }

    public function sendToUser(User $user, array $payload)
    {
        if (!$this->isConfigured()) {
            return 0;
        }

        $subscriptions = WebPushSubscription::where('user_id', $user->id)
            ->whereNull('failed_at')
            ->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ],
        ], [
            'TTL' => 86400,
            'urgency' => 'high',
            'batchSize' => 50,
        ], 15);
        $webPush->setReuseVAPIDHeaders(true);

        $json = json_encode([
            'title' => Str::limit((string) ($payload['title'] ?? 'PAPEDA'), 80, ''),
            'body' => Str::limit((string) ($payload['body'] ?? 'Terdapat informasi baru untuk Anda.'), 180),
            'url' => $this->normalizeUrl($payload['url'] ?? route('action-center.index')),
            'module' => $payload['module'] ?? 'general',
            'tag' => $payload['tag'] ?? null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $sent = 0;
        foreach ($subscriptions as $storedSubscription) {
            try {
                $subscription = Subscription::create([
                    'endpoint' => $storedSubscription->endpoint,
                    'publicKey' => $storedSubscription->public_key,
                    'authToken' => $storedSubscription->auth_token,
                    'contentEncoding' => $storedSubscription->content_encoding ?: 'aes128gcm',
                ]);
                $report = $webPush->sendOneNotification($subscription, $json);

                if ($report && $report->isSuccess()) {
                    $storedSubscription->update([
                        'last_used_at' => now(),
                        'failed_at' => null,
                    ]);
                    $sent++;
                } elseif ($report && $report->isSubscriptionExpired()) {
                    $storedSubscription->delete();
                } else {
                    Log::warning('Web Push gagal dikirim.', [
                        'user_id' => $user->id,
                        'reason' => $report ? $report->getReason() : 'Laporan pengiriman tidak tersedia.',
                    ]);
                }
            } catch (\Throwable $exception) {
                Log::warning('Web Push mengalami exception.', [
                    'user_id' => $user->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    public function payloadFromWhatsAppMessage($message, array $context = [])
    {
        $module = $context['module'] ?? 'general';
        $event = $context['event'] ?? 'notification';

        return [
            'title' => $this->titleFor($module, $event),
            'body' => $this->bodyFromMessage($message),
            'url' => $this->urlFromMessage($message) ?: route('action-center.index'),
            'module' => $module,
            'tag' => implode('-', array_filter([
                'papeda',
                $module,
                $event,
                $context['notifiable_id'] ?? null,
            ])),
        ];
    }

    protected function titleFor($module, $event)
    {
        $titles = [
            'persuratan' => 'Persuratan PAPEDA',
            'rapat' => 'Rapat dan Agenda',
            'cuti' => 'Pengajuan Cuti',
            'progress_zi' => 'Progress ZI',
            'perawatan' => 'Perawatan Alat dan Mesin',
            'persediaan' => 'Persediaan',
            'agenda_pimpinan' => 'Agenda Pimpinan',
            'virtual_meeting' => 'Virtual Meeting',
            'voting' => 'E-Voting',
            'security' => 'Keamanan Akun',
        ];

        return $titles[$module] ?? 'PAPEDA';
    }

    protected function bodyFromMessage($message)
    {
        $plain = preg_replace('/https?:\/\/\S+/i', '', (string) $message);
        $plain = str_replace(['*', '_', '`'], '', $plain);
        $lines = collect(preg_split('/\R+/', $plain))
            ->map(function ($line) {
                return trim($line);
            })
            ->filter()
            ->reject(function ($line) {
                return stripos($line, 'PAPEDA |') !== false
                    || stripos($line, 'Yth.') === 0
                    || stripos($line, 'Dengan hormat') === 0
                    || stripos($line, 'Silakan ') === 0
                    || stripos($line, 'Mohon ') === 0;
            })
            ->take(3)
            ->implode(' - ');

        return $lines !== '' ? $lines : 'Terdapat tindak lanjut baru untuk Anda.';
    }

    protected function urlFromMessage($message)
    {
        preg_match_all('/https?:\/\/[^\s<>"\']+/i', (string) $message, $matches);

        return !empty($matches[0]) ? rtrim(end($matches[0]), '.,;)') : null;
    }

    protected function normalizeUrl($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return url('/' . ltrim((string) $url, '/'));
    }
}
