<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopUp extends Model
{
    //
    protected $table = 'topups';
    protected $primaryKey = 'topup_id'; // Set the primary key to topup_id
    public $incrementing = false; // Disable auto-increment
    protected $keyType = 'string'; // Set the key type to string
    // public $timestamps = false; // Disable timestamps if not needed

    protected $fillable = [
        'topup_id',
        'user_id',
        'points',
        'gcash_ref_no'
    ];
}
