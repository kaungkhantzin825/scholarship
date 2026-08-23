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
        Schema::create('ad_waterfall_units', function (Blueprint $table) {
            $table->id();
            $table->string('platform');   // android, ios
            $table->string('ad_type');    // banner, interstitial
            $table->string('ad_unit_id');
            $table->unsignedInteger('priority')->default(0); // lower tried first
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->index(['platform', 'ad_type', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_waterfall_units');
    }
};
