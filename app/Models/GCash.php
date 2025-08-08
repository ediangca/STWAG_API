<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GCash extends Model
{
    //
    protected $table = 'gcash';
    protected $primaryKey = 'gcashid';
    public $timestamps = true;
    
    protected $fillable = [
        'gcashno'
    ];
}
