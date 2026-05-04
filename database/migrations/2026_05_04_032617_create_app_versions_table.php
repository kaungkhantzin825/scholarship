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
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('android_latest_version')->default('1.0.0');
            $table->string('android_required_version')->default('1.0.0');
            $table->string('android_store_url')->nullable();
            
            $table->string('ios_latest_version')->default('1.0.0');
            $table->string('ios_required_version')->default('1.0.0');
            $table->string('ios_store_url')->nullable();
            
            $table->text('force_update_message')->nullable();
            $table->boolean('is_maintenance_mode')->default(false);
            $table->text('maintenance_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
