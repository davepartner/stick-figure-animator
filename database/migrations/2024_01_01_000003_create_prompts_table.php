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
        Schema::create('prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('original_prompt');
            $table->integer('duration_seconds');
            $table->string('text_model')->nullable();
            $table->string('image_model')->nullable();
            $table->string('voice_model')->nullable();
            $table->text('generated_script')->nullable();
            $table->json('scene_descriptions')->nullable();
            $table->decimal('credits_used', 10, 2)->default(0);
            $table->decimal('actual_cost', 10, 4)->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prompts');
    }
};
