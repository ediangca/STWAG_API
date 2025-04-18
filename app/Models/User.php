<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    
    protected $primaryKey = 'user_id' // Set the primary key to user_id
    ;public $incrementing = false; // Disable auto-increment
    protected $keyType = 'string'; // Set the key type to string

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        // Automatically generate a custom user_id when creating a new user
        static::creating(function ($model) {
            if (empty($model->user_id)) {
                $model->user_id = self::GenerateUserAccID(); // Call your custom function
            }
        });
    }

    /**
     * Call the SQL function to generate user_id.
     *
     * @return string
     */
    public static function GenerateUserAccID(): string
    {
        // Call the SQL function using Laravel's DB facade
        return DB::selectOne('SELECT GenerateUserAccID() AS user_id')->user_id;
    }
    
}
