<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('education_level')->nullable()->after('phone'); // bachelor, master, phd, other
            $table->string('field_of_study')->nullable()->after('education_level');
            $table->string('country')->nullable()->after('field_of_study');
            $table->boolean('is_admin')->default(false)->after('country');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'education_level', 'field_of_study', 'country', 'is_admin']);
            $table->dropSoftDeletes();
        });
    }
};
