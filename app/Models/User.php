<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'credits',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'credits' => 'decimal:2',
    ];

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has enough credits
     */
    public function hasCredits(float $amount): bool
    {
        return $this->credits >= $amount;
    }

    /**
     * Deduct credits from user
     */
    public function deductCredits(float $amount): bool
    {
        if (!$this->hasCredits($amount)) {
            return false;
        }

        $this->credits -= $amount;
        $this->save();

        return true;
    }

    /**
     * Add credits to user
     */
    public function addCredits(float $amount): void
    {
        $this->credits += $amount;
        $this->save();
    }

    /**
     * Relationships
     */
    public function prompts()
    {
        return $this->hasMany(Prompt::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
