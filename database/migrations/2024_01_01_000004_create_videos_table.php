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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('audio_path')->nullable();
            $table->json('image_paths')->nullable();
            $table->integer('duration_seconds');
            $table->bigInteger('file_size')->nullable();
            $table->timestamp('expires_at');
            $table->boolean('is_deleted')->default(false);
            $table->json('youtube_titles')->nullable();
            $table->text('youtube_description')->nullable();
            $table->text('youtube_hashtags')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
