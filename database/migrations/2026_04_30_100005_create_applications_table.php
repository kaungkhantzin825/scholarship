<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('scholarship_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending');    // pending, reviewed, approved, rejected
            $table->text('cover_letter')->nullable();
            $table->text('notes')->nullable();               // admin notes
            $table->json('additional_info')->nullable();     // extra form data as JSON
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            // Prevent duplicate applications
            $table->unique(['user_id', 'scholarship_id']);
            $table->index(['user_id', 'status']);
            $table->index(['scholarship_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
