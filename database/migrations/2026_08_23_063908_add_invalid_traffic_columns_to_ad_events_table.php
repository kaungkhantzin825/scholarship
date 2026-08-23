<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ad_events', function (Blueprint $table) {
            $table->string('device_id')->nullable()->after('user_id');
            $table->boolean('is_suspicious')->default(false)->after('device_id');
            $table->string('suspicious_reason')->nullable()->after('is_suspicious');

            $table->index(['device_id', 'ad_type', 'event_type', 'created_at'], 'ad_events_device_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_events', function (Blueprint $table) {
            $table->dropIndex('ad_events_device_lookup_idx');
            $table->dropColumn(['device_id', 'is_suspicious', 'suspicious_reason']);
        });
    }
};
