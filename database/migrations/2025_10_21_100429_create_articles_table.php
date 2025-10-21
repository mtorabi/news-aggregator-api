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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('title');
            $table->string('url', 500)->nullable();
            $table->text('body')->nullable();
            $table->string('source', 100);
            $table->string('author')->nullable();
            $table->text('image_url')->nullable();
            $table->string('category')->nullable();
            $table->timestamp('published_at');
            $table->timestamps();
            
            $table->index(['source', 'external_id']);
            $table->index(['source', 'published_at']);
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
