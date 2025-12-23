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
        Schema::table('prompts', function (Blueprint $table) {
            $table->integer('progress_percentage')->default(0)->after('status');
            $table->string('current_stage')->nullable()->after('progress_percentage');
            $table->boolean('stage_text_completed')->default(false)->after('current_stage');
            $table->boolean('stage_images_completed')->default(false)->after('stage_text_completed');
            $table->boolean('stage_voice_completed')->default(false)->after('stage_images_completed');
            $table->boolean('stage_video_completed')->default(false)->after('stage_voice_completed');
            $table->timestamp('text_completed_at')->nullable()->after('stage_video_completed');
            $table->timestamp('images_completed_at')->nullable()->after('text_completed_at');
            $table->timestamp('voice_completed_at')->nullable()->after('images_completed_at');
            $table->timestamp('video_completed_at')->nullable()->after('voice_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prompts', function (Blueprint $table) {
            $table->dropColumn([
                'progress_percentage',
                'current_stage',
                'stage_text_completed',
                'stage_images_completed',
                'stage_voice_completed',
                'stage_video_completed',
                'text_completed_at',
                'images_completed_at',
                'voice_completed_at',
                'video_completed_at',
            ]);
        });
    }
};
