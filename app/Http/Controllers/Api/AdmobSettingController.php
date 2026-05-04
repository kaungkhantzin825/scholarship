<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdmobSetting;
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
        ]);
    }
}
