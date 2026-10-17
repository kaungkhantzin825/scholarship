<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('disk', 32)->default('public');
            $table->string('mime', 128)->nullable();
            $table->string('extension', 16)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('kind', 32)->default('document'); // cv | transcript | essay | certificate | document
            $table->longText('extracted_text')->nullable();
            $table->unsignedInteger('extracted_chars')->default(0);
            $table->boolean('text_extracted')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_uploads');
    }
};
