<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdEventController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ad_type' => 'required|string|in:banner,interstitial',
            'event_type' => 'required|string|in:impression,click',
            'platform' => 'nullable|string|in:android,ios',
            'screen' => 'nullable|string|max:100',
            'device_id' => 'nullable|string|max:100',
        ]);

        [$isSuspicious, $reason] = $this->detectInvalidTraffic($validated);

        AdEvent::create([
            ...$validated,
            'user_id' => $request->user('sanctum')?->id,
            'is_suspicious' => $isSuspicious,
            'suspicious_reason' => $reason,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Lightweight invalid-traffic (IVT) heuristics. Nothing here blocks the
     * write — flagged events are still stored so admins can review them, but
     * excluded from headline stats like CTR (see AdEventsOverview).
     */
    private function detectInvalidTraffic(array $data): array
    {
        $deviceId = $data['device_id'] ?? null;
        if (!$deviceId) {
            return [false, null];
        }

        // Click with no matching impression from the same device/ad type in
        // the preceding window — a click can't happen without a real view.
        if ($data['event_type'] === 'click') {
            $hasImpression = AdEvent::where('device_id', $deviceId)
                ->where('ad_type', $data['ad_type'])
                ->where('event_type', 'impression')
                ->where('created_at', '>=', now()->subMinutes(10))
                ->exists();

            if (!$hasImpression) {
                return [true, 'click_without_impression'];
            }
        }

        // Burst of events from one device in a short window — a real user
        // does not generate this many ad events in a minute.
        $recentCount = AdEvent::where('device_id', $deviceId)
            ->where('created_at', '>=', now()->subMinute())
            ->count();
        if ($recentCount >= 15) {
            return [true, 'high_frequency'];
        }

        // Repeated clicks from the same device in a short window.
        if ($data['event_type'] === 'click') {
            $recentClicks = AdEvent::where('device_id', $deviceId)
                ->where('event_type', 'click')
                ->where('created_at', '>=', now()->subMinutes(5))
                ->count();
            if ($recentClicks >= 4) {
                return [true, 'excessive_clicks'];
            }
        }

        return [false, null];
    }
}
