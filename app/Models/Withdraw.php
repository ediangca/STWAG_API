<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
    //
    protected $table = 'withdraws';
    protected $primaryKey = 'withdraw_id'; // Set the primary key to topup_id
    public $incrementing = false; // Disable auto-increment
    protected $keyType = 'string'; // Set the key type to string
    // public $timestamps = false; // Disable timestamps if not needed

    protected $fillable = [
        'withdraw_id',
        'user_id',
        'points',
        'contactno'
    ];
}
