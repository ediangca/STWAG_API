<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
        Schema::create('lottery', function (Blueprint $table) {
            $table->id('lottery_id');
            $table->string('lottery_session');
            $table->time('time');
        });
        Schema::create('bet', function (Blueprint $table) {
            $table->id('bet_id');
            $table->foreignId('result_id')->nullable()->index();
            $table->foreignId('user_id')->index();  
            $table->integer('number');  
            $table->double('points');  
            $table->timestamps();
        });

        Schema::create('result', function (Blueprint $table) {
            $table->id('result_id');
            $table->foreignId('lottery_id')->index();
            $table->double('winning_points');  
            $table->double('incentives_share');  
            $table->double('mother_share');  
            $table->double('admin_share');  
            $table->double('other_share');  
            $table->date('date');
        });

        Schema::create('winning', function (Blueprint $table) {
            $table->id('winning_id');
            $table->foreignId('result_id')->index();
            $table->foreignId('user_id')->index();
            $table->foreignId('bet_id')->index();
            $table->double('points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('lottery');
        Schema::dropIfExists('bet');
        Schema::dropIfExists('result');
        Schema::dropIfExists('winning');
    }
};
