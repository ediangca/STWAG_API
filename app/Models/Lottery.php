<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lottery extends Model
{
    //
    use HasFactory;

    protected $table = 'lottery';

    protected $primaryKey = 'lottery_id'; 

    protected $fillable = [
        'lottery_session',
        'time',
    ];
}
