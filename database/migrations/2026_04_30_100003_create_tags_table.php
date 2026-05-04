<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('scholarship_tag', function (Blueprint $table) {
            $table->foreignId('scholarship_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->primary(['scholarship_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_tag');
        Schema::dropIfExists('tags');
    }
};
