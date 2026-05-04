<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    public function index()
    {
        $settings = AppVersion::firstOrCreate(['id' => 1]);
        
        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }
}
