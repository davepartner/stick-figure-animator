<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
        'prompt_id',
        'file_path',
        'audio_path',
        'image_paths',
        'duration_seconds',
        'file_size',
        'expires_at',
        'is_deleted',
        'youtube_titles',
        'youtube_description',
        'youtube_hashtags',
    ];

    protected $casts = [
        'image_paths' => 'array',
        'youtube_titles' => 'array',
        'expires_at' => 'datetime',
        'is_deleted' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function prompt()
    {
        return $this->belongsTo(Prompt::class);
    }

    /**
     * Check if video is expired
     */
    public function isExpired(): bool
    {
        return Carbon::now()->isAfter($this->expires_at);
    }

    /**
     * Get time remaining before deletion
     */
    public function getTimeRemaining(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }

        return $this->expires_at->diffForHumans();
    }

    /**
     * Mark video as deleted and remove file
     */
    public function markAsDeleted(): void
    {
        if (file_exists($this->file_path)) {
            unlink($this->file_path);
        }

        $this->is_deleted = true;
        $this->save();
    }
}
