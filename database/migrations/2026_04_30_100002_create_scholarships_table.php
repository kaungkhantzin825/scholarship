<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('description');                          // rich HTML
            $table->longText('eligibility')->nullable();              // eligibility criteria (rich HTML)
            $table->text('benefits')->nullable();                     // what you get
            $table->text('required_documents')->nullable();           // documents needed
            $table->decimal('amount', 10, 2)->nullable();             // scholarship value
            $table->string('amount_type')->default('full');           // full, partial, monthly, other
            $table->string('currency', 10)->default('USD');
            $table->string('country')->nullable();                    // host country
            $table->json('eligible_countries')->nullable();           // array of eligible countries
            $table->string('level')->default('any');                  // bachelor, master, phd, diploma, any
            $table->string('field_of_study')->nullable();
            $table->date('deadline')->nullable();
            $table->date('start_date')->nullable();
            $table->string('official_link')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('status')->default('active');              // active, expired, closed
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('applications_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_featured']);
            $table->index(['category_id', 'status']);
            $table->index('deadline');
            $table->index('country');
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarships');
    }
};
