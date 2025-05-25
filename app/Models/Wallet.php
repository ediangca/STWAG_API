<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    //
    protected $table = 'wallets';
    protected $primaryKey = 'wallet_id'; // Set the primary key to wallet_id
    public $incrementing = false; // Disable auto-incrementing since wallet_id is a string
    protected $keyType = 'string'; // Set the key type to string since wallet_id is a string
    // public $timestamps = false; // Disable timestamps if not needed

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     * 
            $table->string('wallet_id')->primary();
            $table->foreignId('user_id')->index();
            $table->double('points');  
            $table->string('ref_id', 45); //result_id
            $table->string('source', 45); //Source of the points
            $table->boolean('withdrawableFlag')->default(0); //confirm of the points
            $table->boolean('confirmedFlag')->default(0); //confirm of the points
     */


    protected $fillable = [
        'wallet_id',
        'user_id',
        'points',
        'ref_id', //result_id
        'source', //Source of the points
        'withdrawableFlag', //confirm of the points
        'confirmFlag', //confirm of the points
    ];
    // protected $casts = [
    //     'withdrawableFlag' => 'boolean',
    //     'confirmedFlag' => 'boolean',
    // ];
}
