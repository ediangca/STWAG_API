<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bet extends Model
{
    //
    use HasFactory;

    protected $table = 'bets';

    protected $primaryKey = 'bet_id'; 

    protected $fillable = [
        'result_id',
        'user_id',
        'number',
        'points',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
}
