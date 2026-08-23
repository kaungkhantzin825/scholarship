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
        Schema::table('admob_settings', function (Blueprint $table) {
            // Google-required content/consent tagging (policy compliance)
            $table->string('max_ad_content_rating')->default('T'); // G, PG, T, MA
            $table->boolean('tag_for_child_directed_treatment')->nullable();
            $table->boolean('tag_for_under_age_of_consent')->nullable();

            // Comma-separated hashed device IDs so devs/testers never see (and
            // can't accidentally click) real ads while developing — AdMob
            // policy explicitly bans invalid-traffic clicks on live ads.
            $table->text('test_device_ids')->nullable();

            // Frequency cap so interstitials never feel like a "limit" to the
            // user — just show naturally less often. Not policy-mandated by
            // itself, but over-serving interstitials risks AdMob's
            // "disruptive ads" policy violation.
            $table->unsignedInteger('interstitial_min_interval_seconds')->default(90);
            $table->unsignedInteger('interstitial_max_per_session')->default(3);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admob_settings', function (Blueprint $table) {
            $table->dropColumn([
                'max_ad_content_rating',
                'tag_for_child_directed_treatment',
                'tag_for_under_age_of_consent',
                'test_device_ids',
                'interstitial_min_interval_seconds',
                'interstitial_max_per_session',
            ]);
        });
    }
};
