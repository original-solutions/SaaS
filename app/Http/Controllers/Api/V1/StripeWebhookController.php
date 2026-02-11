<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // Verify Stripe signature
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        if ($secret && $signature) {
            try {
                \Stripe\Webhook::constructEvent(
                    $request->getContent(),
                    $signature,
                    $secret
                );
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid signature.'], 400);
            }
        } elseif ($secret) {
            return response()->json(['error' => 'Missing signature.'], 400);
        }

        $payload = $request->all();
        $eventId = $payload['id'] ?? null;
        $type = $payload['type'] ?? 'unknown';

        // Idempotency check
        if ($eventId && WebhookEvent::where('provider', 'stripe')->where('event_id', $eventId)->exists()) {
            return response()->json(['message' => 'Already processed.'], 200);
        }

        $webhookEvent = WebhookEvent::create([
            'provider' => 'stripe',
            'event_id' => $eventId ?? uniqid('evt_'),
            'type' => $type,
            'payload' => $payload,
            'received_at' => now(),
            'status' => 'received',
            'idempotency_key' => $eventId,
        ]);

        // Dispatch processing job (stub — job not fully implemented yet)
        // ProcessWebhookJob::dispatch($webhookEvent);

        return response()->json(['message' => 'Received.'], 200);
    }
}
