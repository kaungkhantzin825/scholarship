<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'android_latest_version',
        'android_required_version',
        'android_store_url',
        'ios_latest_version',
        'ios_required_version',
        'ios_store_url',
        'force_update_message',
        'is_maintenance_mode',
        'maintenance_message',
    ];

    protected $casts = [
        'is_maintenance_mode' => 'boolean',
    ];
}
