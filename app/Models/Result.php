<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    //
    use HasFactory;

    protected $table = 'results';
    protected $primaryKey = 'result_id';
    protected $keyType = 'string'; // Set the primary key type to string
    public $incrementing = false; // Disable auto-incrementing for string primary key
    // public $timestamps = false; // Disable timestamps if not needed

    protected $fillable = [
        'result_id',
        'lottery_id',
        'number',
        'winning_points', 
        'incentives_share',  
        'mother_share',  
        'admin_share',  
        'other_share'  
    ];
}
