<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_prompt',
        'duration_seconds',
        'text_model',
        'image_model',
        'voice_model',
        'generated_script',
        'scene_descriptions',
        'credits_used',
        'actual_cost',
        'status',
        'error_message',
    ];

    protected $casts = [
        'scene_descriptions' => 'array',
        'credits_used' => 'decimal:2',
        'actual_cost' => 'decimal:4',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function video()
    {
        return $this->hasOne(Video::class);
    }
}
