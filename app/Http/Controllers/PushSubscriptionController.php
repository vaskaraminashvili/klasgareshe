<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request, UserRepository $users, NotificationService $alerts): JsonResponse
    {
        /** @var array{endpoint: string, key?: string, token?: string, encoding?: string|null, keys?: array{p256dh?: string, auth?: string}} $data */
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:1024'],
            'key' => ['required_without:keys.p256dh', 'nullable', 'string', 'max:255'],
            'token' => ['required_without:keys.auth', 'nullable', 'string', 'max:255'],
            'encoding' => ['nullable', 'string', 'max:32'],
            'keys.p256dh' => ['required_without:key', 'nullable', 'string', 'max:255'],
            'keys.auth' => ['required_without:token', 'nullable', 'string', 'max:255'],
        ]);

        $keys = $data['keys'] ?? [];
        $publicKey = $data['key'] ?? (isset($keys['p256dh']) ? (string) $keys['p256dh'] : '');
        $authToken = $data['token'] ?? (isset($keys['auth']) ? (string) $keys['auth'] : '');
        $encoding = $data['encoding'] ?? 'aes128gcm';

        $alerts->subscribe(
            $users->authenticated(),
            $data['endpoint'],
            $publicKey,
            $authToken,
            $encoding !== '' ? $encoding : 'aes128gcm',
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, UserRepository $users, NotificationService $alerts): JsonResponse
    {
        /** @var array{endpoint: string} $data */
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:1024'],
        ]);

        $alerts->unsubscribe($users->authenticated(), $data['endpoint']);

        return response()->json(['ok' => true]);
    }
}
