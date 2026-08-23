<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdmobSetting;
use App\Models\AdWaterfallUnit;
use Illuminate\Http\JsonResponse;

class AdmobSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $setting = AdmobSetting::first();

        if (!$setting) {
            return response()->json([
                'is_enabled' => false,
            ]);
        }

        return response()->json([
            'is_enabled' => $setting->is_enabled,
            'android_app_id' => $setting->android_app_id,
            'android_banner_id' => $setting->android_banner_id,
            'android_interstitial_id' => $setting->android_interstitial_id,
            'ios_app_id' => $setting->ios_app_id,
            'ios_banner_id' => $setting->ios_banner_id,
            'ios_interstitial_id' => $setting->ios_interstitial_id,
            'max_ad_content_rating' => $setting->max_ad_content_rating,
            'tag_for_child_directed_treatment' => $setting->tag_for_child_directed_treatment,
            'tag_for_under_age_of_consent' => $setting->tag_for_under_age_of_consent,
            'test_device_ids' => $setting->test_device_ids
                ? array_values(array_filter(array_map('trim', explode(',', $setting->test_device_ids))))
                : [],
            'interstitial_min_interval_seconds' => $setting->interstitial_min_interval_seconds,
            'interstitial_max_per_session' => $setting->interstitial_max_per_session,
            'waterfall' => $this->waterfall(),
        ]);
    }

    /**
     * Ordered ad-unit fallback chains per platform/ad type, e.g.
     * ['android' => ['banner' => ['id1', 'id2'], 'interstitial' => ['id3']], 'ios' => [...]]
     * The app tries each ID in order and falls through to the next only if
     * the previous one fails to load — a "waterfall" within AdMob itself.
     */
    private function waterfall(): array
    {
        $units = AdWaterfallUnit::where('is_enabled', true)
            ->orderBy('priority')
            ->get(['platform', 'ad_type', 'ad_unit_id']);

        $result = [
            'android' => ['banner' => [], 'interstitial' => []],
            'ios' => ['banner' => [], 'interstitial' => []],
        ];

        foreach ($units as $unit) {
            if (isset($result[$unit->platform][$unit->ad_type])) {
                $result[$unit->platform][$unit->ad_type][] = $unit->ad_unit_id;
            }
        }

        return $result;
    }
}
