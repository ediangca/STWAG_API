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
            $table->timestamps();
        });
        Schema::create('bets', function (Blueprint $table) {
            $table->id('bet_id');
            $table->string('result_id')->nullable()->index();
            $table->string('user_id')->index();  
            $table->integer('number');  
            $table->double('points');  
            $table->timestamps();
        });

        Schema::create('results', function (Blueprint $table) {
            $table->string('result_id')->primary(true);
            $table->foreignId('lottery_id')->index();
            $table->integer('number');  
            $table->double('winning_points', 10, 2)->nullable()->default(0.00);  
            $table->double('incentives_share', 10, 2)->nullable()->default(0.00);  
            $table->double('mother_share', 10, 2)->nullable()->default(0.00);  
            $table->double('admin_share', 10, 2)->nullable()->default(0.00);  
            $table->double('other_share', 10, 2)->nullable()->default(0.00);  
            $table->timestamps();
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
        Schema::dropIfExists('bets');
        Schema::dropIfExists('result');
        Schema::dropIfExists('winning');
    }
};
