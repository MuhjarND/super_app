<?php

namespace App\Http\Controllers;

use App\WebPushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class WebPushSubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function config(Request $request)
    {
        $enabled = !empty(config('services.webpush.public_key'))
            && !empty(config('services.webpush.private_key'))
            && Schema::hasTable('web_push_subscriptions');

        return response()->json([
            'enabled' => $enabled,
            'public_key' => $enabled ? config('services.webpush.public_key') : null,
            'subscribed' => $enabled && WebPushSubscription::where('user_id', $request->user()->id)
                ->whereNull('failed_at')
                ->exists(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Schema::hasTable('web_push_subscriptions'), 503);

        $data = $request->validate([
            'endpoint' => 'required|url|max:4000',
            'keys.p256dh' => 'required|string|max:1000',
            'keys.auth' => 'required|string|max:1000',
            'content_encoding' => 'nullable|in:aesgcm,aes128gcm',
        ]);
        $hash = hash('sha256', $data['endpoint']);

        WebPushSubscription::updateOrCreate(
            ['endpoint_hash' => $hash],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $data['endpoint'],
                'public_key' => data_get($data, 'keys.p256dh'),
                'auth_token' => data_get($data, 'keys.auth'),
                'content_encoding' => $data['content_encoding'] ?? 'aes128gcm',
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'last_used_at' => now(),
                'failed_at' => null,
            ]
        );

        return response()->json(['message' => 'Notifikasi perangkat berhasil diaktifkan.']);
    }

    public function destroy(Request $request)
    {
        abort_unless(Schema::hasTable('web_push_subscriptions'), 503);

        $data = $request->validate(['endpoint' => 'required|url|max:4000']);
        WebPushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint_hash', hash('sha256', $data['endpoint']))
            ->delete();

        return response()->json(['message' => 'Notifikasi perangkat dinonaktifkan.']);
    }
}
